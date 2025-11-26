@extends('sendportal::layouts.app')

@section('title', __('Campaign Status'))

@section('heading')
    {{ __('Campaign Status') }}
@stop

@push('css')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

<div class="card">
    <div class="card-header card-header-accent">
        <div class="card-header-inner">
            {{ __('Your campaign is currently') }} <strong>{{ strtolower($campaign->status->name) }}</strong>
        </div>
    </div>
    <div class="card-body">
        @if ($campaign->queued)
            @if (isset($recipientCount) && $recipientCount === 0)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>{{ __('Your campaign is currently queued') }}</strong>
                    <p class="mb-0 mt-2">
                        {{ __('However, this campaign has 0 recipients and cannot be processed.') }}
                    </p>
                    <p class="mb-0 mt-2">
                        {{ __('Please update the campaign settings (tags or send to all) or change the campaign status. No processing is needed.') }}
                    </p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#updateCampaignStatusModal">
                            <i class="fas fa-edit"></i> {{ __('Update Campaign Status') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="campaign-queued-info">
                    <!-- Campaign Status Header -->
                    <div class="text-center mb-4">
                        <div class="mb-3" style="font-size: 3.5rem; color: #28a745;">
                            <i class="fas fa-hourglass-half fa-spin"></i>
                        </div>
                        <h4 class="mb-2" style="color: #28a745; font-weight: 600;">{{ __('Your campaign is queued') }}</h4>
                        <p class="text-muted mb-0" style="font-size: 1rem;">{{ __('Your campaign will be sent out soon.') }}</p>
                    </div>

                    <!-- Campaign Details Card -->
                    <div class="card shadow-sm mb-4" style="border: 1px solid #e9ecef; border-radius: 8px;">
                        <div class="card-header" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #dee2e6;">
                            <h5 class="mb-0" style="font-weight: 600; color: #495057;">
                                <i class="fas fa-info-circle mr-2 text-primary"></i>{{ __('Campaign Details') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fas fa-envelope text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1" style="font-weight: 600; color: #495057; font-size: 0.875rem;">{{ __('Campaign Name') }}</h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.9375rem;">{{ $campaign->name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fas fa-paper-plane text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1" style="font-weight: 600; color: #495057; font-size: 0.875rem;">{{ __('Subject') }}</h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.9375rem;">{{ $campaign->subject ?? __('N/A') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fas fa-users text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1" style="font-weight: 600; color: #495057; font-size: 0.875rem;">{{ __('Recipients') }}</h6>
                                            <p class="mb-0" style="font-size: 1.125rem; font-weight: 600; color: #28a745;">
                                                {{ isset($recipientCount) ? number_format($recipientCount) : '0' }}
                                                <small class="text-muted" style="font-size: 0.875rem; font-weight: normal;">{{ __('subscribers') }}</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="fas fa-tags text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1" style="font-weight: 600; color: #495057; font-size: 0.875rem;">{{ __('Targeting') }}</h6>
                                            @if ($campaign->send_to_all)
                                                <p class="mb-0 text-muted" style="font-size: 0.9375rem;">
                                                    <i class="fas fa-check-circle text-success mr-1"></i>{{ __('Send to all subscribers') }}
                                                </p>
                                            @else
                                                <p class="mb-0 text-muted" style="font-size: 0.9375rem;">
                                                    @if ($campaign->tags->count() > 0)
                                                        <i class="fas fa-tag text-primary mr-1"></i>
                                                        {{ $campaign->tags->pluck('name')->join(', ') }}
                                                    @else
                                                        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>{{ __('No tags selected') }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Processing State Card -->
                    <div class="card shadow-sm" style="border: 1px solid #e9ecef; border-radius: 8px;">
                        <div class="card-header" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-bottom: 1px solid #dee2e6;">
                            <h5 class="mb-0" style="font-weight: 600; color: #495057;">
                                <i class="fas fa-cog fa-spin mr-2 text-info"></i>{{ __('Processing Status') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="mr-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-clock text-white" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0" style="font-weight: 600; color: #495057;">{{ __('Waiting in Queue') }}</h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.875rem;">
                                                {{ __('Campaign is queued and will begin processing shortly') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="ml-5">
                                        <div class="progress" style="height: 8px; background-color: #e9ecef; border-radius: 4px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                                 role="progressbar" 
                                                 style="width: 100%"
                                                 aria-valuenow="0" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <p class="text-muted mt-2 mb-0" style="font-size: 0.8125rem;">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            {{ __('Next step: Creating messages and queuing for delivery') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="p-3" style="background: #f8f9fa; border-radius: 8px;">
                                        <div style="font-size: 2rem; font-weight: 700; color: #28a745; line-height: 1;">
                                            {{ isset($recipientCount) ? number_format($recipientCount) : '0' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.875rem; margin-top: 0.25rem;">
                                            {{ __('Emails to send') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @elseif ($campaign->sending)
            <div class="text-center py-5">
                <div class="mb-4" style="font-size: 4rem; color: #17a2b8;">
                    <i class="fas fa-paper-plane fa-spin"></i>
                </div>
                <h4 class="mb-2" style="color: #17a2b8; font-weight: 600;">{{ __('Your campaign is sending') }}</h4>
                @php
                    $totalMessages = $campaignStats[$campaign->id]['counts']['total'] ?? 0;
                    $sentMessages = $campaignStats[$campaign->id]['counts']['sent'] ?? 0;
                @endphp
                @if ($totalMessages > 0)
                    <p class="text-muted mb-0" style="font-size: 1rem;">
                        {{ $sentMessages }} {{ __('out of') }} {{ $totalMessages }} {{ __('emails sent') }}
                    </p>
                    <div class="progress mt-3" style="height: 25px; max-width: 400px; margin: 0 auto;">
                        @php
                            $percentage = $totalMessages > 0 ? round(($sentMessages / $totalMessages) * 100) : 0;
                        @endphp
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                             role="progressbar" 
                             style="width: {{ $percentage }}%"
                             aria-valuenow="{{ $sentMessages }}" 
                             aria-valuemin="0" 
                             aria-valuemax="{{ $totalMessages }}">
                            {{ $percentage }}%
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0" style="font-size: 1rem;">{{ __('Sending emails...') }}</p>
                @endif
            </div>
        @elseif ($campaign->cancelled)
            Your campaign was cancelled.
        @else
            @php
                $totalMessages = $campaignStats[$campaign->id]['counts']['total'] ?? 0;
                $sentMessages = $campaignStats[$campaign->id]['counts']['sent'] ?? 0;
            @endphp
            
            @if ($totalMessages == 0)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>{{ __('No emails to send') }}</strong>
                    <p class="mb-0 mt-2">
                        {{ __('There are no subscribers to send emails to. This may be because:') }}
                    </p>
                    <ul class="mb-0 mt-2">
                        <li>{{ __('All subscribers have been excluded based on your campaign exclusion settings') }}</li>
                        <li>{{ __('No subscribers match the selected tags') }}</li>
                        <li>{{ __('All subscribers are unsubscribed') }}</li>
                    </ul>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#updateCampaignStatusModal">
                            <i class="fas fa-edit"></i> {{ __('Update Campaign Status') }}
                        </button>
                    </div>
                </div>
            @else
                <i class="fas fa-cog fa-spin"></i>
                {{ $sentMessages }} out of {{ $totalMessages }} messages sent.
            @endif
        @endif
    </div>
</div>

<!-- Update Campaign Status Modal -->
<div class="modal fade" id="updateCampaignStatusModal" tabindex="-1" aria-labelledby="updateCampaignStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateCampaignStatusModalLabel">{{ __('Update Campaign Status') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>{{ __('No emails to send') }}</strong>
                    <p class="mb-0 mt-2">{{ __('There are no emails to send for this campaign. Please select a new status for the campaign.') }}</p>
                </div>
                
                <div class="form-group">
                    <label for="campaign-status-select">{{ __('Select Campaign Status') }}</label>
                    <select class="form-control" id="campaign-status-select">
                        <option value="1" {{ $campaign->status_id == 1 ? 'selected' : '' }}>Draft</option>
                        <option value="5" {{ $campaign->status_id == 5 ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                
                <div id="status-update-message" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="update-status-btn" data-campaign-id="{{ $campaign->id }}" data-csrf-token="{{ csrf_token() }}">
                    <i class="fas fa-save"></i> {{ __('Update Status') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
(function() {
    'use strict';
    
    function updateCampaignStatus(campaignId) {
        console.log('updateCampaignStatus called with campaignId:', campaignId);
        
        const statusSelect = document.getElementById('campaign-status-select');
        const updateBtn = document.getElementById('update-status-btn');
        const messageDiv = document.getElementById('status-update-message');
        
        if (!statusSelect || !updateBtn) {
            console.error('Required elements not found');
            return;
        }
        
        const statusId = statusSelect.value;
        
        if (!statusId || !campaignId) {
            alert('Missing required information');
            return;
        }
        
        // Disable button and show loading
        updateBtn.disabled = true;
        const originalHtml = updateBtn.innerHTML;
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        messageDiv.innerHTML = '';
        
        // Get CSRF token - try multiple methods
        let csrfToken = '';
        
        // Method 1: Get from button data attribute (most reliable)
        if (updateBtn) {
            csrfToken = updateBtn.getAttribute('data-csrf-token');
        }
        
        // Method 2: meta tag
        if (!csrfToken) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                csrfToken = csrfMeta.getAttribute('content');
            }
        }
        
        // Method 3: Laravel's default token name
        if (!csrfToken) {
            const tokenMeta = document.querySelector('meta[name="_token"]');
            if (tokenMeta) {
                csrfToken = tokenMeta.getAttribute('content');
            }
        }
        
        // Method 4: Get from form or hidden input
        if (!csrfToken) {
            const tokenInput = document.querySelector('input[name="_token"]');
            if (tokenInput) {
                csrfToken = tokenInput.value;
            }
        }
        
        if (!csrfToken) {
            messageDiv.innerHTML = '<div class="alert alert-danger">CSRF token not found. Please refresh the page.</div>';
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalHtml;
            return;
        }
        
        // Make AJAX request
        const url = '/campaigns/' + campaignId + '/status';
        console.log('Making request to:', url, 'with status_id:', statusId);
        
        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                status_id: parseInt(statusId)
            }),
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Server error: ' + response.status);
                }).catch(() => {
                    throw new Error('Server error: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + (data.message || 'Status updated successfully') + '</div>';
                
                // Reload page after 1.5 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (data.error || 'Failed to update status') + '</div>';
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (error.message || 'An error occurred while updating the status') + '</div>';
            updateBtn.disabled = false;
            updateBtn.innerHTML = originalHtml;
        });
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        const updateBtn = document.getElementById('update-status-btn');
        if (updateBtn) {
            const campaignId = updateBtn.getAttribute('data-campaign-id');
            if (campaignId) {
                updateBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    updateCampaignStatus(parseInt(campaignId));
                });
            } else {
                console.error('Campaign ID not found in button data attribute');
            }
        } else {
            console.error('Update button not found');
        }
    }
})();

// jQuery fallback (since jQuery is loaded)
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        $('#update-status-btn').on('click', function(e) {
            e.preventDefault();
            const campaignId = $(this).data('campaign-id');
            if (campaignId) {
                updateCampaignStatus(campaignId);
            }
        });
    });
}
</script>
@endpush

@stop
