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
     * This is a backup check - exclusion should be handled in ExtendedCreateMessages pipeline
     * But we keep this as a safety net
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
            // Subscriber is excluded - throw exception to prevent creation
            // This should not happen if ExtendedCreateMessages is working correctly
            \Illuminate\Support\Facades\Log::warning('MessageObserver: Attempted to create message for excluded subscriber', [
                'campaign_id' => $campaignId,
                'subscriber_id' => $message->subscriber_id,
                'excluded_campaign_ids' => $excludedCampaignIds,
            ]);
            
            throw new \Exception('Cannot create message for excluded subscriber');
        }
    }
}

