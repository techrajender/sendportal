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
use Sendportal\Base\Models\Message;
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
     * Check and fix stuck campaigns
     * This can be called to immediately check and fix campaigns stuck in sending status
     */
    public function checkStuck(): JsonResponse
    {
        try {
            $stuckCampaigns = Campaign::where('status_id', CampaignStatus::STATUS_SENDING)
                ->get();

            $fixedCount = 0;
            $fixedCampaigns = [];

            foreach ($stuckCampaigns as $campaign) {
                $totalMessages = Message::where('source_id', $campaign->id)
                    ->where('source_type', Campaign::class)
                    ->count();

                $sentMessages = Message::where('source_id', $campaign->id)
                    ->where('source_type', Campaign::class)
                    ->whereNotNull('sent_at')
                    ->count();

                // If all messages are sent, mark campaign as sent
                if ($totalMessages > 0 && $sentMessages >= $totalMessages) {
                    Campaign::where('id', $campaign->id)
                        ->update(['status_id' => CampaignStatus::STATUS_SENT]);

                    Log::info('Fixed stuck campaign via checkStuck endpoint', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'total_messages' => $totalMessages,
                        'sent_messages' => $sentMessages,
                    ]);

                    $fixedCampaigns[] = [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                    ];
                    $fixedCount++;
                } elseif ($totalMessages === 0) {
                    // No messages, mark as sent
                    Campaign::where('id', $campaign->id)
                        ->update(['status_id' => CampaignStatus::STATUS_SENT]);

                    Log::info('Fixed stuck campaign with 0 messages via checkStuck endpoint', [
                        'campaign_id' => $campaign->id,
                        'campaign_name' => $campaign->name,
                    ]);

                    $fixedCampaigns[] = [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                    ];
                    $fixedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Checked {$stuckCampaigns->count()} campaign(s) in sending status. Fixed {$fixedCount} campaign(s).",
                'checked_count' => $stuckCampaigns->count(),
                'fixed_count' => $fixedCount,
                'fixed_campaigns' => $fixedCampaigns,
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking stuck campaigns', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to check stuck campaigns: ' . $e->getMessage()
            ], 500);
        }
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

            // Allow reprocessing campaigns that are in queued or stuck in sending status
            if (!$campaign->queued && !$campaign->sending) {
                return response()->json([
                    'error' => 'Campaign is not in queued or sending status. Only queued or stuck sending campaigns can be reprocessed.',
                    'current_status' => $campaign->status->name ?? 'Unknown'
                ], 400);
            }
            
            // If campaign is in sending status, check if it's stuck
            if ($campaign->sending) {
                $totalMessages = \Sendportal\Base\Models\Message::where('source_id', $campaign->id)
                    ->where('source_type', \Sendportal\Base\Models\Campaign::class)
                    ->count();
                
                $sentMessages = \Sendportal\Base\Models\Message::where('source_id', $campaign->id)
                    ->where('source_type', \Sendportal\Base\Models\Campaign::class)
                    ->whereNotNull('sent_at')
                    ->count();
                
                // Check if campaign is actually stuck
                $isStuck = false;
                if ($totalMessages > 0 && $sentMessages === 0) {
                    // Has messages but none sent - definitely stuck
                    $isStuck = true;
                } elseif ($totalMessages > 0 && $sentMessages < $totalMessages) {
                    // Check if updated more than 5 minutes ago
                    $minutesSinceUpdate = $campaign->updated_at ? now()->diffInMinutes($campaign->updated_at) : 0;
                    if ($minutesSinceUpdate > 5) {
                        $isStuck = true;
                    }
                }
                
                if (!$isStuck) {
                    return response()->json([
                        'error' => 'Campaign is still processing. Please wait for it to complete.',
                        'current_status' => $campaign->status->name ?? 'Unknown',
                        'total_messages' => $totalMessages,
                        'sent_messages' => $sentMessages
                    ], 400);
                }
                
                Log::info('Reprocessing stuck sending campaign', [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'total_messages' => $totalMessages,
                    'sent_messages' => $sentMessages,
                ]);
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
                'is_sending' => $campaign->sending,
            ]);

            // If campaign is in sending status, delete unsent messages to allow reprocessing
            $reprocessSubscriberIds = [];
            if ($campaign->sending) {
                $unsentMessages = \Sendportal\Base\Models\Message::where('source_id', $campaign->id)
                    ->where('source_type', \Sendportal\Base\Models\Campaign::class)
                    ->whereNull('sent_at')
                    ->get();
                
                $unsentCount = $unsentMessages->count();
                
                if ($unsentCount > 0) {
                    // Track subscriber IDs who had unsent messages deleted
                    // This allows ExtendedCreateMessages to create new messages for them
                    $reprocessSubscriberIds = $unsentMessages->pluck('subscriber_id')->unique()->toArray();
                    
                    Log::info('Deleting unsent messages before reprocessing', [
                        'campaign_id' => $campaign->id,
                        'unsent_messages_count' => $unsentCount,
                        'message_ids' => $unsentMessages->pluck('id')->toArray(),
                        'reprocess_subscriber_ids' => $reprocessSubscriberIds,
                    ]);
                    
                    // Delete all unsent messages - this will allow canSendToSubscriber to return true
                    // and create fresh messages for these subscribers
                    foreach ($unsentMessages as $message) {
                        $message->delete();
                    }
                    
                    Log::info('Deleted unsent messages', [
                        'campaign_id' => $campaign->id,
                        'deleted_count' => $unsentCount,
                        'reprocess_subscriber_ids_count' => count($reprocessSubscriberIds),
                    ]);
                }
            }

            // Reset campaign to queued status before dispatching (for both queued and stuck sending campaigns)
            // Use withoutEvents() to prevent CampaignObserver from creating a duplicate delayed job
            if ($campaign->status_id !== CampaignStatus::STATUS_QUEUED) {
                $oldStatusId = $campaign->status_id;
                
                Campaign::withoutEvents(function () use ($campaign) {
                    $campaign->status_id = CampaignStatus::STATUS_QUEUED;
                    $campaign->save();
                });
                
                Log::info('Campaign status reset to queued for reprocessing (without events)', [
                    'campaign_id' => $campaign->id,
                    'old_status_id' => $oldStatusId,
                    'new_status_id' => CampaignStatus::STATUS_QUEUED,
                ]);
            }

            // Refresh campaign and reload relationships to ensure we have the latest data
            $campaign->refresh();
            $campaign->load(['tags', 'status']);

            Log::info('About to dispatch campaign for reprocessing', [
                'campaign_id' => $campaign->id,
                'status_id' => $campaign->status_id,
                'is_queued' => $campaign->queued,
                'recipient_count' => $recipientCount,
            ]);

            // Store reprocess subscriber IDs in cache so ExtendedCreateMessages can access them
            // Use a campaign-specific key that expires after 5 minutes
            if (!empty($reprocessSubscriberIds)) {
                $cacheKey = "campaign_reprocess_subscribers_{$campaign->id}";
                \Illuminate\Support\Facades\Cache::put($cacheKey, $reprocessSubscriberIds, 300); // 5 minutes
                
                Log::info('Stored reprocess subscriber IDs in cache', [
                    'campaign_id' => $campaign->id,
                    'cache_key' => $cacheKey,
                    'subscriber_ids_count' => count($reprocessSubscriberIds),
                ]);
            }

            // Dispatch the campaign (this will process the campaign and queue message dispatch jobs)
            // We call handle directly to ensure immediate processing for stuck campaigns
            try {
                $this->dispatchService->handle($campaign);
                
                Log::info('Campaign dispatch handle completed successfully', [
                    'campaign_id' => $campaign->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Error in dispatch handle during reprocess', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            } finally {
                // Clean up cache after processing
                if (!empty($reprocessSubscriberIds)) {
                    $cacheKey = "campaign_reprocess_subscribers_{$campaign->id}";
                    \Illuminate\Support\Facades\Cache::forget($cacheKey);
                }
            }

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

