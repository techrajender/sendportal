@extends('sendportal::layouts.app')

@section('title', $campaign->name . ' - ' . __('Tracking'))

@section('heading')
    {{ $campaign->name }}
@endsection

@section('content')

    @include('sendportal::campaigns.reports.partials.nav')

    <div class="card">
        <div class="card-header card-header-accent">
            <div class="card-header-inner d-flex justify-content-between align-items-center">
                <span>{{ __('Subscriber Tracking') }}</span>
                <div class="d-flex align-items-center">
                    <button type="button" 
                            class="btn btn-sm btn-light mr-2" 
                            id="toggle-filters-btn"
                            onclick="toggleFilters()"
                            title="{{ __('Show/Hide Filters') }}">
                        <i class="fas fa-filter" id="filter-icon"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-light" 
                            id="refresh-tracking-btn"
                            onclick="refreshTrackingData()"
                            title="{{ __('Refresh Data') }}">
                        <i class="fas fa-sync-alt" id="refresh-icon"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Filters and Export Section -->
        <div class="card-body border-bottom" id="filters-section" style="display: none;">
            <form method="GET" action="{{ route('sendportal.campaigns.reports.tracking', $campaign->id) }}" id="filter-form" class="row align-items-end">
                <div class="col-md-4">
                    <label for="search_email" class="form-label">{{ __('Search Email') }}</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           id="search_email" 
                           name="search_email" 
                           value="{{ $searchEmail ?? '' }}" 
                           placeholder="{{ __('Enter email address...') }}">
                </div>
                <div class="col-md-4">
                    <label for="filter_task_type" class="form-label">{{ __('Filter by Task') }}</label>
                    <select class="form-control form-control-sm" id="filter_task_type" name="filter_task_type">
                        <option value="">{{ __('All Tasks') }}</option>
                        <option value="email_sent" {{ ($filterTaskType ?? '') === 'email_sent' ? 'selected' : '' }}>{{ __('Email Sent') }}</option>
                        <option value="email_opened" {{ ($filterTaskType ?? '') === 'email_opened' ? 'selected' : '' }}>{{ __('Email Opened') }}</option>
                        <option value="email_clicked" {{ ($filterTaskType ?? '') === 'email_clicked' ? 'selected' : '' }}>{{ __('Email Clicked') }}</option>
                        <option value="newsletter_opened" {{ ($filterTaskType ?? '') === 'newsletter_opened' ? 'selected' : '' }}>{{ __('Newsletter Opened') }}</option>
                        <option value="landing_page_opened" {{ ($filterTaskType ?? '') === 'landing_page_opened' ? 'selected' : '' }}>{{ __('Landing Page') }}</option>
                        <option value="thank_you_received" {{ ($filterTaskType ?? '') === 'thank_you_received' ? 'selected' : '' }}>{{ __('Thank You') }}</option>
                        <option value="asset_downloaded" {{ ($filterTaskType ?? '') === 'asset_downloaded' ? 'selected' : '' }}>{{ __('Asset Downloaded') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> {{ __('Apply Filters') }}
                    </button>
                    <a href="{{ route('sendportal.campaigns.reports.tracking', $campaign->id) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i> {{ __('Clear') }}
                    </a>
                </div>
            </form>
            
            <div class="mt-3">
                <form method="GET" action="{{ route('sendportal.campaigns.reports.tracking.export', $campaign->id) }}" id="export-form" class="d-inline">
                    @if(!empty($searchEmail))
                        <input type="hidden" name="search_email" value="{{ $searchEmail }}">
                    @endif
                    @if(!empty($filterTaskType))
                        <input type="hidden" name="filter_task_type" value="{{ $filterTaskType }}">
                    @endif
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="include_device_info" name="include_device_info" value="1">
                        <label class="form-check-label" for="include_device_info">
                            {{ __('Include Device Info (Browser, Device Type, OS, IP, User Agent)') }}
                        </label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fas fa-download"></i> {{ __('Export CSV') }}
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card-table table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Subscriber') }}</th>
                        <th class="text-center">{{ __('Email Sent') }}</th>
                        <th class="text-center">{{ __('Email Opened') }}</th>
                        <th class="text-center">{{ __('Email Clicked') }}</th>
                        <th class="text-center">{{ __('Newsletter Opened') }}</th>
                        <th class="text-center">{{ __('Landing Page') }}</th>
                        <th class="text-center">{{ __('Thank You') }}</th>
                        <th class="text-center">{{ __('Asset Downloaded') }}</th>
                        <th class="text-center">{{ __('Last Activity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $subscriber)
                        @php
                            $events = $subscriber->tracking_events ?? [];
                            
                            $taskTypes = [
                                'email_sent' => __('Email Sent'),
                                'email_opened' => __('Email Opened'),
                                'email_clicked' => __('Email Clicked'),
                                'newsletter_opened' => __('Newsletter Opened'),
                                'landing_page_opened' => __('Landing Page'),
                                'thank_you_received' => __('Thank You'),
                                'asset_downloaded' => __('Asset Downloaded'),
                            ];
                            
                            $lastActivity = null;
                            foreach ($events as $event) {
                                if ($event && (!$lastActivity || $event->tracked_at > $lastActivity)) {
                                    $lastActivity = $event->tracked_at;
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $subscriber->email }}</strong><br>
                                <small class="text-muted">
                                    {{ $subscriber->first_name }} {{ $subscriber->last_name }}
                                </small>
                            </td>
                            @foreach(['email_sent', 'email_opened', 'email_clicked', 'newsletter_opened', 'landing_page_opened', 'thank_you_received', 'asset_downloaded'] as $taskType)
                                <td class="text-center">
                                    @if(isset($events[$taskType]))
                                        @php
                                            $event = $events[$taskType];
                                            $deviceInfo = $event->metadata['device_info'] ?? null;
                                            $eventId = 'event-' . $subscriber->id . '-' . $taskType;
                                        @endphp
                                        @if($event->status === 'opened')
                                            <div class="tracking-event-cell">
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> {{ __('Yes') }}
                                                </span>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.65rem; line-height: 1.2;">
                                                    {{ $event->tracked_at->format('Y-m-d H:i:s') }}
                                                </small>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.6rem; line-height: 1.2;">
                                                    {{ $event->tracked_at->diffForHumans() }}
                                                    <button type="button" 
                                                            class="btn btn-sm btn-link p-0 text-info ml-1" 
                                                            onclick="openTrackingDrawer('{{ $eventId }}')"
                                                            data-toggle="tooltip" 
                                                            data-placement="top" 
                                                            title="{{ __('View Details') }}"
                                                            style="vertical-align: baseline;">
                                                        <i class="fas fa-info-circle" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                </small>
                                                <!-- Hidden data for drawer -->
                                                <div id="{{ $eventId }}" 
                                                     style="display: none;"
                                                     data-event-id="{{ $event->id }}"
                                                     data-task-type="{{ $taskType }}"
                                                     data-status="{{ $event->status }}"
                                                     data-tracked-at="{{ $event->tracked_at->toIso8601String() }}"
                                                     data-tracked-at-formatted="{{ $event->tracked_at->format('Y-m-d H:i:s') }}"
                                                     data-browser="{{ $deviceInfo['browser'] ?? 'Unknown' }}"
                                                     data-device-type="{{ $deviceInfo['device_type'] ?? 'Unknown' }}"
                                                     data-os="{{ $deviceInfo['os'] ?? 'Unknown' }}"
                                                     data-ip="{{ $deviceInfo['ip_address'] ?? 'Unknown' }}"
                                                     data-user-agent="{{ $deviceInfo['user_agent'] ?? 'Unknown' }}"
                                                     data-metadata="{{ json_encode($event->metadata) }}">
                                                </div>
                                            </div>
                                        @elseif($event->status === 'pending')
                                            <div class="tracking-event-cell">
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> {{ __('Pending') }}
                                                </span>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.65rem; line-height: 1.2;">
                                                    {{ $event->tracked_at->format('Y-m-d H:i:s') }}
                                                </small>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.6rem; line-height: 1.2;">
                                                    {{ $event->tracked_at->diffForHumans() }}
                                                    <button type="button" 
                                                            class="btn btn-sm btn-link p-0 text-info ml-1" 
                                                            onclick="openTrackingDrawer('{{ $eventId }}')"
                                                            data-toggle="tooltip" 
                                                            data-placement="top" 
                                                            title="{{ __('View Details') }}"
                                                            style="vertical-align: baseline;">
                                                        <i class="fas fa-info-circle" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                </small>
                                                <!-- Hidden data for drawer -->
                                                <div id="{{ $eventId }}" 
                                                     style="display: none;"
                                                     data-event-id="{{ $event->id }}"
                                                     data-task-type="{{ $taskType }}"
                                                     data-status="{{ $event->status }}"
                                                     data-tracked-at="{{ $event->tracked_at->toIso8601String() }}"
                                                     data-tracked-at-formatted="{{ $event->tracked_at->format('Y-m-d H:i:s') }}"
                                                     data-browser="{{ $deviceInfo['browser'] ?? 'Unknown' }}"
                                                     data-device-type="{{ $deviceInfo['device_type'] ?? 'Unknown' }}"
                                                     data-os="{{ $deviceInfo['os'] ?? 'Unknown' }}"
                                                     data-ip="{{ $deviceInfo['ip_address'] ?? 'Unknown' }}"
                                                     data-user-agent="{{ $deviceInfo['user_agent'] ?? 'Unknown' }}"
                                                     data-metadata="{{ json_encode($event->metadata) }}">
                                                </div>
                                            </div>
                                        @elseif($event->status === 'failed')
                                            <div class="tracking-event-cell">
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times"></i> {{ __('Failed') }}
                                                </span>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.65rem; line-height: 1.2;">
                                                    {{ $event->tracked_at->format('Y-m-d H:i:s') }}
                                                </small>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.6rem; line-height: 1.2;">
                                                    {{ $event->tracked_at->diffForHumans() }}
                                                    <button type="button" 
                                                            class="btn btn-sm btn-link p-0 text-info ml-1" 
                                                            onclick="openTrackingDrawer('{{ $eventId }}')"
                                                            data-toggle="tooltip" 
                                                            data-placement="top" 
                                                            title="{{ __('View Details') }}"
                                                            style="vertical-align: baseline;">
                                                        <i class="fas fa-info-circle" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                </small>
                                                <!-- Hidden data for drawer -->
                                                <div id="{{ $eventId }}" 
                                                     style="display: none;"
                                                     data-event-id="{{ $event->id }}"
                                                     data-task-type="{{ $taskType }}"
                                                     data-status="{{ $event->status }}"
                                                     data-tracked-at="{{ $event->tracked_at->toIso8601String() }}"
                                                     data-tracked-at-formatted="{{ $event->tracked_at->format('Y-m-d H:i:s') }}"
                                                     data-browser="{{ $deviceInfo['browser'] ?? 'Unknown' }}"
                                                     data-device-type="{{ $deviceInfo['device_type'] ?? 'Unknown' }}"
                                                     data-os="{{ $deviceInfo['os'] ?? 'Unknown' }}"
                                                     data-ip="{{ $deviceInfo['ip_address'] ?? 'Unknown' }}"
                                                     data-user-agent="{{ $deviceInfo['user_agent'] ?? 'Unknown' }}"
                                                     data-metadata="{{ json_encode($event->metadata) }}">
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-minus"></i> {{ __('No') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge badge-light">
                                            <i class="fas fa-minus"></i> {{ __('No') }}
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">
                                @if($lastActivity)
                                    <small>{{ $lastActivity->diffForHumans() }}</small><br>
                                    <small class="text-muted">{{ $lastActivity->format('Y-m-d H:i:s') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                <p class="empty-table-text">{{ __('No tracking data available yet.') }}</p>
                                <p class="text-muted">{{ __('Tracking data will appear here once emails are sent and subscribers interact with the campaign.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('sendportal::layouts.partials.pagination', ['records' => $subscribers])

    <!-- Tracking Details Drawer -->
    <div class="tracking-drawer" id="trackingDrawer">
        <div class="tracking-drawer-overlay" onclick="closeTrackingDrawer()"></div>
        <div class="tracking-drawer-content">
            <div class="tracking-drawer-header">
                <h5 class="mb-0">{{ __('Tracking Event Details') }}</h5>
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="closeTrackingDrawer()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="tracking-drawer-body" id="trackingDrawerBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <style>
        .tracking-drawer {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1050;
        }
        .tracking-drawer.active {
            display: block;
        }
        .tracking-drawer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        .tracking-drawer-content {
            position: absolute;
            right: 0;
            top: 0;
            width: 400px;
            max-width: 90%;
            height: 100%;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(0);
            }
        }
        .tracking-drawer-header {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        .tracking-drawer-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .tracking-detail-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .tracking-detail-item:last-child {
            border-bottom: none;
        }
        .tracking-detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        .tracking-detail-value {
            color: #333;
            font-size: 0.9375rem;
        }
        .gap-1 {
            gap: 4px;
        }
        #toggle-filters-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        #toggle-filters-btn.active:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        #filters-section {
            transition: all 0.3s ease;
        }
    </style>

@push('js')
<script>
    function toggleFilters() {
        const filtersSection = document.getElementById('filters-section');
        const toggleBtn = document.getElementById('toggle-filters-btn');
        
        if (filtersSection.style.display === 'none' || filtersSection.style.display === '') {
            filtersSection.style.display = 'block';
            toggleBtn.classList.add('active');
        } else {
            filtersSection.style.display = 'none';
            toggleBtn.classList.remove('active');
        }
    }
    
    // Show filters section if filters are active
    @if(!empty($searchEmail) || !empty($filterTaskType))
        document.addEventListener('DOMContentLoaded', function() {
            toggleFilters();
        });
    @endif

    function refreshTrackingData() {
        const btn = document.getElementById('refresh-tracking-btn');
        const icon = document.getElementById('refresh-icon');
        
        // Disable button and add spinning animation
        btn.disabled = true;
        icon.classList.add('fa-spin');
        
        // Reload the page after a short delay to show the animation
        setTimeout(function() {
            window.location.reload();
        }, 300);
    }

    function openTrackingDrawer(eventId) {
        const eventData = document.getElementById(eventId);
        if (!eventData) return;

        const drawer = document.getElementById('trackingDrawer');
        const drawerBody = document.getElementById('trackingDrawerBody');

        // Get task type labels
        const taskTypeLabels = {
            'email_sent': '{{ __('Email Sent') }}',
            'email_opened': '{{ __('Email Opened') }}',
            'email_clicked': '{{ __('Email Clicked') }}',
            'newsletter_opened': '{{ __('Newsletter Opened') }}',
            'landing_page_opened': '{{ __('Landing Page Opened') }}',
            'thank_you_received': '{{ __('Thank You Received') }}',
            'asset_downloaded': '{{ __('Asset Downloaded') }}'
        };

        const taskType = eventData.getAttribute('data-task-type');
        const status = eventData.getAttribute('data-status');
        const trackedAtFormatted = eventData.getAttribute('data-tracked-at-formatted');
        const browser = eventData.getAttribute('data-browser');
        const deviceType = eventData.getAttribute('data-device-type');
        const os = eventData.getAttribute('data-os');
        const ip = eventData.getAttribute('data-ip');
        const userAgent = eventData.getAttribute('data-user-agent');
        const metadata = JSON.parse(eventData.getAttribute('data-metadata') || '{}');

        // Use the formatted date directly from server to avoid timezone conversion issues
        // This matches the format shown in the table: Y-m-d H:i:s
        let formattedDate = trackedAtFormatted;
        
        // Fallback: if formatted date not available, parse ISO8601 and format it
        if (!formattedDate) {
            const trackedAt = eventData.getAttribute('data-tracked-at');
            if (trackedAt) {
                const date = new Date(trackedAt);
                if (!isNaN(date.getTime())) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getUTCHours()).padStart(2, '0');
                    const minutes = String(date.getUTCMinutes()).padStart(2, '0');
                    const seconds = String(date.getUTCSeconds()).padStart(2, '0');
                    formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                }
            }
        }

        // Build HTML content
        let html = `
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Event Type') }}</div>
                <div class="tracking-detail-value">${taskTypeLabels[taskType] || taskType}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Status') }}</div>
                <div class="tracking-detail-value">
                    <span class="badge badge-${status === 'opened' ? 'success' : status === 'pending' ? 'warning' : 'danger'}">${status}</span>
                </div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Tracked At') }}</div>
                <div class="tracking-detail-value">${formattedDate}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Browser') }}</div>
                <div class="tracking-detail-value">${browser}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Device Type') }}</div>
                <div class="tracking-detail-value">${deviceType}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Operating System') }}</div>
                <div class="tracking-detail-value">${os}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('IP Address') }}</div>
                <div class="tracking-detail-value">${ip}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('User Agent') }}</div>
                <div class="tracking-detail-value" style="word-break: break-all; font-size: 0.8rem; color: #666;">${userAgent}</div>
            </div>
        `;

        // Add URL if it's a click event
        if (metadata.url || metadata.redirect) {
            const url = metadata.url || metadata.redirect;
            html += `
                <div class="tracking-detail-item">
                    <div class="tracking-detail-label">{{ __('Clicked URL') }}</div>
                    <div class="tracking-detail-value">
                        <a href="${url}" target="_blank" style="word-break: break-all;">${url}</a>
                    </div>
                </div>
            `;
        }

        // Add other metadata if available
        if (Object.keys(metadata).length > 0 && !metadata.device_info) {
            html += `
                <div class="tracking-detail-item">
                    <div class="tracking-detail-label">{{ __('Additional Metadata') }}</div>
                    <div class="tracking-detail-value">
                        <pre style="font-size: 0.8rem; background: #f5f5f5; padding: 0.5rem; border-radius: 4px; overflow-x: auto;">${JSON.stringify(metadata, null, 2)}</pre>
                    </div>
                </div>
            `;
        }

        drawerBody.innerHTML = html;
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeTrackingDrawer() {
        const drawer = document.getElementById('trackingDrawer');
        drawer.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Close drawer on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTrackingDrawer();
        }
    });

    // Initialize tooltips on page load
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endpush

@endsection
