<?php

namespace App\Observers;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use Sendportal\Base\Models\Message;

class MessageObserver
{
    protected $exclusionService;

    public function __construct(CampaignExclusionService $exclusionService)
    {
        $this->exclusionService = $exclusionService;
    }

    /**
     * Handle the Message "creating" event.
     * Prevent message creation if subscriber should be excluded
     */
    public function creating(Message $message): void
    {
        // Only process campaign messages
        if (!$message->isCampaign() || !$message->subscriber_id) {
            return; // Allow creation
        }

        $campaignId = $message->source_id;
        
        // Get excluded campaign IDs for this campaign
        $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaignId)
            ->pluck('excluded_campaign_id')
            ->toArray();

        if (empty($excludedCampaignIds)) {
            // No exclusions, allow creation
            return;
        }

        // Check if this subscriber received emails from excluded campaigns
        $excludedSubscriberIds = $this->exclusionService->getExcludedSubscriberIds($excludedCampaignIds);

        if (in_array($message->subscriber_id, $excludedSubscriberIds)) {
            // Subscriber is excluded, prevent message creation by setting a flag
            // We'll handle this in the event listener instead
            \Illuminate\Support\Facades\Log::info('Subscriber should be excluded from campaign', [
                'campaign_id' => $campaignId,
                'subscriber_id' => $message->subscriber_id,
                'excluded_campaign_ids' => $excludedCampaignIds,
            ]);
            
            // Store exclusion info in message metadata for the event listener
            $message->setAttribute('_should_exclude', true);
        }
    }
}

