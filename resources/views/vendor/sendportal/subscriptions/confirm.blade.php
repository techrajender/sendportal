@extends('sendportal::layouts.subscriptions')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #ffffff 0%, #f7fafc 50%, #edf2f7 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        position: relative;
    }
    .page-logo {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 10;
        max-width: 300px;
        height: auto;
    }
    .page-logo img,
    .page-logo svg {
        max-width: 100%;
        height: auto;
        display: block;
        filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
        transition: transform 0.3s ease;
    }
    .page-logo img:hover,
    .page-logo svg:hover {
        transform: scale(1.05);
    }
    @media (max-width: 576px) {
        .page-logo {
            top: 15px;
            left: 15px;
            max-width: 200px;
        }
    }
    .confirm-container {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 3rem;
        max-width: 500px;
        width: 100%;
        margin: 2rem;
        animation: fadeInUp 0.6s ease-out;
        text-align: center;
    }
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .confirm-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        box-shadow: 0 10px 30px rgba(72, 187, 120, 0.3);
        animation: checkmark 0.6s ease-out;
    }
    @keyframes checkmark {
        0% {
            transform: scale(0);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
    .confirm-icon svg {
        width: 40px;
        height: 40px;
        fill: white;
    }
    .confirm-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1rem;
    }
    .confirm-message {
        color: #4a5568;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }
    @media (max-width: 576px) {
        .confirm-container {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }
        .confirm-title {
            font-size: 1.5rem;
        }
    }
</style>

<div class="page-logo">
    <img src="{{ asset('/vendor/sendportal/img/unisonwave-logo.svg') }}" alt="{{ config('app.name', 'UnisonWave') }}" onerror="this.style.display='none'">
</div>

<div class="confirm-container">
    <div class="confirm-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
    </div>
    
    <h1 class="confirm-title">{{ __('Success!') }}</h1>
    
    <p class="confirm-message">
        @if(session('success'))
            {{ session('success') }}
        @else
            {{ __('Your subscription preferences have been updated.') }}
        @endif
    </p>
</div>
@endsection
