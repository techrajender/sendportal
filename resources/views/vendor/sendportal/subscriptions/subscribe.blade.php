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
    .subscribe-container {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 3rem;
        max-width: 500px;
        width: 100%;
        margin: 2rem;
        animation: fadeInUp 0.6s ease-out;
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
    .subscribe-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        box-shadow: 0 10px 30px rgba(72, 187, 120, 0.3);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    .subscribe-icon svg {
        width: 40px;
        height: 40px;
        fill: white;
    }
    .subscribe-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1rem;
        text-align: center;
    }
    .subscribe-message {
        color: #4a5568;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
        text-align: center;
    }
    .subscribe-email {
        color: #667eea;
        font-weight: 600;
        word-break: break-all;
    }
    .subscribe-form {
        margin-top: 2rem;
    }
    .btn-subscribe {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border: none;
        color: white;
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-subscribe:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(72, 187, 120, 0.5);
        background: linear-gradient(135deg, #38a169 0%, #48bb78 100%);
    }
    .btn-subscribe:active {
        transform: translateY(0);
    }
    .subscribe-note {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f0fff4;
        border-radius: 10px;
        color: #22543d;
        font-size: 0.9rem;
        text-align: center;
        line-height: 1.5;
    }
    .subscribe-note-icon {
        display: inline-block;
        margin-right: 0.5rem;
    }
    @media (max-width: 576px) {
        .subscribe-container {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }
        .subscribe-title {
            font-size: 1.5rem;
        }
        .subscribe-message {
            font-size: 1rem;
        }
    }
</style>

<div class="page-logo">
    <img src="{{ asset('/vendor/sendportal/img/unisonwave-logo.svg') }}" alt="{{ config('app.name', 'UnisonWave') }}" onerror="this.style.display='none'">
</div>

<div class="subscribe-container">
    <div class="subscribe-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
    </div>
    
    <h1 class="subscribe-title">{{ __('Resubscribe') }}</h1>
    
    <p class="subscribe-message">
        {!! __('Welcome back! Would you like to add <span class="subscribe-email">:email</span> back to this email list?', ['email' => $message->subscriber->email])  !!}
    </p>

    <form action="{{ route('sendportal.subscriptions.update', $message->hash) }}" method="post" class="subscribe-form">
            @csrf
            <input type="hidden" name="_method" value="put">
            <input type="hidden" name="unsubscribed" value="0">
        <button type="submit" class="btn-subscribe">
            {{ __('Yes, Resubscribe') }}
        </button>
        </form>

    <div class="subscribe-note">
        <span class="subscribe-note-icon">✨</span>
        {{ __('You\'ll start receiving our emails again.') }}
    </div>
    </div>
@endsection
