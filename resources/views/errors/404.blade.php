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
    .error-container {
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
    .error-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        box-shadow: 0 10px 30px rgba(229, 62, 62, 0.3);
    }
    .error-icon svg {
        width: 60px;
        height: 60px;
        fill: white;
    }
    .error-code {
        font-size: 6rem;
        font-weight: 700;
        color: #e53e3e;
        margin-bottom: 1rem;
        line-height: 1;
    }
    .error-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1rem;
    }
    .error-message {
        color: #4a5568;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 0;
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
        .error-container {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }
        .error-code {
            font-size: 4rem;
        }
        .error-title {
            font-size: 1.5rem;
        }
        .error-message {
            font-size: 1rem;
        }
        .page-logo {
            top: 15px;
            left: 15px;
            max-width: 200px;
        }
    }
</style>

<div class="page-logo">
    <img src="{{ asset('/vendor/sendportal/img/unisonwave-logo.svg') }}" alt="{{ config('app.name', 'UnisonWave') }}" onerror="this.style.display='none'">
</div>

<div class="error-container">
    <div class="error-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
    </div>
    
    <div class="error-code">404</div>
    
    <h1 class="error-title">{{ __('Page Not Found') }}</h1>
    
    <p class="error-message">
        {{ __('Sorry, the page you are looking for could not be found.') }}<br>
        {{ __('The link may be invalid or expired.') }}
    </p>
</div>
@endsection

