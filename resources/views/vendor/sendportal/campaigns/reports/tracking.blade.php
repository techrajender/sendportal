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
                            class="btn btn-sm btn-light mr-2" 
                            id="toggle-mask-btn"
                            onclick="toggleEmailMask()"
                            title="{{ __('Mask/Unmask Emails') }}">
                        <i class="fas fa-eye-slash" id="mask-icon"></i>
                        <span id="mask-status">{{ __('Mask On') }}</span>
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
                        <th class="text-center">{{ __('Actions') }}</th>
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
                                <strong class="subscriber-email" data-email="{{ $subscriber->email }}">{{ $subscriber->email }}</strong><br>
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
                            <td class="text-center">
                                <a href="javascript:void(0)" 
                                   class="text-primary" 
                                   onclick="openGenerateClickDrawer({{ $campaign->id }}, '{{ $subscriber->hash }}', '{{ $subscriber->email }}', {{ json_encode(array_keys($events)) }})"
                                   style="font-size: 1.2rem; display: inline-block; padding: 0.25rem;">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">
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
                <h5 class="mb-0" id="trackingDrawerTitle">{{ __('Tracking Event Details') }}</h5>
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
        .generate-link-btn {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: geometricPrecision;
            border: none;
            background: transparent;
            outline: none;
            position: relative;
            padding: 0.25rem;
        }
        .generate-link-btn:hover {
            opacity: 0.7;
            color: #0056b3 !important;
        }
        .generate-link-btn:hover i {
            text-shadow: none;
        }
        .generate-link-btn:active {
            opacity: 0.5;
        }
        .generate-link-btn:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
            border-radius: 2px;
        }
        .generate-link-btn i {
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
            font-weight: normal;
            text-rendering: geometricPrecision;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
        #toggle-mask-btn {
            font-size: 0.875rem;
        }
        #toggle-mask-btn #mask-status {
            margin-left: 0.25rem;
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

    // Email masking functionality
    let emailMasked = true; // Default: mask on
    
    function maskEmail(email) {
        if (!email || !email.includes('@')) {
            return email;
        }
        const [localPart, domain] = email.split('@');
        const maskedLocal = localPart.length > 2 
            ? localPart.substring(0, 2) + '***' 
            : localPart.substring(0, 1) + '***';
        const [domainName, ...tldParts] = domain.split('.');
        const maskedDomain = domainName.length > 2 
            ? domainName.substring(0, 2) + '***' 
            : domainName.substring(0, 1) + '***';
        const tld = tldParts.join('.');
        return maskedLocal + '@' + maskedDomain + '.' + tld;
    }
    
    function toggleEmailMask() {
        emailMasked = !emailMasked;
        const maskBtn = document.getElementById('toggle-mask-btn');
        const maskIcon = document.getElementById('mask-icon');
        const maskStatus = document.getElementById('mask-status');
        const emailElements = document.querySelectorAll('.subscriber-email');
        
        if (emailMasked) {
            // Mask on
            maskIcon.classList.remove('fa-eye');
            maskIcon.classList.add('fa-eye-slash');
            maskStatus.textContent = '{{ __("Mask On") }}';
            emailElements.forEach(el => {
                const originalEmail = el.getAttribute('data-email');
                el.textContent = maskEmail(originalEmail);
            });
        } else {
            // Mask off
            maskIcon.classList.remove('fa-eye-slash');
            maskIcon.classList.add('fa-eye');
            maskStatus.textContent = '{{ __("Mask Off") }}';
            emailElements.forEach(el => {
                const originalEmail = el.getAttribute('data-email');
                el.textContent = originalEmail;
            });
        }
    }
    
    // Apply masking on page load (default: mask on)
    document.addEventListener('DOMContentLoaded', function() {
        if (emailMasked) {
            const emailElements = document.querySelectorAll('.subscriber-email');
            emailElements.forEach(el => {
                const originalEmail = el.getAttribute('data-email');
                el.textContent = maskEmail(originalEmail);
            });
        }
    });

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
        const drawerTitle = document.getElementById('trackingDrawerTitle');
        
        // Reset title to default
        drawerTitle.textContent = '{{ __("Tracking Event Details") }}';

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

    // Variables to store drawer data
    let currentCampaignId = null;
    let currentSubscriberHash = null;
    
    // Task type labels
    const taskTypeLabels = {
        1: '{{ __("email_sent") }}',
        2: '{{ __("email_opened") }}',
        3: '{{ __("email_clicked") }}',
        4: '{{ __("newsletter_opened") }}',
        5: '{{ __("landing_page_opened") }}',
        6: '{{ __("thank_you_received") }}',
        7: '{{ __("asset_downloaded") }}'
    };
    
    function updateTaskNumber() {
        const taskNumberSelect = document.getElementById('taskNumber');
        const taskSuffixInput = document.getElementById('taskSuffix');
        const displayTaskNumber = document.getElementById('displayTaskNumber');
        const displayTaskType = document.getElementById('displayTaskType');
        const linkTaskNumber = document.getElementById('linkTaskNumber');
        
        if (!taskNumberSelect) return;
        
        // Get the selected option's value to ensure we have the correct value
        const selectedOption = taskNumberSelect.options[taskNumberSelect.selectedIndex];
        const taskNumber = selectedOption ? String(selectedOption.value) : String(taskNumberSelect.value || '3');
        const suffix = taskSuffixInput ? taskSuffixInput.value.trim() : '';
        const fullTaskNumber = suffix ? `${taskNumber}${suffix}` : taskNumber;
        
        console.log('updateTaskNumber - Task Number:', taskNumber);
        console.log('updateTaskNumber - Full Task Number:', fullTaskNumber);
        
        if (displayTaskNumber) {
            displayTaskNumber.textContent = fullTaskNumber;
        }
        if (displayTaskType) {
            // Use numeric index for taskTypeLabels
            const taskNum = parseInt(taskNumber, 10);
            displayTaskType.textContent = `(${taskTypeLabels[taskNum] || ''})`;
        }
        if (linkTaskNumber) {
            linkTaskNumber.textContent = fullTaskNumber;
        }
        
        // Always regenerate the tracking link when task number changes
        generateTrackingLink();
    }

    function openGenerateClickDrawer(campaignId, subscriberHash, subscriberEmail, completedTasks, preferredTaskNumber) {
        currentCampaignId = campaignId;
        currentSubscriberHash = subscriberHash;
        
        const drawer = document.getElementById('trackingDrawer');
        const drawerBody = document.getElementById('trackingDrawerBody');
        const drawerTitle = document.getElementById('trackingDrawerTitle');
        
        // Update title
        drawerTitle.textContent = '{{ __("Generate Click Tracking Link") }}';
        
        // Determine default task number - use preferredTaskNumber if provided, otherwise default to 3
        const defaultTaskNumber = preferredTaskNumber || 3;
        
        // Build task number options - show all tasks (1-7)
        let taskOptionsHtml = '';
        for (let i = 1; i <= 7; i++) {
            const isSelected = (i === defaultTaskNumber) ? ' selected' : '';
            const taskLabel = taskTypeLabels[i] || '';
            taskOptionsHtml += `<option value="${String(i)}"${isSelected}>${i} - ${taskLabel}</option>`;
        }
        
        // Build HTML content for generate link form
        const appUrl = '{{ config("app.url") }}';
        let html = `
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Subscriber') }}</div>
                <div class="tracking-detail-value">${subscriberEmail}</div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Link Components') }}</div>
                <div style="margin-top: 0.5rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px; font-size: 0.8rem;">
                    <div style="margin-bottom: 0.5rem;">
                        <strong>{{ __('App URL') }}:</strong><br>
                        <code style="color: #0066cc; word-break: break-all;">${appUrl}</code>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>{{ __('Campaign Hash') }}:</strong><br>
                        <code style="color: #0066cc;">${campaignId}</code>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>{{ __('Subscriber Hash') }}:</strong><br>
                        <code style="color: #0066cc; word-break: break-all; font-size: 0.75rem;">${subscriberHash}</code>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>{{ __('Task Number') }}:</strong><br>
                        <code style="color: #0066cc;" id="displayTaskNumber">${defaultTaskNumber}</code> <span style="color: #666; font-size: 0.75rem;" id="displayTaskType">(${taskTypeLabels[defaultTaskNumber] || ''})</span>
                    </div>
                    <div id="redirectUrlDisplay" style="display: none; margin-bottom: 0;">
                        <strong>{{ __('Redirect URL') }}:</strong><br>
                        <code style="color: #0066cc; word-break: break-all; font-size: 0.75rem;" id="redirectUrlCode"></code>
                    </div>
                </div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Task Number') }}</div>
                <div class="row" style="margin-top: 0.5rem;">
                    <div class="col-6">
                        <select class="form-control form-control-sm" id="taskNumber" onchange="updateTaskNumber()">
                            ${taskOptionsHtml}
                        </select>
                    </div>
                    <div class="col-6">
                        <input type="text" 
                               class="form-control form-control-sm" 
                               id="taskSuffix" 
                               placeholder="-001 (optional)"
                               oninput="updateTaskNumber()"
                               style="font-size: 0.75rem;">
                        <small class="form-text text-muted" style="font-size: 0.65rem; margin-top: 0.25rem;">
                            {{ __('Optional suffix like -001, -002') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">
                    {{ __('Redirect URL') }} <span class="text-danger">*</span>
                    <button type="button" 
                            class="btn btn-sm btn-link p-0 ml-2" 
                            onclick="appendDefaultQueryStrings()"
                            style="font-size: 0.7rem; vertical-align: baseline;"
                            title="{{ __('Add default query strings') }}">
                        <i class="fas fa-plus-circle text-primary"></i> {{ __('Add Default Query Strings') }}
                    </button>
                </div>
                <textarea class="form-control form-control-sm" 
                       id="redirectUrl" 
                       placeholder="https://unisonwavepromote.com/001/sniffr-002"
                       required
                       rows="3"
                       style="margin-top: 0.5rem; font-size: 0.75rem;"></textarea>
                <small class="form-text text-muted" style="font-size: 0.75rem; margin-top: 0.25rem;">
                    The URL to redirect to after tracking. You can use merge tags like <code>&#123;&#123; app_url &#125;&#125;</code>, <code>&#123;&#123; campaign_hash &#125;&#125;</code>, <code>&#123;&#123; subscriber_hash &#125;&#125;</code> which will be replaced when the email is sent.
                    <br>
                    <strong>Default query strings:</strong> <code>app_url=&#123;&#123; app_url &#125;&#125;&amp;campaign_hash=&#123;&#123; campaign_hash &#125;&#125;&amp;subscriber_hash=&#123;&#123; subscriber_hash &#125;&#125;&amp;token=5</code>
                </small>
            </div>
            <div class="tracking-detail-item">
                <div class="tracking-detail-label">{{ __('Generated Tracking Link') }}</div>
                <div class="input-group" style="margin-top: 0.5rem;">
                    <input type="text" 
                           class="form-control form-control-sm" 
                           id="generatedLink" 
                           readonly
                           style="font-size: 0.75rem;">
                    <div class="input-group-append">
                        <button class="btn btn-sm btn-outline-secondary" 
                                type="button" 
                                onclick="copyGeneratedLink()"
                                style="font-size: 0.75rem;">
                            <i class="fas fa-copy"></i> {{ __('Copy') }}
                        </button>
                    </div>
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.7rem; color: #666;">
                    <strong>{{ __('Link Format') }}:</strong><br>
                    <code style="font-size: 0.65rem; word-break: break-all; display: block; padding: 0.5rem; background: #f8f9fa; border-radius: 4px; margin-top: 0.25rem;">${appUrl}/api/track/<span id="linkCampaignId">${campaignId}</span>/<span id="linkSubscriberHash">${subscriberHash}</span>/<span id="linkTaskNumber">3</span>?redirect=<span id="linkRedirectUrl">...</span></code>
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.65rem; color: #666; padding: 0.5rem; background: #fff3cd; border-radius: 4px; border-left: 3px solid #ffc107;">
                    <strong>{{ __('Note') }}:</strong> Merge tags in the redirect URL (like <code>&#123;&#123; app_url &#125;&#125;</code>, <code>&#123;&#123; campaign_hash &#125;&#125;</code>, <code>&#123;&#123; subscriber_hash &#125;&#125;</code>) will be preserved and replaced when the email is sent.
                </div>
            </div>
            <div class="tracking-detail-item" style="border-bottom: none; padding-bottom: 0;">
                <button type="button" 
                        class="btn btn-primary btn-sm" 
                        onclick="submitTrackingRequest()"
                        style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-paper-plane"></i> {{ __('Submit Tracking Request') }}
                </button>
            </div>
        `;
        
        drawerBody.innerHTML = html;
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Generate initial tracking link with default task number
        setTimeout(() => {
            const redirectInput = document.getElementById('redirectUrl');
            const taskNumberSelect = document.getElementById('taskNumber');
            const taskSuffixInput = document.getElementById('taskSuffix');
            
            // Generate initial link (even without redirect URL)
            generateTrackingLink();
            
            if (redirectInput) {
                redirectInput.addEventListener('input', function() {
                    generateTrackingLink();
                });
            }
            
            if (taskNumberSelect) {
                taskNumberSelect.addEventListener('change', function() {
                    updateTaskNumber();
                    generateTrackingLink();
                });
            }
            
            if (taskSuffixInput) {
                taskSuffixInput.addEventListener('input', function() {
                    updateTaskNumber();
                    generateTrackingLink();
                });
            }
        }, 100);
    }

    function generateTrackingLink() {
        const redirectUrlInput = document.getElementById('redirectUrl');
        const linkInput = document.getElementById('generatedLink');
        const redirectUrlDisplay = document.getElementById('redirectUrlDisplay');
        const redirectUrlCode = document.getElementById('redirectUrlCode');
        const linkRedirectUrl = document.getElementById('linkRedirectUrl');
        const taskNumberSelect = document.getElementById('taskNumber');
        const taskSuffixInput = document.getElementById('taskSuffix');
        
        if (!redirectUrlInput || !linkInput) {
            console.warn('Required elements not found');
            return;
        }
        
        const redirectUrl = redirectUrlInput.value.trim();
        console.log('Reading redirect URL from input:', redirectUrl);
        
        // Get task number and suffix - ensure we get the actual selected value
        let taskNumber = '3'; // default
        if (taskNumberSelect) {
            // Get the selected option's value
            const selectedOption = taskNumberSelect.options[taskNumberSelect.selectedIndex];
            taskNumber = selectedOption ? String(selectedOption.value) : String(taskNumberSelect.value || '3');
            console.log('Selected option:', selectedOption);
            console.log('Selected option value:', selectedOption ? selectedOption.value : 'none');
        }
        const suffix = taskSuffixInput ? taskSuffixInput.value.trim() : '';
        const fullTaskNumber = suffix ? `${taskNumber}${suffix}` : taskNumber;
        
        // Debug logging
        console.log('Task Number Select element:', taskNumberSelect);
        console.log('Task Number Select value:', taskNumberSelect ? taskNumberSelect.value : 'N/A');
        console.log('Selected Task Number (final):', taskNumber);
        console.log('Suffix:', suffix);
        console.log('Full Task Number:', fullTaskNumber);
        
        // Generate base tracking URL
        const appUrl = '{{ config("app.url") }}';
        let trackingUrl = `${appUrl}/api/track/${currentCampaignId}/${currentSubscriberHash}/${fullTaskNumber}`;
        
        // Only add redirect parameter if redirect URL is present
        if (redirectUrl) {
            // Don't validate URL if it contains merge tags (they're valid for email templates)
            const openBrace = '{';
            const hasMergeTags = redirectUrl.includes(openBrace + openBrace) || redirectUrl.includes(openBrace + '%');
            
            if (!hasMergeTags) {
                // Validate URL only if it doesn't contain merge tags
                // Extract base URL (without query params) for validation
                const baseUrl = redirectUrl.split('?')[0];
                try {
                    new URL(baseUrl);
                    // If base URL is valid, the full URL with query params should be fine
                } catch (e) {
                    // If validation fails, still allow it (might be a relative URL or special format)
                    console.warn('URL validation warning:', e);
                }
            }
            
            // Encode the entire redirect URL (including query parameters)
            const encodedRedirectUrl = encodeURIComponent(redirectUrl);
            trackingUrl += `?redirect=${encodedRedirectUrl}`;
            
            // Show redirect URL in display
            if (redirectUrlDisplay) {
                redirectUrlDisplay.style.display = 'block';
            }
            if (redirectUrlCode) {
                redirectUrlCode.textContent = redirectUrl;
            }
            if (linkRedirectUrl) {
                linkRedirectUrl.textContent = encodedRedirectUrl;
            }
        } else {
            // No redirect URL - hide redirect display
            if (redirectUrlDisplay) {
                redirectUrlDisplay.style.display = 'none';
            }
            if (linkRedirectUrl) {
                linkRedirectUrl.textContent = '(no redirect)';
            }
        }
        
        linkInput.value = trackingUrl;
        
        // Debug: log the generated URL to console
        console.log('Generated tracking URL:', trackingUrl);
        console.log('Redirect URL:', redirectUrl || '(none)');
        
        // Update redirect URL display in components section
        if (redirectUrlDisplay) {
            redirectUrlDisplay.style.display = 'block';
        }
        if (redirectUrlCode) {
            redirectUrlCode.textContent = redirectUrl;
        }
        if (linkRedirectUrl) {
            linkRedirectUrl.textContent = encodedRedirectUrl;
        }
    }

    function appendDefaultQueryStrings() {
        const redirectUrlInput = document.getElementById('redirectUrl');
        if (!redirectUrlInput) return;
        
        // Check if we have the required values
        if (!currentCampaignId || !currentSubscriberHash) {
            alert('{{ __("Campaign and subscriber information not available") }}');
            return;
        }
        
        const currentUrl = redirectUrlInput.value.trim();
        const appUrl = '{{ config("app.url") }}';
        
        // Build query string with actual values instead of merge tags
        const defaultQueryString = 'app_url=' + encodeURIComponent(appUrl) + 
                                   '&campaign_hash=' + encodeURIComponent(currentCampaignId) + 
                                   '&subscriber_hash=' + encodeURIComponent(currentSubscriberHash) + 
                                   '&token=5';
        
        if (!currentUrl) {
            // If no URL, show a message
            alert('{{ __("Please enter a base URL first (e.g., https://example.com)") }}');
            redirectUrlInput.focus();
            return;
        }
        
        // Check if URL already has query parameters
        const hasQuery = currentUrl.includes('?');
        const separator = hasQuery ? '&' : '?';
        
        // Check if default query strings are already present
        if (currentUrl.includes('app_url=')) {
            // Check if all default params are present
            const hasAllParams = currentUrl.includes('app_url=') && 
                                currentUrl.includes('campaign_hash=') && 
                                currentUrl.includes('subscriber_hash=') && 
                                currentUrl.includes('token=');
            if (hasAllParams) {
                alert('{{ __("Default query strings are already present in the URL") }}');
                return;
            }
        }
        
        // Append the default query strings with actual values
        const updatedUrl = currentUrl + separator + defaultQueryString;
        redirectUrlInput.value = updatedUrl;
        
        // Force a small delay to ensure the input value is updated
        setTimeout(() => {
            // Trigger link generation
            generateTrackingLink();
        }, 100);
        
        // Focus back on the textarea and scroll to show the added content
        redirectUrlInput.focus();
        redirectUrlInput.scrollTop = redirectUrlInput.scrollHeight;
    }

    function copyGeneratedLink() {
        const linkInput = document.getElementById('generatedLink');
        if (!linkInput || !linkInput.value) {
            alert('{{ __('Please generate a link first by entering a redirect URL') }}');
            return;
        }
        
        linkInput.select();
        document.execCommand('copy');
        
        // Show feedback
        const copyBtn = event.target.closest('button');
        if (copyBtn) {
            const originalHtml = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> {{ __("Copied!") }}';
            setTimeout(() => {
                copyBtn.innerHTML = originalHtml;
            }, 2000);
        }
    }

    function submitTrackingRequest() {
        const redirectUrlInput = document.getElementById('redirectUrl');
        const linkInput = document.getElementById('generatedLink');
        const taskNumberSelect = document.getElementById('taskNumber');
        const taskSuffixInput = document.getElementById('taskSuffix');
        
        if (!redirectUrlInput) return;
        
        const redirectUrl = redirectUrlInput.value.trim();
        
        if (!redirectUrl) {
            alert('{{ __('Please enter a redirect URL') }}');
            return;
        }

        // Get task number and suffix - ensure it's a string
        const taskNumber = taskNumberSelect ? String(taskNumberSelect.value) : '3';
        const suffix = taskSuffixInput ? taskSuffixInput.value.trim() : '';
        const fullTaskNumber = suffix ? `${taskNumber}${suffix}` : taskNumber;
        
        // Debug logging
        console.log('Submit - Task Number Select:', taskNumberSelect);
        console.log('Submit - Selected Task Number:', taskNumber);
        console.log('Submit - Full Task Number:', fullTaskNumber);

        // Don't validate URL if it contains merge tags (they're valid for email templates)
        const openBrace = '{';
        const hasMergeTags = redirectUrl.includes(openBrace + openBrace) || redirectUrl.includes(openBrace + '%');
        
        if (!hasMergeTags) {
            // Validate URL only if it doesn't contain merge tags
            try {
                new URL(redirectUrl);
            } catch (e) {
                alert('{{ __('Please enter a valid URL') }}');
                return;
            }
        }

        // Generate tracking URL if not already generated
        if (!linkInput || !linkInput.value) {
            generateTrackingLink();
        }

        const trackingUrl = linkInput ? linkInput.value : '';
        if (!trackingUrl) {
            alert('{{ __('Failed to generate tracking URL') }}');
            return;
        }
        
        // Show loading state
        const submitBtn = event.target;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Submitting...") }}';

        // Submit tracking request
        // Use the full task number with suffix - API will strip it automatically
        const appUrl = '{{ config("app.url") }}';
        const trackingUrlWithoutRedirect = `${appUrl}/api/track/${currentCampaignId}/${currentSubscriberHash}/${fullTaskNumber}`;
        
        // Make tracking request using fetch with proper error handling
        fetch(trackingUrlWithoutRedirect, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            mode: 'cors',
            credentials: 'omit'
        })
        .then(response => {
            // Check content type
            const contentType = response.headers.get('content-type');
            
            // If it's JSON, parse it
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            
            // If it's a redirect or other response, check status
            if (response.ok || response.status === 200) {
                // Try to get text and parse as JSON
                return response.text().then(text => {
                    // Check if it's HTML (error page)
                    if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<!doctype')) {
                        console.warn('Server returned HTML:', text.substring(0, 200));
                        // Still consider it a success if status is 200
                        return { success: true, message: 'Tracking recorded (HTML response)' };
                    }
                    // Try to parse as JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // If not JSON, assume success if status is OK
                        return { success: true, message: 'Tracking recorded' };
                    }
                });
            }
            
            // For other status codes, assume success if it's 2xx
            if (response.status >= 200 && response.status < 300) {
                return { success: true, message: 'Tracking recorded' };
            }
            
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        })
        .then(data => {
            if (data && data.success) {
                alert('{{ __('Tracking request submitted successfully! The page will refresh to show the updated data.') }}');
                closeTrackingDrawer();
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                throw new Error(data?.message || 'Tracking failed');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            console.error('Tracking URL:', trackingUrlWithoutRedirect);
            
            // Fallback: Use image element method (more reliable for tracking pixels)
            console.log('Trying fallback method with image element...');
            const img = new Image();
            let requestCompleted = false;
            
            img.onload = function() {
                if (!requestCompleted) {
                    requestCompleted = true;
                    alert('{{ __('Tracking request submitted successfully! The page will refresh to show the updated data.') }}');
                    closeTrackingDrawer();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            };
            
            img.onerror = function() {
                // Even if image fails, the request was likely sent
                if (!requestCompleted) {
                    requestCompleted = true;
                    alert('{{ __('Tracking request sent. The page will refresh to verify.') }}');
                    closeTrackingDrawer();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            };
            
            // Set timeout
            setTimeout(() => {
                if (!requestCompleted) {
                    requestCompleted = true;
                    alert('{{ __('Tracking request sent. The page will refresh to verify.') }}');
                    closeTrackingDrawer();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            }, 3000);
            
            // Trigger tracking with image element
            img.src = trackingUrlWithoutRedirect;
        });
    }
</script>
@endpush

@endsection
