<?php

namespace App\Listeners;

use App\Services\TrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Sendportal\Base\Events\MessageDispatchEvent;
use Sendportal\Base\Models\Message;

class TrackEmailSent implements ShouldQueue
{
    use InteractsWithQueue;

    protected $trackingService;

    /**
     * Create the event listener.
     */
    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle the event.
     * Note: email_sent tracking is now handled automatically in ExtendedMarkAsSent
     * after the message is successfully sent. This listener is kept for backward compatibility
     * but the primary tracking happens in MarkAsSent service.
     */
    public function handle(MessageDispatchEvent $event): void
    {
        // Email sent tracking is now handled in ExtendedMarkAsSent service
        // which is called after the message is successfully dispatched
        // This ensures tracking happens only when email is actually sent
    }
}
