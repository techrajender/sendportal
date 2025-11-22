<?php

namespace App\Services;

use App\Models\CampaignSubscriberTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\Message;
use Sendportal\Base\Models\Subscriber;

class TrackingService
{
    /**
     * Valid task types
     */
    const TASK_TYPES = [
        'email_sent',
        'email_opened',
        'email_clicked',
        'newsletter_opened',
        'landing_page_opened',
        'thank_you_received',
        'asset_downloaded',
    ];

    /**
     * Valid status types
     */
    const STATUS_TYPES = [
        'opened',
        'not_opened',
        'pending',
        'failed',
    ];

    /**
     * Track an event for a campaign subscriber
     *
     * @param int $campaignId
     * @param string $subscriberHash
     * @param string $taskType
     * @param string $status
     * @param array|null $metadata
     * @return CampaignSubscriberTracking|null
     */
    public function track(
        int $campaignId,
        string $subscriberHash,
        string $taskType,
        string $status = 'opened',
        ?array $metadata = null
    ): ?CampaignSubscriberTracking {
        // Validate task type
        if (!in_array($taskType, self::TASK_TYPES)) {
            Log::warning("Invalid task type: {$taskType}");
            return null;
        }

        // Validate status
        if (!in_array($status, self::STATUS_TYPES)) {
            Log::warning("Invalid status: {$status}");
            return null;
        }

        // Find campaign
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            Log::warning("Campaign not found: {$campaignId}");
            return null;
        }

        // Find subscriber by hash
        $subscriber = Subscriber::where('hash', $subscriberHash)->first();
        if (!$subscriber) {
            Log::warning("Subscriber not found with hash: {$subscriberHash}");
            return null;
        }

        // Check if this event was already tracked (idempotency)
        $existing = CampaignSubscriberTracking::where('campaign_id', $campaignId)
            ->where('subscriber_id', $subscriber->id)
            ->where('task_type', $taskType)
            ->first();

        $isNewEvent = !$existing;

        if ($existing) {
            // Update existing record
            $existing->update([
                'status' => $status,
                'metadata' => $metadata,
                'tracked_at' => now(),
            ]);
            $tracking = $existing;
        } else {
            // Create new tracking record
            $tracking = CampaignSubscriberTracking::create([
                'campaign_id' => $campaignId,
                'subscriber_id' => $subscriber->id,
                'subscriber_hash' => $subscriberHash,
                'task_type' => $taskType,
                'status' => $status,
                'metadata' => $metadata,
                'tracked_at' => now(),
            ]);
        }

        // Update Message model and campaign counts for opened events
        if ($status === 'opened' && in_array($taskType, ['email_opened', 'email_clicked'])) {
            // Always update Message model (even for existing tracking records)
            // This ensures Message.opened_at/clicked_at is set even if tracking was created before this code
            $this->updateMessageTracking($campaign, $subscriber, $taskType);
            
            // Only update campaign counts for new events (to avoid unnecessary queries)
            if ($isNewEvent) {
                Log::info('Updating campaign counts for new event', [
                    'campaign_id' => $campaignId,
                    'task_type' => $taskType,
                    'subscriber_id' => $subscriber->id,
                    'is_new_event' => $isNewEvent,
                ]);
                $this->updateCampaignCounts($campaign, $taskType);
            } else {
                Log::debug('Skipping campaign count update (existing event)', [
                    'campaign_id' => $campaignId,
                    'task_type' => $taskType,
                    'is_new_event' => $isNewEvent,
                ]);
            }
        }

