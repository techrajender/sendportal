@extends('sendportal::layouts.app')

@section('title', __('Campaigns'))

@section('heading')
    {{ __('Campaigns') }}
@endsection

@section('content')

    @include('sendportal::campaigns.partials.nav')

    @component('sendportal::layouts.partials.actions')
        @slot('right')
            <button type="button" 
                    class="btn btn-light btn-md btn-flat mr-2" 
                    id="reload-campaigns-btn"
                    title="{{ __('Reload Campaigns') }}">
                <i class="fas fa-sync-alt" id="reload-icon"></i>
            </button>
            <button type="button" 
                    class="btn btn-light btn-md btn-flat mr-2 {{ (!empty(request()->get('search')) || !empty(request()->get('status_id'))) ? 'active' : '' }}" 
                    id="toggle-filters-btn"
                    onclick="toggleFilters()"
                    title="{{ __('Show/Hide Filters') }}">
                <i class="fas fa-filter" id="filter-icon"></i>
            </button>
            <a class="btn btn-primary btn-md btn-flat" href="{{ route('sendportal.campaigns.create') }}">
                <i class="fa fa-plus mr-1"></i> {{ __('New Campaign') }}
            </a>
        @endslot
    @endcomponent

    <!-- Filters Section -->
    <div class="card mb-3" id="filters-section" style="display: {{ (!empty(request()->get('search')) || !empty(request()->get('status_id'))) ? 'block' : 'none' }};">
        <div class="card-body">
            <form method="GET" action="{{ request()->url() }}" id="filter-form" class="row align-items-end">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label for="search">{{ __('Search by Name') }}</label>
                        <input type="text" 
                               class="form-control" 
                               id="search" 
                               name="search" 
                               value="{{ request()->get('search') }}" 
                               placeholder="{{ __('Enter campaign name...') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <label for="status_id">{{ __('Filter by Status') }}</label>
                        <select class="form-control" id="status_id" name="status_id">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="1" {{ request()->get('status_id') == '1' ? 'selected' : '' }}>Draft</option>
                            <option value="2" {{ request()->get('status_id') == '2' ? 'selected' : '' }}>Queued</option>
                            <option value="3" {{ request()->get('status_id') == '3' ? 'selected' : '' }}>Sending</option>
                            <option value="4" {{ request()->get('status_id') == '4' ? 'selected' : '' }}>Sent</option>
                            <option value="5" {{ request()->get('status_id') == '5' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> {{ __('Filter') }}
                    </button>
                    @if (!empty(request()->get('search')) || !empty(request()->get('status_id')))
                        <a href="{{ request()->url() }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-table table-responsive">
            <table class="table" style="font-size: 0.875rem;">
                <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    @if (request()->routeIs('sendportal.campaigns.sent'))
                        <th>{{ __('Sent') }}</th>
                        <th>{{ __('Opened') }}</th>
                        <th>{{ __('Clicked') }}</th>
                    @endif
                    <th>{{ __('Created') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($campaigns as $campaign)
                    @php
                        // Calculate recipient count for draft campaigns
                        $recipientCount = 0;
                        if ($campaign->draft) {
                            if ($campaign->send_to_all) {
                                // Count all active subscribers in workspace
                                $subscriberIds = \Sendportal\Base\Models\Subscriber::where('workspace_id', $campaign->workspace_id)
                                    ->whereNull('unsubscribed_at')
                                    ->pluck('id')
                                    ->toArray();
                                $recipientCount = count($subscriberIds);
                            } else {
                                // Count unique subscribers across all selected tags (avoid double counting)
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
                            $excludedCampaignIds = \App\Models\CampaignExclusion::where('campaign_id', $campaign->id)
                                ->pluck('excluded_campaign_id')
                                ->toArray();
                            
                            if (!empty($excludedCampaignIds) && $recipientCount > 0) {
                                $excludedSubscriberIds = \App\Models\CampaignSubscriberTracking::whereIn('campaign_id', $excludedCampaignIds)
                                    ->where('task_type', 'email_sent')
                                    ->where('status', 'opened')
                                    ->distinct()
                                    ->pluck('subscriber_id')
                                    ->toArray();
                                
                                // Calculate how many of our recipients are excluded
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
                        }
                    @endphp
                    <tr>
                        <td style="font-size: 0.875rem;">
                            @if ($campaign->draft)
                                <a href="{{ route('sendportal.campaigns.edit', $campaign->id) }}">{{ $campaign->name }}</a>
                            @elseif($campaign->sent)
                                <a href="{{ route('sendportal.campaigns.reports.index', $campaign->id) }}">{{ $campaign->name }}</a>
                            @else
                                <a href="{{ route('sendportal.campaigns.status', $campaign->id) }}">{{ $campaign->name }}</a>
                            @endif
                        </td>
                        @if (request()->routeIs('sendportal.campaigns.sent'))
                            <td style="font-size: 0.875rem;">{{ $campaignStats[$campaign->id]['counts']['sent'] }}</td>
                            <td style="font-size: 0.875rem;">{{ number_format($campaignStats[$campaign->id]['ratios']['open'] * 100, 1) . '%' }}</td>
                            <td style="font-size: 0.875rem;">
                                {{ number_format($campaignStats[$campaign->id]['ratios']['click'] * 100, 1) . '%' }}
                            </td>
                        @endif
                        <td style="font-size: 0.875rem;">
                            <div class="mb-1"><span class="text-muted" style="font-size: 0.75rem;">{{ $campaign->created_at->format('M d, Y') }}</span></div>
                            <div class="text-muted" style="font-size: 0.65rem;">{{ $campaign->created_at->format('h:i A') }}</div>
                        </td>
                        <td style="font-size: 0.875rem;">
                            @include('sendportal::campaigns.partials.status')
                            @if ($campaign->draft)
                                <div class="mt-1">
                                    @if ($recipientCount > 0)
                                        <small class="text-muted">
                                            <i class="fas fa-users"></i> {{ $recipientCount }} {{ __('recipient') }}{{ $recipientCount !== 1 ? 's' : '' }}
                                        </small>
                                    @else
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i> {{ __('No recipients') }}
                                        </small>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm btn-wide" type="button" id="dropdownMenuButton"
                                        data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    @if ($campaign->draft)
                                        <a href="{{ route('sendportal.campaigns.edit', $campaign->id) }}"
                                           class="dropdown-item">
                                            {{ __('Edit') }}
                                        </a>
                                    @else
                                        <a href="{{ route('sendportal.campaigns.reports.index', $campaign->id) }}"
                                           class="dropdown-item">
                                            {{ __('View Report') }}
                                        </a>
                                    @endif


                                    @if(!$campaign->queued)
                                        <a href="{{ route('sendportal.campaigns.duplicate', $campaign->id) }}"
                                           class="dropdown-item">
                                            {{ __('Duplicate') }}
                                        </a>
                                    @endif

                                    @php
                                        // Check if campaign should show reprocess option
                                        $showReprocess = false;
                                        $isStuckSending = false;
                                        
                                        if ($campaign->queued) {
                                            $showReprocess = true;
                                        } elseif ($campaign->sending) {
                                            // Check if sending campaign is stuck
                                            // Get message counts from campaignStats or query directly
                                            $totalMessages = 0;
                                            $sentMessages = 0;
                                            
                                            if (isset($campaignStats[$campaign->id])) {
                                                $totalMessages = $campaignStats[$campaign->id]['counts']['total'] ?? 0;
                                                $sentMessages = $campaignStats[$campaign->id]['counts']['sent'] ?? 0;
                                            } else {
                                                // Query directly if not in stats
                                                $totalMessages = \Sendportal\Base\Models\Message::where('source_id', $campaign->id)
                                                    ->where('source_type', \Sendportal\Base\Models\Campaign::class)
                                                    ->count();
                                                
                                                $sentMessages = \Sendportal\Base\Models\Message::where('source_id', $campaign->id)
                                                    ->where('source_type', \Sendportal\Base\Models\Campaign::class)
                                                    ->whereNotNull('sent_at')
                                                    ->count();
                                            }
                                            
                                            // Consider stuck if:
                                            // 1. Has messages but none sent (e.g., "0 out of 1 emails sent")
                                            // 2. Has messages but not all sent AND updated more than 5 minutes ago
                                            if ($totalMessages > 0 && $sentMessages === 0) {
                                                $isStuckSending = true;
                                                $showReprocess = true;
                                            } elseif ($totalMessages > 0 && $sentMessages < $totalMessages) {
                                                // Check if updated more than 5 minutes ago
                                                $updatedAt = $campaign->updated_at;
                                                $minutesSinceUpdate = $updatedAt ? now()->diffInMinutes($updatedAt) : 0;
                                                
                                                if ($minutesSinceUpdate > 5) {
                                                    $isStuckSending = true;
                                                    $showReprocess = true;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if($showReprocess)
                                        <div class="dropdown-divider"></div>
                                        <a href="#" 
                                           class="dropdown-item reprocess-campaign-btn" 
                                           data-campaign-id="{{ $campaign->id }}"
                                           data-campaign-name="{{ $campaign->name }}">
                                            <i class="fas fa-redo"></i> {{ __('Reprocess') }}
                                            @if($isStuckSending)
                                                <small class="text-muted d-block">({{ __('Stuck in sending') }})</small>
                                            @endif
                                        </a>
                                    @endif

                                    @if($campaign->canBeCancelled())
                                        <div class="dropdown-divider"></div>
                                        <a href="{{ route('sendportal.campaigns.confirm-cancel', $campaign->id) }}"
                                           class="dropdown-item">
                                            {{ __('Cancel') }}
                                        </a>
                                    @endif

                                    @if ($campaign->draft)
                                        <div class="dropdown-divider"></div>
                                        <a href="{{ route('sendportal.campaigns.destroy.confirm', $campaign->id) }}"
                                           class="dropdown-item">
                                            {{ __('Delete') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="100%">
                            <p class="empty-table-text">
                                @if (request()->routeIs('sendportal.campaigns.index'))
                                    {{ __('You do not have any draft campaigns.') }}
                                @else
                                    {{ __('You do not have any sent campaigns.') }}
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('sendportal::layouts.partials.pagination', ['records' => $campaigns])

    <!-- Reprocess Confirmation Modal -->
    <div class="modal fade" id="reprocessConfirmModal" tabindex="-1" role="dialog" aria-labelledby="reprocessConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title" id="reprocessConfirmModalLabel">
                        <i class="fas fa-redo mr-2"></i>{{ __('Confirm Reprocess Campaign') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>{{ __('Are you sure?') }}</strong>
                        <p class="mb-0 mt-2" id="reprocess-campaign-message"></p>
                    </div>
                    <p class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('This will attempt to restart the campaign dispatch process.') }}
                    </p>
                    <input type="hidden" id="reprocess-campaign-id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>{{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="confirm-reprocess-action-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="fas fa-check mr-2"></i>{{ __('Yes, Reprocess') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Message Modal -->
    <div class="modal fade" id="errorMessageModal" tabindex="-1" role="dialog" aria-labelledby="errorMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                    <h5 class="modal-title" id="errorMessageModalLabel">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ __('Error') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i>
                        <strong id="error-modal-title">{{ __('An error occurred') }}</strong>
                        <p class="mb-0 mt-2" id="error-modal-message"></p>
                    </div>
                    <div id="error-modal-suggestion" style="display: none;">
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb mr-2"></i>
                            <strong>{{ __('Suggestion') }}</strong>
                            <p class="mb-0 mt-2" id="error-modal-suggestion-text"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <i class="fas fa-check mr-2"></i>{{ __('OK') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message Modal -->
    <div class="modal fade" id="successMessageModal" tabindex="-1" role="dialog" aria-labelledby="successMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <h5 class="modal-title" id="successMessageModalLabel">
                        <i class="fas fa-check-circle mr-2"></i>{{ __('Success') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p class="mb-0" id="success-modal-message"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none;">
                        <i class="fas fa-check mr-2"></i>{{ __('OK') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

@push('js')
<script>
function toggleFilters() {
    const filtersSection = document.getElementById('filters-section');
    const toggleBtn = document.getElementById('toggle-filters-btn');
    
    if (filtersSection.style.display === 'none') {
        filtersSection.style.display = 'block';
        toggleBtn.classList.add('active');
    } else {
        filtersSection.style.display = 'none';
        toggleBtn.classList.remove('active');
    }
}

// Handle campaign reprocess with modal dialog
document.addEventListener('DOMContentLoaded', function() {
    const reprocessButtons = document.querySelectorAll('.reprocess-campaign-btn');
    const modal = $('#reprocessConfirmModal');
    const confirmBtn = $('#confirm-reprocess-action-btn');
    const campaignIdInput = $('#reprocess-campaign-id');
    const campaignMessage = $('#reprocess-campaign-message');
    let currentButton = null;
    let originalButtonHtml = '';
    
    // Open modal when reprocess button is clicked
    reprocessButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const campaignId = this.getAttribute('data-campaign-id');
            const campaignName = this.getAttribute('data-campaign-name');
            
            // Store button reference
            currentButton = this;
            originalButtonHtml = this.innerHTML;
            
            // Set campaign info in modal
            campaignIdInput.val(campaignId);
            campaignMessage.text('Are you sure you want to reprocess the campaign "' + campaignName + '"?');
            
            // Show modal
            modal.modal('show');
        });
    });
    
    // Handle confirm button click
    confirmBtn.on('click', function() {
        const campaignId = campaignIdInput.val();
        
        if (!campaignId) {
            return;
        }
        
        // Disable button and show loading
        confirmBtn.prop('disabled', true);
        confirmBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>{{ __('Processing...') }}');
        
        // Update original button state
        if (currentButton) {
            currentButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Processing...') }}';
            currentButton.style.pointerEvents = 'none';
        }
        
        // Close modal
        modal.modal('hide');
        
        // Make AJAX request
        fetch('{{ route("sendportal.campaigns.reprocess", ":id") }}'.replace(':id', campaignId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                showSuccessModal('Campaign reprocessing initiated successfully! The campaign will be processed shortly.');
                
                // Reload page after a short delay to show updated status
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                // Show error modal with detailed message
                let errorMsg = data.error || 'Failed to reprocess campaign';
                let suggestion = '';
                
                if (data.recipient_count === 0) {
                    suggestion = data.suggestion || 'Please update campaign settings or change campaign status.';
                }
                
                showErrorModal(errorMsg, suggestion);
                
                // Restore button state
                if (currentButton) {
                    currentButton.innerHTML = originalButtonHtml;
                    currentButton.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('An error occurred while reprocessing the campaign. Please try again.');
            
            // Restore button state
            if (currentButton) {
                currentButton.innerHTML = originalButtonHtml;
                currentButton.style.pointerEvents = 'auto';
            }
        })
        .finally(function() {
            // Reset confirm button
            confirmBtn.prop('disabled', false);
            confirmBtn.html('<i class="fas fa-check mr-2"></i>{{ __('Yes, Reprocess') }}');
        });
    });
    
    // Reset modal when closed
    modal.on('hidden.bs.modal', function() {
        campaignIdInput.val('');
        campaignMessage.text('');
        confirmBtn.prop('disabled', false);
        confirmBtn.html('<i class="fas fa-check mr-2"></i>{{ __('Yes, Reprocess') }}');
        currentButton = null;
        originalButtonHtml = '';
    });
    
    // Function to show error modal
    function showErrorModal(message, suggestion = '') {
        const errorModal = $('#errorMessageModal');
        const errorMessage = $('#error-modal-message');
        const errorSuggestion = $('#error-modal-suggestion');
        const errorSuggestionText = $('#error-modal-suggestion-text');
        
        errorMessage.text(message);
        
        if (suggestion) {
            errorSuggestionText.text(suggestion);
            errorSuggestion.show();
        } else {
            errorSuggestion.hide();
        }
        
        errorModal.modal('show');
    }
    
    // Function to show success modal
    function showSuccessModal(message) {
        const successModal = $('#successMessageModal');
        const successMessage = $('#success-modal-message');
        
        successMessage.text(message);
        successModal.modal('show');
        
        // Auto-close after 2 seconds if user hasn't closed it
        setTimeout(function() {
            if (successModal.is(':visible')) {
                successModal.modal('hide');
            }
        }, 2000);
    }
    
    // Handle campaigns list reload button
    const reloadBtn = document.getElementById('reload-campaigns-btn');
    const reloadIcon = document.getElementById('reload-icon');
    
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading state with spinning icon
            reloadIcon.classList.remove('fa-sync-alt');
            reloadIcon.classList.add('fa-spinner', 'fa-spin');
            reloadBtn.disabled = true;
            reloadBtn.style.pointerEvents = 'none';
            
            // Reload the page after a short delay to show the loading state
            setTimeout(function() {
                window.location.reload();
            }, 300);
        });
    }
});
</script>
@endpush

@endsection
