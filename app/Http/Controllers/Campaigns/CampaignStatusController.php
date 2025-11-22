<?php

namespace App\Http\Controllers\Campaigns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Controller;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;

class CampaignStatusController extends Controller
{
    protected $campaignRepo;

    public function __construct(CampaignTenantRepositoryInterface $campaignRepository)
    {
        $this->campaignRepo = $campaignRepository;
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
}

