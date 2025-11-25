<?php

namespace App\Http\Controllers\Campaigns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Controller;
use Sendportal\Base\Models\Tag;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;

class CampaignRecipientsController extends Controller
{
    protected $campaignRepo;

    public function __construct(CampaignTenantRepositoryInterface $campaignRepository)
    {
        $this->campaignRepo = $campaignRepository;
    }

    /**
     * Get recipients for selected tags or all subscribers
     */
    public function getRecipients(Request $request, int $campaignId): JsonResponse
    {
        $request->validate([
            'recipients_type' => 'required|in:send_to_all,send_to_tags',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:sendportal_tags,id',
        ]);

        try {
            $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $campaignId);

            if (!$campaign) {
                return response()->json(['error' => 'Campaign not found'], 404);
            }

            $recipientsType = $request->input('recipients_type');
            $workspaceId = Sendportal::currentWorkspaceId();

            // If sending to all subscribers
            if ($recipientsType === 'send_to_all') {
                $subscribers = \Sendportal\Base\Models\Subscriber::where('workspace_id', $workspaceId)
                    ->whereNull('unsubscribed_at')
                    ->select('id', 'email', 'first_name', 'last_name')
                    ->orderBy('email')
                    ->get();
            } else {
                // Get recipients from selected tags
                $tagIds = $request->input('tag_ids', []);
                
                if (empty($tagIds)) {
                    return response()->json([
                        'error' => 'No tags selected'
                    ], 400);
                }

                // Get unique subscribers from selected tags
                $subscriberIds = [];
                foreach ($tagIds as $tagId) {
                    $tag = Tag::where('workspace_id', $workspaceId)->find($tagId);
                    if ($tag) {
                        $tagSubscriberIds = $tag->subscribers()
                            ->whereNull('unsubscribed_at')
                            ->pluck('sendportal_subscribers.id')
                            ->toArray();
                        $subscriberIds = array_merge($subscriberIds, $tagSubscriberIds);
                    }
                }

                $uniqueSubscriberIds = array_unique($subscriberIds);

                // Get subscribers with their details
                $subscribers = \Sendportal\Base\Models\Subscriber::whereIn('id', $uniqueSubscriberIds)
                    ->select('id', 'email', 'first_name', 'last_name')
                    ->orderBy('email')
                    ->get();
            }

            // Apply exclusion filter if exclusions exist
            $excludedCampaignIds = \App\Models\CampaignExclusion::where('campaign_id', $campaignId)
                ->pluck('excluded_campaign_id')
                ->toArray();

            if (!empty($excludedCampaignIds)) {
                // Get subscriber IDs who received emails from excluded campaigns
                // Check for email_sent records - if record exists, email was sent
                $excludedSubscriberIds = \App\Models\CampaignSubscriberTracking::whereIn('campaign_id', $excludedCampaignIds)
                    ->where('task_type', 'email_sent')
                    ->distinct()
                    ->pluck('subscriber_id')
                    ->toArray();

                // Filter out excluded subscribers
                if (!empty($excludedSubscriberIds)) {
                    $subscribers = $subscribers->reject(function ($subscriber) use ($excludedSubscriberIds) {
                        return in_array($subscriber->id, $excludedSubscriberIds);
                    });
                }
            }

            return response()->json([
                'success' => true,
                'recipients' => $subscribers->values(),
                'total_count' => $subscribers->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get recipients: ' . $e->getMessage()
            ], 500);
        }
    }
}

