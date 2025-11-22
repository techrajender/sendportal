<?php

namespace App\Pipelines\Campaigns;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use Sendportal\Base\Pipelines\Campaigns\CreateMessages as BaseCreateMessages;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\Subscriber;

class ExtendedCreateMessages extends BaseCreateMessages
{
    protected $exclusionService;

    public function __construct(CampaignExclusionService $exclusionService)
    {
        $this->exclusionService = $exclusionService;
    }

    /**
     * Dispatch the campaign to a given subscriber
     * Override to filter excluded subscribers before creating messages
     *
     * @param Campaign $campaign
     * @param $subscribers
     */
    protected function dispatchToSubscriber(Campaign $campaign, $subscribers)
    {
        \Log::info('- Number of subscribers in this chunk: ' . count($subscribers));

        // Get excluded subscriber IDs for this campaign
        $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaign->id)
            ->pluck('excluded_campaign_id')
            ->toArray();

        $excludedSubscriberIds = [];
        if (!empty($excludedCampaignIds)) {
            $excludedSubscriberIds = $this->exclusionService->getExcludedSubscriberIds($excludedCampaignIds);
            
            \Log::info('ExtendedCreateMessages: Filtering excluded subscribers', [
                'campaign_id' => $campaign->id,
                'excluded_campaign_ids' => $excludedCampaignIds,
                'excluded_subscriber_ids_count' => count($excludedSubscriberIds),
                'total_subscribers_in_chunk' => count($subscribers),
            ]);
        }

        foreach ($subscribers as $subscriber) {
            // Skip if subscriber is excluded
            if (!empty($excludedSubscriberIds) && in_array($subscriber->id, $excludedSubscriberIds)) {
                \Log::info('ExtendedCreateMessages: Skipping excluded subscriber', [
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $subscriber->id,
                    'subscriber_email' => $subscriber->email,
                ]);
                continue;
            }

            if (! $this->canSendToSubscriber($campaign->id, $subscriber->id)) {
                continue;
            }

            $this->dispatch($campaign, $subscriber);
        }
    }
}

