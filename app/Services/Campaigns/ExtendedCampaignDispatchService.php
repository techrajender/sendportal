<?php

namespace App\Services\Campaigns;

use Illuminate\Pipeline\Pipeline;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Pipelines\Campaigns\CreateMessages;
use Sendportal\Base\Pipelines\Campaigns\StartCampaign;
use Sendportal\Base\Services\Campaigns\CampaignDispatchService as BaseCampaignDispatchService;
use App\Pipelines\Campaigns\ExtendedCompleteCampaign;

class ExtendedCampaignDispatchService extends BaseCampaignDispatchService
{
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

        $pipes = [
            StartCampaign::class,
            CreateMessages::class,
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

