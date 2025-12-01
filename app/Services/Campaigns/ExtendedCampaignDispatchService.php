<?php

namespace App\Services\Campaigns;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use Illuminate\Pipeline\Pipeline;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Pipelines\Campaigns\StartCampaign;
use Sendportal\Base\Services\Campaigns\CampaignDispatchService as BaseCampaignDispatchService;
use App\Pipelines\Campaigns\ExtendedCreateMessages;
use App\Pipelines\Campaigns\ExtendedCompleteCampaign;

class ExtendedCampaignDispatchService extends BaseCampaignDispatchService
{
    protected $exclusionService;

    public function __construct(CampaignExclusionService $exclusionService = null)
    {
        $this->exclusionService = $exclusionService ?? app(CampaignExclusionService::class);
    }

    /**
     * Calculate recipient count for a campaign
     *
     * @param Campaign $campaign
     * @return int
     */
    protected function calculateRecipientCount(Campaign $campaign): int
    {
        $recipientCount = 0;

        if ($campaign->send_to_all) {
            // Count all active subscribers in workspace
            $subscriberIds = \Sendportal\Base\Models\Subscriber::where('workspace_id', $campaign->workspace_id)
                ->whereNull('unsubscribed_at')
                ->pluck('id')
                ->toArray();
            $recipientCount = count($subscriberIds);
        } else {
            // Count unique subscribers across all selected tags
            $subscriberIds = [];
            foreach ($campaign->tags as $tag) {
                $tagSubscriberIds = $tag->subscribers()
                    ->whereNull('unsubscribed_at')
                    ->pluck('sendportal_subscribers.id')
                    ->toArray();
                $subscriberIds = array_merge($subscriberIds, $tagSubscriberIds);
            }
            $recipientCount = count(array_unique($subscriberIds));
        }

        // Apply exclusion filter if exclusions exist
        $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaign->id)
            ->pluck('excluded_campaign_id')
            ->toArray();

        if (!empty($excludedCampaignIds) && $recipientCount > 0) {
            $excludedSubscriberIds = $this->exclusionService->getExcludedSubscriberIds($excludedCampaignIds);

            if ($campaign->send_to_all) {
                $allSubscriberIds = \Sendportal\Base\Models\Subscriber::where('workspace_id', $campaign->workspace_id)
                    ->whereNull('unsubscribed_at')
                    ->pluck('id')
                    ->toArray();
                $excludedCount = count(array_intersect($allSubscriberIds, $excludedSubscriberIds));
            } else {
                $subscriberIds = [];
                foreach ($campaign->tags as $tag) {
                    $tagSubscriberIds = $tag->subscribers()
                        ->whereNull('unsubscribed_at')
                        ->pluck('sendportal_subscribers.id')
                        ->toArray();
                    $subscriberIds = array_merge($subscriberIds, $tagSubscriberIds);
                }
                $uniqueSubscriberIds = array_unique($subscriberIds);
                $excludedCount = count(array_intersect($uniqueSubscriberIds, $excludedSubscriberIds));
            }

            $recipientCount = max(0, $recipientCount - $excludedCount);
        }

        return $recipientCount;
    }

    /**
     * Dispatch the campaign
     * Override to use ExtendedCompleteCampaign which handles 0 messages
     *
     * @param Campaign $campaign
     * @return void
     */
    public function handle(Campaign $campaign)
    {
        // check if the campaign still exists
        if (! $campaign = $this->findCampaign($campaign->id)) {
            return;
        }

        if (! $campaign->queued) {
            \Log::error('Campaign does not have a queued status campaign_id=' . $campaign->id . ' status_id=' . $campaign->status_id);

            return;
        }

        // Check recipient count before processing
        $recipientCount = $this->calculateRecipientCount($campaign);

        if ($recipientCount === 0) {
            \Log::warning('Campaign has 0 recipients, cannot process. Please update campaign settings or change status.', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'send_to_all' => $campaign->send_to_all,
                'tags_count' => $campaign->tags->count(),
            ]);

            // Don't process, just return
            return;
        }

        $pipes = [
            StartCampaign::class,
            ExtendedCreateMessages::class, // Use our extended version that filters excluded subscribers
            ExtendedCompleteCampaign::class, // Use our extended version
        ];

        try {
            app(Pipeline::class)
                ->send($campaign)
                ->through($pipes)
                ->then(function ($campaign) {
                    return $campaign;
                });
        } catch (\Exception $exception) {
            \Log::error('Error dispatching campaign id=' . $campaign->id . ' exception=' . $exception->getMessage() . ' trace=' . $exception->getTraceAsString());
        }
    }
}

