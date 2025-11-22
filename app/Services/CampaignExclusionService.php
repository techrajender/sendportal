<?php

namespace App\Services;

use App\Models\CampaignSubscriberTracking;
use Illuminate\Support\Collection;
use Sendportal\Base\Models\Campaign;

class CampaignExclusionService
{
    /**
     * Get subscriber IDs to exclude based on selected campaigns
     *
     * @param array $excludeCampaignIds
     * @return array
     */
    public function getExcludedSubscriberIds(array $excludeCampaignIds): array
    {
        if (empty($excludeCampaignIds)) {
            return [];
        }

        // Get all subscriber IDs who received emails from the excluded campaigns
        $excludedSubscriberIds = CampaignSubscriberTracking::whereIn('campaign_id', $excludeCampaignIds)
            ->where('task_type', 'email_sent')
            ->where('status', 'opened')
            ->distinct()
            ->pluck('subscriber_id')
            ->toArray();

        return $excludedSubscriberIds;
    }

    /**
     * Filter subscribers to exclude those from selected campaigns
     *
     * @param Collection $subscribers
     * @param array $excludeCampaignIds
     * @return Collection
     */
    public function filterSubscribers(Collection $subscribers, array $excludeCampaignIds): Collection
    {
        if (empty($excludeCampaignIds)) {
            return $subscribers;
        }

        $excludedSubscriberIds = $this->getExcludedSubscriberIds($excludeCampaignIds);

        if (empty($excludedSubscriberIds)) {
            return $subscribers;
        }

        return $subscribers->reject(function ($subscriber) use ($excludedSubscriberIds) {
            return in_array($subscriber->id, $excludedSubscriberIds);
        });
    }

    /**
     * Get campaigns available for exclusion (excluding current campaign)
     *
     * @param int $workspaceId
     * @param int $currentCampaignId
     * @return Collection
     */
    public function getAvailableCampaignsForExclusion(int $workspaceId, int $currentCampaignId): Collection
    {
        // Show all campaigns except the current one
        // Users can select which campaigns to exclude recipients from
        return Campaign::where('workspace_id', $workspaceId)
            ->where('id', '!=', $currentCampaignId)
            ->orderBy('name')
            ->get();
    }
}

