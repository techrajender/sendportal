<?php

namespace App\Listeners;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use Sendportal\Base\Events\MessageDispatchEvent;
use Sendportal\Base\Models\Message;

class FilterExcludedSubscribers
{
    protected $exclusionService;

    /**
     * Create the event listener.
     */
    public function __construct(CampaignExclusionService $exclusionService)
    {
        $this->exclusionService = $exclusionService;
    }

    /**
     * Handle the event.
     * This listener prevents messages from being dispatched to subscribers
     * who have already received emails from excluded campaigns.
     * 
     * Note: This runs synchronously (not queued) to filter before dispatch
     */
    public function handle(MessageDispatchEvent $event): void
    {
        $message = $event->message;
        
        // Check if observer marked this for exclusion
        if ($message->getAttribute('_should_exclude')) {
            \Illuminate\Support\Facades\Log::info('Excluding subscriber - message marked for exclusion', [
                'message_id' => $message->id,
                'campaign_id' => $message->source_id,
                'subscriber_id' => $message->subscriber_id,
            ]);
            
            try {
                $message->delete();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error deleting excluded message', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
            return;
        }
        
        // Only process campaign messages
        if (!$message->isCampaign() || !$message->subscriber_id) {
            return;
        }

        $campaignId = $message->source_id;
        
        // Get excluded campaign IDs for this campaign
        $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaignId)
            ->pluck('excluded_campaign_id')
            ->toArray();

        if (empty($excludedCampaignIds)) {
            // No exclusions, allow the message
            return;
        }

        // Check if this subscriber received emails from excluded campaigns
        $excludedSubscriberIds = $this->exclusionService->getExcludedSubscriberIds($excludedCampaignIds);

        if (in_array($message->subscriber_id, $excludedSubscriberIds)) {
            // Subscriber is excluded, delete the message to prevent sending
            \Illuminate\Support\Facades\Log::info('Excluding subscriber from campaign - message deleted', [
                'campaign_id' => $campaignId,
                'subscriber_id' => $message->subscriber_id,
                'message_id' => $message->id,
                'excluded_campaign_ids' => $excludedCampaignIds,
            ]);
            
            // Delete the message to prevent it from being sent
            try {
                $message->delete();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error deleting excluded message', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