        return $tracking;
    }

    /**
     * Track email sent event
     *
     * @param int $campaignId
     * @param int $subscriberId
     * @return CampaignSubscriberTracking|null
     */
    public function trackEmailSent(int $campaignId, int $subscriberId): ?CampaignSubscriberTracking
    {
        $subscriber = Subscriber::find($subscriberId);
        if (!$subscriber) {
            return null;
        }

        return $this->track(
            $campaignId,
            $subscriber->hash,
            'email_sent',
            'opened'
        );
    }

    /**
     * Get tracking data for a campaign
     *
     * @param int $campaignId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCampaignTracking(int $campaignId)
    {
        return CampaignSubscriberTracking::where('campaign_id', $campaignId)
            ->with(['subscriber'])
            ->orderBy('tracked_at', 'desc')
            ->get();
    }

    /**
     * Get tracking data for a specific subscriber in a campaign
     *
     * @param int $campaignId
     * @param int $subscriberId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubscriberTracking(int $campaignId, int $subscriberId)
    {
        return CampaignSubscriberTracking::where('campaign_id', $campaignId)
            ->where('subscriber_id', $subscriberId)
            ->orderBy('tracked_at', 'asc')
            ->get();
    }

    /**
     * Update Message model's opened_at or clicked_at fields
     *
     * @param Campaign $campaign
     * @param Subscriber $subscriber
     * @param string $taskType
     * @return void
     */
    protected function updateMessageTracking(Campaign $campaign, Subscriber $subscriber, string $taskType): void
    {
        try {
            // Find the message for this campaign and subscriber
            $message = Message::where('source_type', Campaign::class)
                ->where('source_id', $campaign->id)
                ->where('subscriber_id', $subscriber->id)
                ->first();

            if (!$message) {
                Log::debug('Message not found for campaign and subscriber', [
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $subscriber->id,
                    'task_type' => $taskType,
                ]);
                return;
            }

            if ($taskType === 'email_opened') {
                $updates = [];
                
                // Set opened_at if not already set
                if (!$message->opened_at) {
                    $updates['opened_at'] = now();
                }
                
                // Increment open_count for total opens calculation
                $updates['open_count'] = ($message->open_count ?? 0) + 1;
                
                if (!empty($updates)) {
                    $message->update($updates);
                    Log::info('Updated message opened_at and open_count', [
                        'message_id' => $message->id,
                        'campaign_id' => $campaign->id,
                        'subscriber_id' => $subscriber->id,
                        'open_count' => $updates['open_count'] ?? $message->open_count,
                    ]);
                }
            } elseif ($taskType === 'email_clicked') {
                $updates = [];
                
                // Set clicked_at if not already set
                if (!$message->clicked_at) {
                    $updates['clicked_at'] = now();
                }
                
                // Increment click_count for total clicks calculation
                $updates['click_count'] = ($message->click_count ?? 0) + 1;
                
                if (!empty($updates)) {
                    $message->update($updates);
                    Log::info('Updated message clicked_at and click_count', [
                        'message_id' => $message->id,
                        'campaign_id' => $campaign->id,
                        'subscriber_id' => $subscriber->id,
                        'click_count' => $updates['click_count'] ?? $message->click_count,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update message tracking', [
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'task_type' => $taskType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update campaign open and click counts based on tracking events
     *
     * @param Campaign $campaign
     * @param string $taskType
     * @return void
     */
    protected function updateCampaignCounts(Campaign $campaign, string $taskType): void
    {
        try {
            if ($taskType === 'email_opened') {
                // Count unique email opens for this campaign
                $result = DB::table('sendportal_campaign_subscriber_tracking')
                    ->where('campaign_id', $campaign->id)
                    ->where('task_type', 'email_opened')
                    ->where('status', 'opened')
                    ->select(DB::raw('COUNT(DISTINCT subscriber_id) as count'))
                    ->first();

                $openCount = $result ? (int) $result->count : 0;
                $campaign->update(['open_count' => $openCount]);
                
                Log::info('Updated campaign open_count', [
                    'campaign_id' => $campaign->id,
                    'open_count' => $openCount,
                    'previous_count' => $campaign->getOriginal('open_count'),
                ]);
            } elseif ($taskType === 'email_clicked') {
                // Count unique email clicks for this campaign
                $result = DB::table('sendportal_campaign_subscriber_tracking')
                    ->where('campaign_id', $campaign->id)
                    ->where('task_type', 'email_clicked')
                    ->where('status', 'opened')
                    ->select(DB::raw('COUNT(DISTINCT subscriber_id) as count'))
                    ->first();

                $clickCount = $result ? (int) $result->count : 0;
                $campaign->update(['click_count' => $clickCount]);
                
                Log::info('Updated campaign click_count', [
                    'campaign_id' => $campaign->id,
                    'click_count' => $clickCount,
                    'previous_count' => $campaign->getOriginal('click_count'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update campaign counts', [
                'campaign_id' => $campaign->id,
                'task_type' => $taskType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

