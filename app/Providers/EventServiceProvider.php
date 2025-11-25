<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\FilterExcludedSubscribers;
use App\Listeners\TrackEmailSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Sendportal\Base\Events\MessageDispatchEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        MessageDispatchEvent::class => [
            FilterExcludedSubscribers::class, // Run first to filter excluded subscribers
            TrackEmailSent::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
