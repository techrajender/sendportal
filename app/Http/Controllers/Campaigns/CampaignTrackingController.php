<?php

namespace App\Http\Controllers\Campaigns;

use App\Services\TrackingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Controller;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;

class CampaignTrackingController extends Controller
{
    protected $campaignRepo;
    protected $trackingService;

    public function __construct(
        CampaignTenantRepositoryInterface $campaignRepository,
        TrackingService $trackingService
    ) {
        $this->campaignRepo = $campaignRepository;
        $this->trackingService = $trackingService;
    }

    /**
     * Show campaign tracking view.
     *
     * @return RedirectResponse|View
     * @throws Exception
     */
    public function index(int $id)
    {
        $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $id);

        if ($campaign->draft) {
            return redirect()->route('sendportal.campaigns.edit', $id);
        }

        if ($campaign->queued || $campaign->sending) {
            return redirect()->route('sendportal.campaigns.status', $id);
        }

        // Get paginated tracking data grouped by subscriber
        $subscribers = $this->trackingService->getCampaignTrackingPaginated($campaign->id, 25);

        return view('sendportal::campaigns.reports.tracking', compact('campaign', 'subscribers'));
    }
}

