<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        // Log the tracking request
        \Illuminate\Support\Facades\Log::info('Tracking pixel accessed', [
            'campaign_id' => $campaignId,
            'subscriber_hash' => $subscriberHash,
            'task_number' => $taskNumber,
            'task_type' => $taskType,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
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
            // Still return image even if tracking fails (to avoid broken images)
            return $this->returnTrackingPixel();
        }

        \Illuminate\Support\Facades\Log::info('Tracking successful', [
            'tracking_id' => $tracking->id,
            'task_type' => $tracking->task_type,
            'campaign_id' => $tracking->campaign_id,
        ]);

        // Return 1x1 transparent GIF for email tracking pixels
        return $this->returnTrackingPixel();
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

        $tracking = $this->trackingService->track(
            $campaignId,
            $subscriberHash,
            $taskType,
            $status,
            $metadata
        );

        if (!$tracking) {
            // Still return image even if tracking fails (to avoid broken images)
            return $this->returnTrackingPixel();
        }

        // Return 1x1 transparent GIF for email tracking pixels
        return $this->returnTrackingPixel();
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
     * Return a 1x1 transparent GIF image for tracking pixels
     * 
     * @return \Illuminate\Http\Response
     */
    protected function returnTrackingPixel(): \Illuminate\Http\Response
    {
        // 1x1 transparent GIF (43 bytes)
        // This is the smallest possible GIF: 1x1 transparent pixel
        $pixel = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x04\x01\x00\x3B";
        
        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Content-Length', (string) strlen($pixel))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
            ->header('Cross-Origin-Resource-Policy', 'cross-origin')
            ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
    }
}
