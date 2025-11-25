<?php

namespace App\Observers;

use App\Services\Campaigns\ExtendedCampaignDispatchService;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;

class CampaignObserver
{
    protected $dispatchService;

    public function __construct(ExtendedCampaignDispatchService $dispatchService)
    {
        $this->dispatchService = $dispatchService;
    }

    /**
     * Handle the Campaign "updated" event.
     * Automatically queue dispatch job when campaign status changes to queued
     */
    public function updated(Campaign $campaign): void
    {
        // Check if status was changed to queued
        if ($campaign->wasChanged('status_id') && $campaign->status_id === CampaignStatus::STATUS_QUEUED) {
            // Check recipient count before queuing
            $recipientCount = $this->calculateRecipientCount($campaign);

            if ($recipientCount === 0) {
                Log::warning('Campaign has 0 recipients, cannot process. Please update campaign settings or change status.', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'send_to_all' => $campaign->send_to_all,
                    'tags_count' => $campaign->tags->count(),
                ]);

                // Don't queue the job, just log and return
                return;
            }

            Log::info('Campaign status changed to queued, queuing dispatch job', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'old_status_id' => $campaign->getOriginal('status_id'),
                'new_status_id' => $campaign->status_id,
                'recipient_count' => $recipientCount,
            ]);

            // Queue the dispatch job with a small delay to ensure status is saved
            try {
                $dispatchService = app(ExtendedCampaignDispatchService::class);
                $campaignId = $campaign->id;

                // Queue the handle method with a small delay
                dispatch(function () use ($dispatchService, $campaignId) {
                    $campaign = \Sendportal\Base\Models\Campaign::find($campaignId);
                    if ($campaign && $campaign->queued) {
                        $dispatchService->handle($campaign);
                    }
                })->delay(now()->addSeconds(5))->onQueue('default');

                Log::info('Campaign dispatch job queued', [
                    'campaign_id' => $campaign->id,
                    'delay_seconds' => 5,
                    'recipient_count' => $recipientCount,
                ]);
            } catch (\Exception $e) {
                Log::error('Error queuing campaign dispatch job', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
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
        $excludedCampaignIds = \App\Models\CampaignExclusion::where('campaign_id', $campaign->id)
            ->pluck('excluded_campaign_id')
            ->toArray();

        if (!empty($excludedCampaignIds) && $recipientCount > 0) {
            $exclusionService = app(\App\Services\CampaignExclusionService::class);
            $excludedSubscriberIds = $exclusionService->getExcludedSubscriberIds($excludedCampaignIds);

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
}

