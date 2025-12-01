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
    .unsubscribe-container {
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
    .unsubscribe-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
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
    .unsubscribe-icon svg {
        width: 40px;
        height: 40px;
        fill: white;
    }
    .unsubscribe-title {
        font-size: 2rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1rem;
        text-align: center;
    }
    .unsubscribe-message {
        color: #4a5568;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
        text-align: center;
    }
    .unsubscribe-email {
        color: #667eea;
        font-weight: 600;
        word-break: break-all;
    }
    .unsubscribe-form {
        margin-top: 2rem;
    }
    .btn-unsubscribe {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        border: none;
        color: white;
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-unsubscribe:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.5);
        background: linear-gradient(135deg, #ee5a6f 0%, #ff6b6b 100%);
    }
    .btn-unsubscribe:active {
        transform: translateY(0);
    }
    .unsubscribe-note {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f7fafc;
        border-radius: 10px;
        color: #718096;
        font-size: 0.9rem;
        text-align: center;
        line-height: 1.5;
    }
    .unsubscribe-note-icon {
        display: inline-block;
        margin-right: 0.5rem;
        color: #a0aec0;
    }
    @media (max-width: 576px) {
        .unsubscribe-container {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }
        .unsubscribe-title {
            font-size: 1.5rem;
        }
        .unsubscribe-message {
            font-size: 1rem;
        }
    }
</style>

<div class="page-logo">
    <img src="{{ asset('/vendor/sendportal/img/unisonwave-logo.svg') }}" alt="{{ config('app.name', 'UnisonWave') }}" onerror="this.style.display='none'">
</div>

<div class="unsubscribe-container">
    <div class="unsubscribe-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
    </div>
    
    <h1 class="unsubscribe-title">{{ __('Unsubscribe') }}</h1>
    
    <p class="unsubscribe-message">
        {!! __('We\'re sorry to see you go! Are you sure you want to remove <span class="unsubscribe-email">:email</span> from this email list?', ['email' => $message->subscriber->email])  !!}
    </p>

    <form action="{{ route('sendportal.subscriptions.update', $message->hash) }}" method="post" class="unsubscribe-form">
            @csrf
            <input type="hidden" name="_method" value="put">
            <input type="hidden" name="unsubscribed" value="1">
        <button type="submit" class="btn-unsubscribe">
            {{ __('Yes, Unsubscribe') }}
        </button>
        </form>

    <div class="unsubscribe-note">
        <span class="unsubscribe-note-icon">ℹ️</span>
        {{ __('You can resubscribe at any time by clicking the link in future emails.') }}
    </div>
    </div>
@endsection
