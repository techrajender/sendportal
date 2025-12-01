<?php

namespace App\Providers;

use App\Models\CampaignExclusion;
use App\Services\CampaignExclusionService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Sendportal\Base\Facades\Sendportal;

class CampaignViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(CampaignExclusionService $exclusionService): void
    {
        // Add exclude campaigns to reports index view
        View::composer('sendportal::campaigns.reports.index', function ($view) use ($exclusionService) {
            try {
                $viewData = $view->getData();
                $campaign = $viewData['campaign'] ?? null;
                
                if ($campaign) {
                    $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaign->id)
                        ->pluck('excluded_campaign_id')
                        ->toArray();
                    
                    $excludedCampaigns = \Sendportal\Base\Models\Campaign::whereIn('id', $excludedCampaignIds)
                        ->orderBy('name')
                        ->get();
                    
                    $view->with('excludedCampaigns', $excludedCampaigns);
                    $view->with('excludedCampaignsCount', $excludedCampaigns->count());
                } else {
                    $view->with('excludedCampaigns', collect());
                    $view->with('excludedCampaignsCount', 0);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Error loading excluded campaigns for reports', [
                    'error' => $e->getMessage()
                ]);
                $view->with('excludedCampaigns', collect());
                $view->with('excludedCampaignsCount', 0);
            }
        });

        // Add exclude campaigns to preview view
        View::composer('sendportal::campaigns.preview', function ($view) use ($exclusionService) {
            try {
                $viewData = $view->getData();
                $campaign = $viewData['campaign'] ?? null;
                
                \Illuminate\Support\Facades\Log::info('CampaignViewServiceProvider: Loading campaigns', [
                    'has_campaign' => !is_null($campaign),
                    'campaign_id' => $campaign->id ?? null,
                ]);
                
                if ($campaign) {
                    $workspaceId = null;
                    try {
                        $workspaceId = Sendportal::currentWorkspaceId();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Could not get workspace ID from Sendportal facade', [
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    // Use workspace_id from campaign if facade doesn't work
                    if (!$workspaceId && isset($campaign->workspace_id)) {
                        $workspaceId = $campaign->workspace_id;
                    }
                    
                    if ($workspaceId) {
                        $excludeCampaigns = $exclusionService->getAvailableCampaignsForExclusion(
                            $workspaceId,
                            $campaign->id
                        );
                        
                        \Illuminate\Support\Facades\Log::info('CampaignViewServiceProvider: Found campaigns', [
                            'count' => $excludeCampaigns->count(),
                            'workspace_id' => $workspaceId,
                            'campaign_id' => $campaign->id,
                        ]);
                        
                        // Get already excluded campaigns
                        $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaign->id)
                            ->pluck('excluded_campaign_id')
                            ->toArray();
                        
                        $excludedCampaigns = \Sendportal\Base\Models\Campaign::whereIn('id', $excludedCampaignIds)->get();
                        
                        $view->with('excludeCampaigns', $excludeCampaigns);
                        $view->with('excludedCampaigns', $excludedCampaigns);
                        $view->with('excludedCampaignIds', $excludedCampaignIds);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('CampaignViewServiceProvider: No workspace ID available');
                        $view->with('excludeCampaigns', collect());
                        $view->with('excludedCampaigns', collect());
                        $view->with('excludedCampaignIds', []);
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('CampaignViewServiceProvider: No campaign found in view data');
                    $view->with('excludeCampaigns', collect());
                    $view->with('excludedCampaigns', collect());
                    $view->with('excludedCampaignIds', []);
                }
            } catch (\Exception $e) {
                // If there's an error, pass empty collection
                \Illuminate\Support\Facades\Log::error('Error loading exclude campaigns', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $view->with('excludeCampaigns', collect());
                $view->with('excludedCampaigns', collect());
                $view->with('excludedCampaignIds', []);
            }
        });

        // Add recipient count to campaign status view
        View::composer('sendportal::campaigns.status', function ($view) use ($exclusionService) {
            try {
                $viewData = $view->getData();
                $campaign = $viewData['campaign'] ?? null;
                
                if ($campaign) {
                    $recipientCount = 0;

                    if ($campaign->send_to_all) {
                        // Count all active subscribers in workspace
                        $subscriberIds = \Sendportal\Base\Models\Subscriber::where('workspace_id', $campaign->workspace_id)
                            ->whereNull('unsubscribed_at')
                            ->pluck('id')
                            ->toArray();
                        $recipientCount = count($subscriberIds);
                    } else {
                        // Count unique subscribers across all selected tags
                        $subscriberIds = [];
                        foreach ($campaign->tags as $tag) {
                            $tagSubscriberIds = $tag->subscribers()
                                ->whereNull('unsubscribed_at')
                                ->pluck('sendportal_subscribers.id')
                                ->toArray();
                            $subscriberIds = array_merge($subscriberIds, $tagSubscriberIds);
                        }
                        $recipientCount = count(array_unique($subscriberIds));
                    }

                    // Apply exclusion filter if exclusions exist
                    $excludedCampaignIds = CampaignExclusion::where('campaign_id', $campaign->id)
                        ->pluck('excluded_campaign_id')
                        ->toArray();

                    if (!empty($excludedCampaignIds) && $recipientCount > 0) {
                        $excludedSubscriberIds = $exclusionService->getExcludedSubscriberIds($excludedCampaignIds);

                        if ($campaign->send_to_all) {
                            $allSubscriberIds = \Sendportal\Base\Models\Subscriber::where('workspace_id', $campaign->workspace_id)
                                ->whereNull('unsubscribed_at')
                                ->pluck('id')
                                ->toArray();
                            $excludedCount = count(array_intersect($allSubscriberIds, $excludedSubscriberIds));
                        } else {
                            $subscriberIds = [];
                            foreach ($campaign->tags as $tag) {
                                $tagSubscriberIds = $tag->subscribers()
                                    ->whereNull('unsubscribed_at')
                                    ->pluck('sendportal_subscribers.id')
                                    ->toArray();
                                $subscriberIds = array_merge($subscriberIds, $tagSubscriberIds);
                            }
                            $uniqueSubscriberIds = array_unique($subscriberIds);
                            $excludedCount = count(array_intersect($uniqueSubscriberIds, $excludedSubscriberIds));
                        }

                        $recipientCount = max(0, $recipientCount - $excludedCount);
                    }

                    $view->with('recipientCount', $recipientCount);
                } else {
                    $view->with('recipientCount', 0);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Error calculating recipient count for status view', [
                    'error' => $e->getMessage()
                ]);
                $view->with('recipientCount', 0);
            }
        });
    }
}

