<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\DeviceHelper;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Task number to task type mapping
     */
    const TASK_MAP = [
        1 => 'email_sent',
        2 => 'email_opened',
        3 => 'email_clicked',
        4 => 'newsletter_opened',
        5 => 'landing_page_opened',
        6 => 'thank_you_received',
        7 => 'asset_downloaded',
    ];

    protected $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Simplified tracking endpoint using task numbers
     * GET /api/track/{campaignHash}/{subscriberHash}/{taskNumber}
     * 
     * Task numbers:
     * 1 = email_sent
     * 2 = email_opened
     * 3 = email_clicked
     * 4 = newsletter_opened
     * 5 = landing_page_opened
     * 6 = thank_you_received
     * 7 = asset_downloaded
     *
     * @param string $campaignHash Campaign ID (as hash)
     * @param string $subscriberHash Subscriber hash
     * @param int $taskNumber Task number (1-7)
     * @param Request $request
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function trackSimple(string $campaignHash, string $subscriberHash, int $taskNumber, Request $request)
    {
        // Handle CORS preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $this->options();
        }

        // Map task number to task type
        if (!isset(self::TASK_MAP[$taskNumber])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid task number. Must be between 1 and 7.',
                'valid_tasks' => [
                    1 => 'email_sent',
                    2 => 'email_opened',
                    3 => 'email_clicked',
                    4 => 'newsletter_opened',
                    5 => 'landing_page_opened',
                    6 => 'thank_you_received',
                    7 => 'asset_downloaded',
                ]
            ], 400);
        }

        $taskType = self::TASK_MAP[$taskNumber];
        $status = $request->get('status', 'opened');
        $metadata = $request->get('metadata');
        
        // Parse metadata if it's a JSON string
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }
        
        // For email_clicked events, extract redirect URL and add to metadata
        if ($taskType === 'email_clicked' && $request->has('redirect')) {
            if (!is_array($metadata)) {
                $metadata = [];
            }
            // Use redirect as the URL if not already in metadata
            if (!isset($metadata['url']) && !isset($metadata['redirect'])) {
                $metadata['url'] = $request->get('redirect');
            }
        }

        // Convert campaign hash (which is actually campaign ID) to integer
        $campaignId = is_numeric($campaignHash) ? (int) $campaignHash : null;
        
        if (!$campaignId) {
            \Illuminate\Support\Facades\Log::warning('Invalid campaign hash', [
                'campaign_hash' => $campaignHash,
                'subscriber_hash' => $subscriberHash,
                'task_number' => $taskNumber,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid campaign hash format.'
            ], 400);
        }

        // Capture device information
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();
        $deviceType = DeviceHelper::detectDeviceType($userAgent);
        $browser = DeviceHelper::detectBrowser($userAgent);
        $os = DeviceHelper::detectOS($userAgent);

        // Add device information to metadata
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $metadata['device_info'] = [
            'browser' => $browser,
            'ip_address' => $ipAddress,
            'device_type' => $deviceType,
            'os' => $os,
            'user_agent' => $userAgent,
        ];

        // Log the tracking request
        \Illuminate\Support\Facades\Log::info('Tracking pixel accessed', [
            'campaign_id' => $campaignId,
            'subscriber_hash' => $subscriberHash,
            'task_number' => $taskNumber,
            'task_type' => $taskType,
            'user_agent' => $userAgent,
            'ip' => $ipAddress,
            'device_type' => $deviceType,
            'browser' => $browser,
        ]);

        $tracking = $this->trackingService->track(
            $campaignId,
            $subscriberHash,
            $taskType,
            $status,
            $metadata
        );

        if (!$tracking) {
            \Illuminate\Support\Facades\Log::warning('Tracking failed', [
                'campaign_id' => $campaignId,
                'subscriber_hash' => $subscriberHash,
                'task_type' => $taskType,
            ]);
            // Return JSON response even if tracking fails
            return $this->returnJsonResponse(false, 'Tracking failed');
        }

        \Illuminate\Support\Facades\Log::info('Tracking successful', [
            'tracking_id' => $tracking->id,
            'task_type' => $tracking->task_type,
            'campaign_id' => $tracking->campaign_id,
        ]);

        // For click tracking with redirect parameter, redirect to the destination URL
        if ($taskType === 'email_clicked' && $request->has('redirect')) {
            $redirectUrl = $request->get('redirect');
            if ($redirectUrl && filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
                return redirect($redirectUrl);
            }
        }

        // Return JSON response (works better with CORS and ORB)
        return $this->returnJsonResponse(true, 'tracked');
    }

    /**
     * Generic tracking endpoint (legacy - kept for backward compatibility)
     * GET /api/track/{campaignId}/{subscriberHash}/{taskType}
     *
     * @param int $campaignId
     * @param string $subscriberHash
     * @param string $taskType
     * @param Request $request
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function track(int $campaignId, string $subscriberHash, string $taskType, Request $request)
    {
        // Handle CORS preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $this->options();
        }

        $status = $request->get('status', 'opened');
        $metadata = $request->get('metadata');
        
        // Parse metadata if it's a JSON string
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        // Capture device information
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();
        $deviceType = DeviceHelper::detectDeviceType($userAgent);
        $browser = DeviceHelper::detectBrowser($userAgent);
        $os = DeviceHelper::detectOS($userAgent);

        // Add device information to metadata
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $metadata['device_info'] = [
            'browser' => $browser,
            'ip_address' => $ipAddress,
            'device_type' => $deviceType,
            'os' => $os,
            'user_agent' => $userAgent,
        ];

        $tracking = $this->trackingService->track(
            $campaignId,
            $subscriberHash,
            $taskType,
            $status,
            $metadata
        );

        if (!$tracking) {
            // Return JSON response even if tracking fails
            return $this->returnJsonResponse(false, 'Tracking failed');
        }

        // Return JSON response (works better with CORS and ORB)
        return $this->returnJsonResponse(true, 'tracked');
    }

    /**
     * Handle CORS preflight requests
     *
     * @return \Illuminate\Http\Response
     */
    public function options()
    {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
            ->header('Access-Control-Max-Age', '86400')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin');
    }

    /**
     * Return JSON response for tracking
     * 
     * This approach works better with CORS and ORB (Opaque Response Blocking)
     * Works in ngrok, Cloudflare, and with any JS fetch / image / pixel call
     * 
     * @param bool $success
     * @param string $message
     * @return JsonResponse
     */
    protected function returnJsonResponse(bool $success, string $message = 'tracked'): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'msg' => $message
        ], 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin')
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
