<?php

declare(strict_types=1);

use App\Http\Middleware\RequireWorkspace;
use Illuminate\Support\Facades\Route;
use Sendportal\Base\Facades\Sendportal;

Route::middleware([
    config('sendportal-host.throttle_middleware'),
    RequireWorkspace::class,
])->group(function () {
    // Auth'd API routes (workspace-level auth!).
    Sendportal::apiRoutes();
});

// Non-auth'd API routes.
Sendportal::publicApiRoutes();

// Simplified tracking endpoint using task numbers (1, 2, 3, etc.)
// GET /api/track/{campaignHash}/{subscriberHash}/{taskNumber}
// Task numbers: 1=email_sent, 2=email_opened, 3=email_clicked, 4=newsletter_opened, 5=landing_page_opened, 6=thank_you_received, 7=asset_downloaded
Route::match(['GET', 'OPTIONS'], 'track/{campaignHash}/{subscriberHash}/{taskNumber}', [App\Http\Controllers\Api\TrackingController::class, 'trackSimple'])
    ->where('taskNumber', '[1-7]')
    ->name('api.track.simple');

// Legacy tracking endpoint (kept for backward compatibility)
Route::match(['GET', 'OPTIONS'], 'track/{campaignId}/{subscriberHash}/{taskType}', [App\Http\Controllers\Api\TrackingController::class, 'track'])
    ->name('api.track');
