<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Exception;
use Illuminate\View\View;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\DashboardController as BaseDashboardController;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Models\Subscriber;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;
use Sendportal\Base\Repositories\Messages\MessageTenantRepositoryInterface;
use Sendportal\Base\Repositories\Subscribers\SubscriberTenantRepositoryInterface;
use Sendportal\Base\Services\Campaigns\CampaignStatisticsService;

class ExtendedDashboardController extends BaseDashboardController
{
    /**
     * @throws Exception
     */
    public function index(): View
    {
        $workspaceId = Sendportal::currentWorkspaceId();
        $subscriberGrowthChart = $this->getSubscriberGrowthChart($workspaceId);
        $request = request();

        // Get paginated completed campaigns (5 per page)
        $completedCampaigns = Campaign::where('workspace_id', $workspaceId)
            ->where('status_id', CampaignStatus::STATUS_SENT)
            ->with(['status'])
            ->orderBy('created_at', 'DESC')
            ->paginate(5, ['*'], 'campaigns_page');

        // Get paginated recent subscribers (5 per page)
        $recentSubscribers = Subscriber::where('workspace_id', $workspaceId)
            ->orderBy('created_at', 'DESC')
            ->paginate(5, ['*'], 'subscribers_page');

        // Preserve both pagination query parameters
        // When clicking campaigns pagination, preserve subscribers_page
        $completedCampaigns->appends($request->except('campaigns_page'));
        
        // When clicking subscribers pagination, preserve campaigns_page
        $recentSubscribers->appends($request->except('subscribers_page'));

        return view('sendportal::dashboard.index', [
            'recentSubscribers' => $recentSubscribers,
            'completedCampaigns' => $completedCampaigns,
            'campaignStats' => $this->campaignStatisticsService->getForPaginator($completedCampaigns, $workspaceId),
            'subscriberGrowthChartLabels' => json_encode($subscriberGrowthChart['labels']),
            'subscriberGrowthChartData' => json_encode($subscriberGrowthChart['data']),
        ]);
    }
}

