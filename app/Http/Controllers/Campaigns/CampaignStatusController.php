<?php

namespace App\Http\Controllers\Campaigns;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use App\Services\Campaigns\ExtendedCampaignDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Controller;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;

class CampaignStatusController extends Controller
{
    protected $campaignRepo;
    protected $dispatchService;
    protected $exclusionService;

    public function __construct(
        CampaignTenantRepositoryInterface $campaignRepository,
        ExtendedCampaignDispatchService $dispatchService,
        CampaignExclusionService $exclusionService
    ) {
        $this->campaignRepo = $campaignRepository;
        $this->dispatchService = $dispatchService;
        $this->exclusionService = $exclusionService;
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
     * Update campaign status
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status_id' => 'required|integer|in:1,2,3,4,5',
        ]);

        try {
            $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $id);

            if (!$campaign) {
                return response()->json(['error' => 'Campaign not found'], 404);
            }

            $statusId = $request->input('status_id');
            $status = CampaignStatus::find($statusId);

            if (!$status) {
                return response()->json(['error' => 'Invalid status'], 400);
            }

            $campaign->status_id = $statusId;
            $campaign->save();

            return response()->json([
                'success' => true,
                'message' => 'Campaign status updated successfully',
                'status' => [
                    'id' => $status->id,
                    'name' => $status->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update campaign status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available campaign statuses
     */
    public function statuses(): JsonResponse
    {
        $statuses = CampaignStatus::all()->map(function ($status) {
            return [
                'id' => $status->id,
                'name' => $status->name,
            ];
        });

        return response()->json($statuses);
    }

    /**
     * Reprocess a stuck campaign that is in queued status
     */
    public function reprocess(int $id): JsonResponse
    {
        try {
            $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $id);

            if (!$campaign) {
                return response()->json(['error' => 'Campaign not found'], 404);
            }

            // Only allow reprocessing campaigns that are in queued status
            if (!$campaign->queued) {
                return response()->json([
                    'error' => 'Campaign is not in queued status. Only queued campaigns can be reprocessed.',
                    'current_status' => $campaign->status->name ?? 'Unknown'
                ], 400);
            }

            // Check recipient count before processing
            $recipientCount = $this->calculateRecipientCount($campaign);

            if ($recipientCount === 0) {
                Log::warning('Cannot reprocess campaign with 0 recipients', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                ]);

                return response()->json([
                    'error' => 'This campaign has 0 recipients. Please update the campaign settings (tags or send to all) or change the campaign status. No processing is needed.',
                    'recipient_count' => 0,
                    'suggestion' => 'Update campaign recipient settings or change campaign status to Draft or Cancelled.'
                ], 400);
            }

            Log::info('Reprocessing stuck campaign', [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'status_id' => $campaign->status_id,
                'recipient_count' => $recipientCount,
            ]);

            // Ensure campaign is in queued status before dispatching
            if ($campaign->status_id !== CampaignStatus::STATUS_QUEUED) {
                $campaign->status_id = CampaignStatus::STATUS_QUEUED;
                $campaign->save();
            }

            // Dispatch the campaign (this will process the campaign and queue message dispatch jobs)
            // We call handle directly to ensure immediate processing for stuck campaigns
            $this->dispatchService->handle($campaign);

            return response()->json([
                'success' => true,
                'message' => 'Campaign reprocessing initiated successfully',
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status->name ?? 'Queued',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error reprocessing campaign', [
                'campaign_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to reprocess campaign: ' . $e->getMessage()
            ], 500);
        }
    }
}

