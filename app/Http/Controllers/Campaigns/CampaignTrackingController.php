<?php

namespace App\Http\Controllers\Campaigns;

use App\Services\TrackingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @param int $id
     * @param Request $request
     * @return RedirectResponse|View
     * @throws Exception
     */
    public function index(int $id, Request $request)
    {
        $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $id);

        if ($campaign->draft) {
            return redirect()->route('sendportal.campaigns.edit', $id);
        }

        if ($campaign->queued || $campaign->sending) {
            return redirect()->route('sendportal.campaigns.status', $id);
        }

        // Get filter parameters
        $searchEmail = $request->get('search_email');
        $filterTaskType = $request->get('filter_task_type');

        // Get paginated tracking data grouped by subscriber
        $subscribers = $this->trackingService->getCampaignTrackingPaginated(
            $campaign->id, 
            25, 
            $searchEmail, 
            $filterTaskType
        );

        return view('sendportal::campaigns.reports.tracking', compact('campaign', 'subscribers', 'searchEmail', 'filterTaskType'));
    }

    /**
     * Export tracking data to CSV
     *
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function export(int $id, Request $request)
    {
        $campaign = $this->campaignRepo->find(Sendportal::currentWorkspaceId(), $id);

        if ($campaign->draft) {
            return redirect()->route('sendportal.campaigns.edit', $id);
        }

        // Get filter parameters
        $searchEmail = $request->get('search_email');
        $filterTaskType = $request->get('filter_task_type');
        $includeDeviceInfo = $request->boolean('include_device_info', false);

        // Get all tracking data for export
        $subscribers = $this->trackingService->getCampaignTrackingForExport(
            $campaign->id,
            $searchEmail,
            $filterTaskType
        );

        // Generate CSV
        $filename = 'campaign_' . $campaign->id . '_tracking_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($subscribers, $includeDeviceInfo) {
            $file = fopen('php://output', 'w');
            
            // Define task types
            $taskTypes = [
                'email_sent' => 'Email Sent',
                'email_opened' => 'Email Opened',
                'email_clicked' => 'Email Clicked',
                'newsletter_opened' => 'Newsletter Opened',
                'landing_page_opened' => 'Landing Page',
                'thank_you_received' => 'Thank You',
                'asset_downloaded' => 'Asset Downloaded',
            ];

            // Write CSV headers - ensure no duplicates
            $csvHeaders = ['Email', 'First Name', 'Last Name'];
            
            foreach ($taskTypes as $taskType => $label) {
                $csvHeaders[] = $label . ' Status';
                $csvHeaders[] = $label . ' Time When Opened';
                
                if ($includeDeviceInfo) {
                    $csvHeaders[] = $label . ' Browser';
                    $csvHeaders[] = $label . ' Device Type';
                    $csvHeaders[] = $label . ' OS';
                    $csvHeaders[] = $label . ' IP Address';
                    $csvHeaders[] = $label . ' User Agent';
                }
            }
            
            $csvHeaders[] = 'Last Activity';
            
            // Write headers only once
            fputcsv($file, $csvHeaders);

            // Write data rows - ensure proper column alignment
            foreach ($subscribers as $subscriber) {
                $events = $subscriber->tracking_events ?? [];
                
                // Initialize row with subscriber info
                $row = [
                    $subscriber->email ?? '',
                    $subscriber->first_name ?? '',
                    $subscriber->last_name ?? '',
                ];

                $lastActivity = null;
                
                // Process each task type in the same order as headers
                foreach ($taskTypes as $taskType => $label) {
                    $event = $events[$taskType] ?? null;
                    
                    if ($event && $event->status === 'opened') {
                        // Status column
                        $row[] = 'Yes';
                        // Time When Opened column
                        $row[] = $event->tracked_at ? $event->tracked_at->format('Y-m-d H:i:s') : '';
                        
                        if ($includeDeviceInfo) {
                            $deviceInfo = $event->metadata['device_info'] ?? [];
                            $row[] = $deviceInfo['browser'] ?? '';
                            $row[] = $deviceInfo['device_type'] ?? '';
                            $row[] = $deviceInfo['os'] ?? '';
                            $row[] = $deviceInfo['ip_address'] ?? '';
                            $row[] = $deviceInfo['user_agent'] ?? '';
                        }
                        
                        // Track last activity
                        if ($event->tracked_at && (!$lastActivity || $event->tracked_at > $lastActivity)) {
                            $lastActivity = $event->tracked_at;
                        }
                    } else {
                        // Status column
                        $row[] = 'No';
                        // Time When Opened column (empty if not opened)
                        $row[] = '';
                        
                        if ($includeDeviceInfo) {
                            // Empty device info columns
                            $row[] = '';
                            $row[] = '';
                            $row[] = '';
                            $row[] = '';
                            $row[] = '';
                        }
                    }
                }
                
                // Add last activity column
                $row[] = $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : '';
                
                // Write the complete row
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

