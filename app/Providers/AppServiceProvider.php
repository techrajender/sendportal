<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Setup;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RuntimeException;
use Sendportal\Base\Facades\Sendportal;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override MergeContentService to add custom merge tags (subscriber_hash, message_hash)
        $this->app->bind(
            \Sendportal\Base\Services\Content\MergeContentService::class,
            \App\Services\Content\ExtendedMergeContentService::class
        );
        
        // Override MergeSubjectService to add custom merge tags in email subjects
        $this->app->bind(
            \Sendportal\Base\Services\Content\MergeSubjectService::class,
            \App\Services\Content\ExtendedMergeSubjectService::class
        );
        
        // Override MarkAsSent to automatically track email_sent events
        $this->app->bind(
            \Sendportal\Base\Services\Messages\MarkAsSent::class,
            \App\Services\Messages\ExtendedMarkAsSent::class
        );
        
        // Override CampaignTenantRepository to fix average time calculations
        $this->app->bind(
            \Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface::class,
            \App\Repositories\Campaigns\ExtendedCampaignTenantRepository::class
        );
        
        // Override CampaignDispatchService to handle campaigns with 0 messages
        $this->app->bind(
            \Sendportal\Base\Services\Campaigns\CampaignDispatchService::class,
            \App\Services\Campaigns\ExtendedCampaignDispatchService::class
        );
        
        // Override CampaignsController to use pagination of 10
        $this->app->bind(
            \Sendportal\Base\Http\Controllers\Campaigns\CampaignsController::class,
            \App\Http\Controllers\Campaigns\ExtendedCampaignsController::class
        );
        
        // Override TagsController to add subscriber selection
        $this->app->bind(
            \Sendportal\Base\Http\Controllers\Tags\TagsController::class,
            \App\Http\Controllers\Tags\ExtendedTagsController::class
        );
        
        // Override DashboardController to add pagination
        $this->app->bind(
            \Sendportal\Base\Http\Controllers\DashboardController::class,
            \App\Http\Controllers\ExtendedDashboardController::class
        );
        
        // Note: TemplatesController is not overridden as it may not exist in base package
        // Clone functionality is added via a separate route
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        // Register Message observer to filter excluded subscribers
        \Sendportal\Base\Models\Message::observe(\App\Observers\MessageObserver::class);

        // Register Campaign observer to automatically queue dispatch jobs when status changes to queued
        \Sendportal\Base\Models\Campaign::observe(\App\Observers\CampaignObserver::class);

        Sendportal::setCurrentWorkspaceIdResolver(
            static function () {
                /** @var User $user */
                $user = auth()->user();
                $request = request();
                $workspaceId = null;

                if ($user && $user->currentWorkspaceId()) {
                    $workspaceId = $user->currentWorkspaceId();
                } elseif ($request && (($apiToken = $request->bearerToken()) || ($apiToken = $request->get('api_token')))) {
                    $workspaceId = ApiToken::resolveWorkspaceId($apiToken);
                }

                if (! $workspaceId) {
                    throw new RuntimeException('Current Workspace ID Resolver must not return a null value.');
                }

                return $workspaceId;
            }
        );

        Sendportal::setSidebarHtmlContentResolver(
            static function () {
                return view('layouts.sidebar.manageUsersMenuItem')->render();
            }
        );

        Sendportal::setHeaderHtmlContentResolver(
            static function () {
                return view('layouts.header.userManagementHeader')->render();
            }
        );

        Livewire::component('setup', Setup::class);
    }
}
