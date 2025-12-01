{{-- Reusable PIN Confirmation Modal Component --}}
{{-- Usage: @include('components.pin-confirmation', ['actionId' => 'unique-id', 'actionTitle' => 'Title', 'actionMessage' => 'Message']) --}}

<div class="modal fade" id="pinConfirmModal-{{ $actionId }}" tabindex="-1" role="dialog" aria-labelledby="pinConfirmModalLabel-{{ $actionId }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title" id="pinConfirmModalLabel-{{ $actionId }}">
                    <i class="fas fa-shield-alt mr-2"></i>{{ $actionTitle ?? __('Confirm Action') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Success/Error Message Area -->
                <div id="pin-message-area-{{ $actionId }}" style="display: none;">
                    <div id="pin-success-message-{{ $actionId }}" class="alert alert-success" style="display: none;">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong id="pin-success-text-{{ $actionId }}"></strong>
                    </div>
                    <div id="pin-error-message-area-{{ $actionId }}" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong id="pin-error-text-{{ $actionId }}"></strong>
                    </div>
                </div>
                
                <div class="alert alert-warning" id="pin-warning-area-{{ $actionId }}">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>{{ __('Please confirm this action') }}</strong>
                    <p class="mb-0 mt-2" id="pin-action-message-{{ $actionId }}">{{ $actionMessage ?? __('Are you sure you want to proceed?') }}</p>
                </div>
                
                <div class="form-group mt-3">
                    <label class="font-weight-bold mb-2">
                        {{ __('Generated PIN') }}
                    </label>
                    <div class="alert alert-info text-center mb-3" style="background: #e7f3ff; border: 2px dashed #0066cc;">
                        <div class="mb-1">
                            <i class="fas fa-key mr-2"></i>
                            <strong style="font-size: 1.8rem; letter-spacing: 0.3rem; color: #0066cc;" id="pin-display-{{ $actionId }}">------</strong>
                        </div>
                        <small class="text-muted">{{ __('Please enter this PIN below to confirm') }}</small>
                    </div>
                    
                    <label for="pin-input-{{ $actionId }}" class="font-weight-bold">
                        {{ __('Enter 6-digit PIN') }}
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control text-center font-weight-bold" 
                               id="pin-input-{{ $actionId }}" 
                               maxlength="6" 
                               pattern="[0-9]{6}" 
                               placeholder="000000"
                               style="font-size: 1.5rem; letter-spacing: 0.5rem;"
                               autocomplete="off">
                    </div>
                    <small class="form-text text-muted">
                        <span id="pin-timer-{{ $actionId }}"></span>
                        <a href="#" id="pin-regenerate-{{ $actionId }}" class="ml-2" style="display: none;">
                            <i class="fas fa-redo mr-1"></i>{{ __('Regenerate PIN') }}
                        </a>
                    </small>
                    <div id="pin-error-{{ $actionId }}" class="text-danger mt-2" style="display: none;">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <span id="pin-error-message-{{ $actionId }}"></span>
                    </div>
                </div>
                
                <input type="hidden" id="pin-expected-{{ $actionId }}">
                <input type="hidden" id="pin-action-url-{{ $actionId }}">
                <input type="hidden" id="pin-action-method-{{ $actionId }}" value="POST">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>{{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary" id="confirm-pin-action-btn-{{ $actionId }}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;" disabled>
                    <i class="fas fa-check mr-2"></i>{{ __('Confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
(function() {
    const actionId = '{{ $actionId }}';
    const modal = $('#pinConfirmModal-' + actionId);
    const pinInput = $('#pin-input-' + actionId);
    const pinDisplay = $('#pin-display-' + actionId);
    const confirmBtn = $('#confirm-pin-action-btn-' + actionId);
    const pinError = $('#pin-error-' + actionId);
    const pinErrorMessage = $('#pin-error-message-' + actionId);
    const pinTimer = $('#pin-timer-' + actionId);
    const pinRegenerate = $('#pin-regenerate-' + actionId);
    const pinExpected = $('#pin-expected-' + actionId);
    const actionUrl = $('#pin-action-url-' + actionId);
    const actionMethod = $('#pin-action-method-' + actionId);
    const messageArea = $('#pin-message-area-' + actionId);
    const successMessage = $('#pin-success-message-' + actionId);
    const successText = $('#pin-success-text-' + actionId);
    const errorMessageArea = $('#pin-error-message-area-' + actionId);
    const errorText = $('#pin-error-text-' + actionId);
    const warningArea = $('#pin-warning-area-' + actionId);
    
    let timerInterval = null;
    let timeLeft = 60;
    let messageTimeout = null;
    
    // Generate 6-digit PIN
    function generatePIN() {
        return Math.floor(100000 + Math.random() * 900000).toString();
    }
    
    // Start timer
    function startTimer() {
        timeLeft = 60;
        pinTimer.text('{{ __('PIN expires in') }} ' + timeLeft + ' {{ __('seconds') }}');
        pinRegenerate.hide();
        
        if (timerInterval) {
            clearInterval(timerInterval);
        }
        
        timerInterval = setInterval(function() {
            timeLeft--;
            if (timeLeft > 0) {
                pinTimer.text('{{ __('PIN expires in') }} ' + timeLeft + ' {{ __('seconds') }}');
            } else {
                clearInterval(timerInterval);
                pinTimer.text('{{ __('PIN expired') }}');
                pinRegenerate.show();
                confirmBtn.prop('disabled', true);
            }
        }, 1000);
    }
    
    // Show message and auto-dismiss after 10 seconds (or close modal for success)
    function showMessage(type, message, shouldReload) {
        // Clear any existing timeout
        if (messageTimeout) {
            clearTimeout(messageTimeout);
        }
        
        // Hide warning area and show message area
        warningArea.hide();
        messageArea.show();
        
        if (type === 'success') {
            successMessage.show();
            errorMessageArea.hide();
            successText.text(message);
            
            // Close modal after 2 seconds for success messages
            setTimeout(function() {
                modal.modal('hide');
                
                // Reload page if needed (for delete operations)
                if (shouldReload) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 300); // Small delay to ensure modal is closed
                }
            }, 2000);
        } else {
            errorMessageArea.show();
            successMessage.hide();
            errorText.text(message);
            
            // Auto-dismiss error message after 10 seconds (but keep modal open)
            messageTimeout = setTimeout(function() {
                messageArea.fadeOut(500, function() {
                    warningArea.fadeIn(500);
                });
            }, 10000);
        }
    }
    
    // Initialize PIN when modal is shown
    modal.on('show.bs.modal', function() {
        const pin = generatePIN();
        pinExpected.val(pin);
        pinDisplay.text(pin); // Display the generated PIN
        pinInput.val('');
        pinError.hide();
        confirmBtn.prop('disabled', true);
        messageArea.hide();
        warningArea.show();
        if (messageTimeout) {
            clearTimeout(messageTimeout);
        }
        startTimer();
    });
    
    // Validate PIN input
    pinInput.on('input', function() {
        const value = $(this).val().replace(/\D/g, ''); // Only digits
        $(this).val(value);
        
        if (value.length === 6) {
            if (value === pinExpected.val()) {
                pinError.hide();
                confirmBtn.prop('disabled', false);
            } else {
                pinError.show();
                pinErrorMessage.text('{{ __('Invalid PIN. Please try again.') }}');
                confirmBtn.prop('disabled', true);
            }
        } else {
            pinError.hide();
            confirmBtn.prop('disabled', true);
        }
    });
    
    // Regenerate PIN
    pinRegenerate.on('click', function(e) {
        e.preventDefault();
        const pin = generatePIN();
        pinExpected.val(pin);
        pinDisplay.text(pin); // Display the new generated PIN
        pinInput.val('');
        pinError.hide();
        confirmBtn.prop('disabled', true);
        startTimer();
    });
    
    // Handle confirm button
    confirmBtn.on('click', function() {
        const pin = pinInput.val();
        const expectedPin = pinExpected.val();
        const url = actionUrl.val();
        const method = actionMethod.val() || 'POST';
        
        if (pin !== expectedPin) {
            pinError.show();
            pinErrorMessage.text('{{ __('Invalid PIN. Please try again.') }}');
            return;
        }
        
        if (!url) {
            console.error('Action URL not set');
            return;
        }
        
        // Disable button and show processing
        confirmBtn.prop('disabled', true);
        confirmBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>{{ __('Processing...') }}');
        
        // Handle GET requests (like clone) - redirect (modal will close on redirect)
        if (method === 'GET') {
            window.location.href = url;
            return;
        }
        
        // Handle POST/DELETE requests - use AJAX to keep modal open
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        if (method === 'DELETE') {
            formData.append('_method', 'DELETE');
        }
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (response.ok) {
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => ({ success: true, message: data.message || '{{ __('Action completed successfully') }}' }));
                } else {
                    // HTML response (redirect) - treat as success
                    return { success: true, message: '{{ __('Action completed successfully') }}' };
                }
            } else {
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || '{{ __('Action failed') }}');
                    });
                } else {
                    throw new Error('{{ __('Action failed') }}');
                }
            }
        })
        .then(data => {
            // Show success message (this will auto-close the modal after 2 seconds)
            // Reload page for DELETE operations to refresh the template list
            const shouldReload = (method === 'DELETE');
            showMessage('success', data.message, shouldReload);
            
            // Reset form
            pinInput.val('');
            confirmBtn.prop('disabled', true);
            confirmBtn.html('<i class="fas fa-check mr-2"></i>{{ __('Confirm') }}');
        })
        .catch(error => {
            // Show error message
            showMessage('error', error.message || '{{ __('An error occurred') }}');
            
            // Re-enable button
            confirmBtn.prop('disabled', false);
            confirmBtn.html('<i class="fas fa-check mr-2"></i>{{ __('Confirm') }}');
        });
    });
    
    // Reset modal when closed
    modal.on('hidden.bs.modal', function() {
        pinDisplay.text('------'); // Reset PIN display
        pinInput.val('');
        pinError.hide();
        confirmBtn.prop('disabled', true);
        confirmBtn.html('<i class="fas fa-check mr-2"></i>{{ __('Confirm') }}');
        messageArea.hide();
        warningArea.show();
        if (timerInterval) {
            clearInterval(timerInterval);
        }
        if (messageTimeout) {
            clearTimeout(messageTimeout);
        }
    });
})();
</script>
@endpush

