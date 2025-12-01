@extends('sendportal::layouts.app')

@section('title', $campaign->name)

@section('heading', $campaign->name)

@section('content')

    @include('sendportal::campaigns.reports.partials.nav')

    <div class="card mb-3">
        <div class="card-header card-header-accent">
            <div class="card-header-inner d-flex justify-content-between align-items-center">
                <span>{{ __('Email Clicks Report') }}</span>
                <div class="d-flex align-items-center">
                    <button type="button" 
                            class="btn btn-sm btn-light" 
                            id="toggle-mask-btn"
                            onclick="toggleEmailMask()"
                            title="{{ __('Mask/Unmask Emails') }}">
                        <i class="fas fa-eye-slash" id="mask-icon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-md-0 mb-3">
            <div class="widget flex-row align-items-center align-items-stretch">
                <div class="col-8 py-4 rounded-right">
                    <div class="h4 m-0">{{ $campaign->unique_click_count }}</div>
                    <div class="text-uppercase">{{ __('Unique Clicks') }}</div>
                </div>
                <div class="col-4 d-flex align-items-center justify-content-center rounded-left">
                    <em class="far fa-hand-pointer fa-2x color-gray-400"></em>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6 mb-md-0 mb-3">
            <div class="widget flex-row align-items-center align-items-stretch">
                <div class="col-8 py-4 rounded-right">
                    <div class="h4 m-0">{{ $campaign->total_click_count }}</div>
                    <div class="text-uppercase">{{ __('Total Clicks') }}</div>
                </div>
                <div class="col-4 d-flex align-items-center justify-content-center rounded-left">
                    <em class="fas fa-hand-pointer fa-2x color-gray-400"></em>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6 mb-md-0 mb-3">
            <div class="widget flex-row align-items-center align-items-stretch">
                <div class="col-8 py-4 rounded-right">
                    <div class="h4 m-0">{{ $averageTimeToClick }}</div>
                    <div class="text-uppercase">{{ __('Avg. Time To Click') }}</div>
                </div>
                <div class="col-4 d-flex align-items-center justify-content-center rounded-left">
                    <em class="far fa-clock fa-2x color-gray-400"></em>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-table table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('Subscriber') }}</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Clicked') }}</th>
                    <th>{{ __('Click Count') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>
                            <a href="{{ route('sendportal.subscribers.show', $message->subscriber_id) }}" class="subscriber-email" data-email="{{ $message->recipient_email }}">{{ $message->recipient_email }}</a>
                        </td>
                        <td>{{ $message->subject }}</td>
                        <td>{{ \Sendportal\Base\Facades\Helper::displayDate($message->clicked_at) }}</td>
                        <td>{{ $message->click_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="100%">
                            <p class="empty-table-text">{{ __('There are no messages') }}</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('sendportal::layouts.partials.pagination', ['records' => $messages])

@endsection

@push('js')
<script>
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
        const maskIcon = document.getElementById('mask-icon');
        const emailElements = document.querySelectorAll('.subscriber-email');
        
        if (emailMasked) {
            // Mask on
            maskIcon.classList.remove('fa-eye');
            maskIcon.classList.add('fa-eye-slash');
            emailElements.forEach(el => {
                const originalEmail = el.getAttribute('data-email');
                el.textContent = maskEmail(originalEmail);
            });
        } else {
            // Mask off
            maskIcon.classList.remove('fa-eye-slash');
            maskIcon.classList.add('fa-eye');
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
</script>
@endpush
