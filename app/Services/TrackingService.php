<?php

namespace App\Services;

use App\Models\CampaignSubscriberTracking;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\Subscriber;

class TrackingService
{
    /**
     * Valid task types
     */
    const TASK_TYPES = [
        'email_sent',
        'email_opened',
        'email_clicked',
        'newsletter_opened',
        'landing_page_opened',
        'thank_you_received',
        'asset_downloaded',
    ];

    /**
     * Valid status types
     */
    const STATUS_TYPES = [
        'opened',
        'not_opened',
        'pending',
        'failed',
    ];

    /**
     * Track an event for a campaign subscriber
     *
     * @param int $campaignId
     * @param string $subscriberHash
     * @param string $taskType
     * @param string $status
     * @param array|null $metadata
     * @return CampaignSubscriberTracking|null
     */
    public function track(
        int $campaignId,
        string $subscriberHash,
        string $taskType,
        string $status = 'opened',
        ?array $metadata = null
    ): ?CampaignSubscriberTracking {
        // Validate task type
        if (!in_array($taskType, self::TASK_TYPES)) {
            Log::warning("Invalid task type: {$taskType}");
            return null;
        }

        // Validate status
        if (!in_array($status, self::STATUS_TYPES)) {
            Log::warning("Invalid status: {$status}");
            return null;
        }

        // Find campaign
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            Log::warning("Campaign not found: {$campaignId}");
            return null;
        }

        // Find subscriber by hash
        $subscriber = Subscriber::where('hash', $subscriberHash)->first();
        if (!$subscriber) {
            Log::warning("Subscriber not found with hash: {$subscriberHash}");
            return null;
        }

        // Check if this event was already tracked (idempotency)
        $existing = CampaignSubscriberTracking::where('campaign_id', $campaignId)
            ->where('subscriber_id', $subscriber->id)
            ->where('task_type', $taskType)
            ->first();

        if ($existing) {
            // Update existing record
            $existing->update([
                'status' => $status,
                'metadata' => $metadata,
                'tracked_at' => now(),
            ]);
            return $existing;
        }

        // Create new tracking record
        return CampaignSubscriberTracking::create([
            'campaign_id' => $campaignId,
            'subscriber_id' => $subscriber->id,
            'subscriber_hash' => $subscriberHash,
            'task_type' => $taskType,
            'status' => $status,
            'metadata' => $metadata,
            'tracked_at' => now(),
        ]);
    }

    /**
     * Track email sent event
     *
     * @param int $campaignId
     * @param int $subscriberId
     * @return CampaignSubscriberTracking|null
     */
    public function trackEmailSent(int $campaignId, int $subscriberId): ?CampaignSubscriberTracking
    {
        $subscriber = Subscriber::find($subscriberId);
        if (!$subscriber) {
            return null;
        }

        return $this->track(
            $campaignId,
            $subscriber->hash,
            'email_sent',
            'opened'
        );
    }

    /**
     * Get tracking data for a campaign
     *
     * @param int $campaignId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCampaignTracking(int $campaignId)
    {
        return CampaignSubscriberTracking::where('campaign_id', $campaignId)
            ->with(['subscriber'])
            ->orderBy('tracked_at', 'desc')
            ->get();
    }

    /**
     * Get tracking data for a specific subscriber in a campaign
     *
     * @param int $campaignId
     * @param int $subscriberId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubscriberTracking(int $campaignId, int $subscriberId)
    {
        return CampaignSubscriberTracking::where('campaign_id', $campaignId)
            ->where('subscriber_id', $subscriberId)
            ->orderBy('tracked_at', 'asc')
            ->get();
    }
}

