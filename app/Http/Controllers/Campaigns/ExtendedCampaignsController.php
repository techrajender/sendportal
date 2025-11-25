<?php

namespace App\Http\Controllers\Campaigns;

use Exception;
use Illuminate\Contracts\View\View as ViewContract;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Campaigns\CampaignsController as BaseCampaignsController;
use Sendportal\Base\Services\Campaigns\CampaignStatisticsService;

class ExtendedCampaignsController extends BaseCampaignsController
{
    /**
     * Override index method to use pagination of 10 and handle filters
     * 
     * @throws Exception
     */
    public function index(): ViewContract
    {
        $workspaceId = Sendportal::currentWorkspaceId();
        $params = ['draft' => true];
        
        // Get request using helper function to maintain compatibility
        $request = request();
        
        // Add filter parameters
        if ($request->has('search') && !empty($request->get('search'))) {
            $params['search'] = $request->get('search');
        }
        if ($request->has('status_id') && !empty($request->get('status_id'))) {
            $params['status_id'] = (int) $request->get('status_id');
        }
        
        $campaigns = $this->campaigns->paginate($workspaceId, 'created_atDesc', ['status'], 10, $params);
        
        // Append query parameters to pagination links
        $campaigns->appends($request->query());

        return view('sendportal::campaigns.index', [
            'campaigns' => $campaigns,
            'campaignStats' => $this->campaignStatisticsService->getForPaginator($campaigns, $workspaceId),
        ]);
    }

    /**
     * Override sent method to use pagination of 10 and handle filters
     * 
     * @throws Exception
     */
    public function sent(): ViewContract
    {
        $workspaceId = Sendportal::currentWorkspaceId();
        $params = ['sent' => true];
        
        // Get request using helper function to maintain compatibility
        $request = request();
        
        // Add filter parameters
        if ($request->has('search') && !empty($request->get('search'))) {
            $params['search'] = $request->get('search');
        }
        if ($request->has('status_id') && !empty($request->get('status_id'))) {
            $params['status_id'] = (int) $request->get('status_id');
        }
        
        $campaigns = $this->campaigns->paginate($workspaceId, 'created_atDesc', ['status'], 10, $params);
        
        // Append query parameters to pagination links
        $campaigns->appends($request->query());

        return view('sendportal::campaigns.index', [
            'campaigns' => $campaigns,
            'campaignStats' => $this->campaignStatisticsService->getForPaginator($campaigns, $workspaceId),
        ]);
    }
}

