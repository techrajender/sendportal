<?php

namespace App\Pipelines\Campaigns;

use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Models\Message;
use Sendportal\Base\Pipelines\Campaigns\CompleteCampaign as BaseCompleteCampaign;

class ExtendedCompleteCampaign extends BaseCompleteCampaign
{
    /**
     * Mark the campaign as complete in the database
     * Override to check for 0 messages and mark as sent immediately
     *
     * @param Campaign $campaign
     * @return void
     */
    protected function markCampaignAsComplete(Campaign $campaign): void
    {
        // Count total messages for this campaign
        $totalMessages = Message::where('source_id', $campaign->id)
            ->where('source_type', Campaign::class)
            ->count();

        // If 0 messages, mark campaign as sent (no emails to send)
        if ($totalMessages === 0) {
            \Illuminate\Support\Facades\Log::info('Campaign has 0 messages, marking as sent', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
            ]);

            $campaign->status_id = CampaignStatus::STATUS_SENT;
            $campaign->save();
        } else {
            // Use parent implementation for campaigns with messages
            parent::markCampaignAsComplete($campaign);
        }
    }
}

