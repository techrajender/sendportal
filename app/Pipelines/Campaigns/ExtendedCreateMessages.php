<?php

namespace App\Pipelines\Campaigns;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use Sendportal\Base\Pipelines\Campaigns\CreateMessages as BaseCreateMessages;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\Message;
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

    /**
     * Override findMessage to allow creating new messages for subscribers
     * who had unsent messages deleted during reprocessing
     *
     * @param Campaign $campaign
     * @param Subscriber $subscriber
     * @return Message|null
     */
    protected function findMessage(Campaign $campaign, Subscriber $subscriber): ?Message
    {
        // Check if this campaign is being reprocessed and this subscriber had unsent messages deleted
        $cacheKey = "campaign_reprocess_subscribers_{$campaign->id}";
        $reprocessSubscriberIds = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        
        // If this subscriber had unsent messages deleted during reprocessing,
        // only check for unsent messages (not sent ones)
        // This allows creating new messages for reprocessing
        if (!empty($reprocessSubscriberIds) && in_array($subscriber->id, $reprocessSubscriberIds)) {
            \Log::info('ExtendedCreateMessages: Checking for unsent messages only (reprocessing)', [
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
            ]);
            
            return Message::where('workspace_id', $campaign->workspace_id)
                ->where('subscriber_id', $subscriber->id)
                ->where('source_type', Campaign::class)
                ->where('source_id', $campaign->id)
                ->whereNull('sent_at') // Only check unsent messages
                ->first();
        }

        // Default behavior: check for any message (sent or unsent)
        return parent::findMessage($campaign, $subscriber);
    }
}

