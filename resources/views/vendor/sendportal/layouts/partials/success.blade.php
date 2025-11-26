        @if(session()->has('success'))
    <div style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 500px; width: calc(100% - 2rem);">
        <div style="background: #48bb78; color: white; padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(72, 187, 120, 0.3); animation: slideDown 0.3s ease-out;">
            <p style="font-weight: 600; margin: 0; font-size: 1rem;">{{ session()->get('success') }}</p>
            </div>
    </div>
    <style>
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
    </style>
@endif
