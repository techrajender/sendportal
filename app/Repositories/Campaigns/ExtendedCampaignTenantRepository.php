<?php

namespace App\Repositories\Campaigns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Repositories\Campaigns\PostgresCampaignTenantRepository;

class ExtendedCampaignTenantRepository extends PostgresCampaignTenantRepository
{
    /**
     * Get the average time it takes for a message to be opened once it has been delivered for the campaign.
     * Uses sent_at as fallback when delivered_at is not available.
     *
     * @param Campaign $campaign
     * @return string
     */
    public function getAverageTimeToOpen(Campaign $campaign): string
    {
        $average = $campaign->opens()
            ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (opened_at - COALESCE(delivered_at, sent_at))))) as average_time_to_open')
            ->value('average_time_to_open');

        return $average ? $this->secondsToHms($average) : 'N/A';
    }

    /**
     * Get the average time it takes for a link to be clicked for the campaign.
     * Uses sent_at as fallback when delivered_at is not available.
     *
     * @param Campaign $campaign
     * @return string
     */
    public function getAverageTimeToClick(Campaign $campaign): string
    {
        $average = $campaign->clicks()
            ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (clicked_at - COALESCE(delivered_at, sent_at))))) as average_time_to_click')
            ->value('average_time_to_click');

        return $average ? $this->secondsToHms($average) : 'N/A';
    }

    /**
     * Override applyFilters to add search and status_id filters
     *
     * @param Builder $instance
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $instance, array $filters = []): void
    {
        // Call parent to apply draft/sent filters
        parent::applyFilters($instance, $filters);

        // Apply search filter
        if (Arr::get($filters, 'search')) {
            $search = Arr::get($filters, 'search');
            $instance->where('name', 'like', '%' . $search . '%');
        }

        // Apply status_id filter (if not already filtered by draft/sent)
        if (Arr::get($filters, 'status_id') && !Arr::get($filters, 'draft') && !Arr::get($filters, 'sent')) {
            $instance->where('status_id', Arr::get($filters, 'status_id'));
        } elseif (Arr::get($filters, 'status_id') && (Arr::get($filters, 'draft') || Arr::get($filters, 'sent'))) {
            // If draft/sent filter is active, further filter by status_id
            $instance->where('status_id', Arr::get($filters, 'status_id'));
        }
    }
}

