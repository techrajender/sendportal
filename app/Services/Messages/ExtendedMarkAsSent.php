<?php

namespace App\Services\Messages;

use App\Services\TrackingService;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Models\Message;
use Sendportal\Base\Services\Messages\MarkAsSent as BaseMarkAsSent;

class ExtendedMarkAsSent extends BaseMarkAsSent
{
    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Mark message as sent and automatically track email_sent event
     * Also check if all campaign messages are sent and update campaign status
     */
    public function handle(Message $message, string $messageId): Message
    {
        // First, mark as sent (parent functionality)
        $message = parent::handle($message, $messageId);

        // Automatically track email_sent event when email is successfully sent
        try {
            // Check if message is for a campaign and has been sent
            // Note: message_id can be -1 for some email services, so we check sent_at instead
            if ($message->isCampaign() && $message->sent_at) {
                // Refresh to ensure we have the latest data
                $message->refresh();
                
                // Get campaign from source relationship
                $campaign = $message->source;
                
                if ($campaign && $message->subscriber_id) {
                    \Illuminate\Support\Facades\Log::info('Tracking email_sent', [
                        'message_id' => $message->id,
                        'campaign_id' => $campaign->id,
                        'subscriber_id' => $message->subscriber_id
                    ]);
                    
                    $tracking = $this->trackingService->trackEmailSent(
                        $campaign->id,
                        $message->subscriber_id
                    );
                    
                    if ($tracking) {
                        \Illuminate\Support\Facades\Log::info('Email sent tracked successfully', [
                            'tracking_id' => $tracking->id
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Failed to track email_sent', [
                            'message_id' => $message->id,
                            'campaign_id' => $campaign->id,
                            'subscriber_id' => $message->subscriber_id
                        ]);
                    }

                    // Check if all messages for this campaign are sent
                    $this->checkAndUpdateCampaignStatus($campaign);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Cannot track email_sent - missing data', [
                        'message_id' => $message->id,
                        'is_campaign' => $message->isCampaign(),
                        'has_campaign' => $campaign ? 'yes' : 'no',
                        'has_subscriber_id' => $message->subscriber_id ? 'yes' : 'no',
                        'sent_at' => $message->sent_at,
                        'message_id_external' => $message->message_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error tracking email_sent', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $message;
    }

    /**
     * Check if all messages for a campaign are sent and update campaign status
     *
     * @param Campaign $campaign
     * @return void
     */
    protected function checkAndUpdateCampaignStatus(Campaign $campaign): void
    {
        try {
            // Refresh campaign to get latest status
            $campaign->refresh();
            
            // Only check if campaign is in sending status
            if ($campaign->status_id !== CampaignStatus::STATUS_SENDING) {
                return;
            }

            // Count total messages for this campaign
            $totalMessages = Message::where('source_id', $campaign->id)
                ->where('source_type', Campaign::class)
                ->count();

            // Count messages that have been sent (have sent_at timestamp)
            $sentMessages = Message::where('source_id', $campaign->id)
                ->where('source_type', Campaign::class)
                ->whereNotNull('sent_at')
                ->count();

            \Illuminate\Support\Facades\Log::info('Checking campaign completion status', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'total_messages' => $totalMessages,
                'sent_messages' => $sentMessages,
                'status_id' => $campaign->status_id,
            ]);

            // If all messages are sent, mark campaign as sent
            if ($totalMessages > 0 && $sentMessages >= $totalMessages) {
                \Illuminate\Support\Facades\Log::info('All campaign messages sent, marking campaign as sent', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'total_messages' => $totalMessages,
                    'sent_messages' => $sentMessages,
                ]);

                // Use fresh query to avoid stale model issues
                $updated = Campaign::where('id', $campaign->id)
                    ->where('status_id', CampaignStatus::STATUS_SENDING) // Only update if still in sending status
                    ->update(['status_id' => CampaignStatus::STATUS_SENT]);
                
                if ($updated > 0) {
                    \Illuminate\Support\Facades\Log::info('Campaign status updated to sent', [
                        'campaign_id' => $campaign->id,
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Campaign status update failed - may have been updated by another process', [
                        'campaign_id' => $campaign->id,
                        'current_status_id' => $campaign->status_id,
                    ]);
                }
            } elseif ($totalMessages > 0 && $sentMessages < $totalMessages) {
                // Log progress for debugging (only log every 10th message to reduce log noise)
                if ($sentMessages % 10 === 0 || $sentMessages === 1) {
                    \Illuminate\Support\Facades\Log::debug('Campaign still sending', [
                        'campaign_id' => $campaign->id,
                        'total_messages' => $totalMessages,
                        'sent_messages' => $sentMessages,
                        'remaining' => $totalMessages - $sentMessages,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error checking campaign completion status', [
                'campaign_id' => $campaign->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

