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
     * Override to set status to "sending" when messages are created, not "sent"
     * Campaign will be marked as "sent" only after all messages are actually sent
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
            // Set status to "sending" instead of "sent"
            // Campaign will be marked as "sent" only after all messages are actually sent
            \Illuminate\Support\Facades\Log::info('Campaign messages created, setting status to sending', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'total_messages' => $totalMessages,
            ]);

            $campaign->status_id = CampaignStatus::STATUS_SENDING;
            $campaign->save();
        }
    }
}

