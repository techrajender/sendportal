<?php

namespace App\Http\Controllers\Campaigns;

use App\Models\CampaignExclusion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Controller;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;

class CampaignExclusionController extends Controller
{
    protected $campaignRepo;

    public function __construct(CampaignTenantRepositoryInterface $campaignRepository)
    {
        $this->campaignRepo = $campaignRepository;
    }

    /**
     * Store excluded campaigns
     */
    public function store(Request $request, int $campaignId): JsonResponse
    {
        $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $campaignId);

        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        $excludedCampaignIds = $request->input('excluded_campaign_ids', []);

        // Delete existing exclusions
        CampaignExclusion::where('campaign_id', $campaignId)->delete();

        // Create new exclusions
        foreach ($excludedCampaignIds as $excludedCampaignId) {
            CampaignExclusion::create([
                'campaign_id' => $campaignId,
                'excluded_campaign_id' => $excludedCampaignId,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exclusions saved successfully'
        ]);
    }

    /**
     * Remove a single excluded campaign
     */
    public function destroy(Request $request, int $campaignId): JsonResponse
    {
        $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $campaignId);

        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        $excludedCampaignId = $request->input('excluded_campaign_id');

        CampaignExclusion::where('campaign_id', $campaignId)
            ->where('excluded_campaign_id', $excludedCampaignId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exclusion removed successfully'
        ]);
    }
}

