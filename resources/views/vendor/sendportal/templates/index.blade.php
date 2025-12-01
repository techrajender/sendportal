@extends('sendportal::layouts.app')

@section('title', __('Email Templates'))

@section('heading')
    {{ __('Email Templates') }}
@endsection

@section('content')

    @component('sendportal::layouts.partials.actions')
        @slot('right')
            <a class="btn btn-primary btn-md btn-flat" href="{{ route('sendportal.templates.create') }}">
                <i class="fa fa-plus mr-1"></i> {{ __('New Template') }}
            </a>
        @endslot
    @endcomponent

    @include('sendportal::templates.partials.grid')

    <!-- Clone PIN Confirmation Modal -->
    @include('components.pin-confirmation', [
        'actionId' => 'clone',
        'actionTitle' => __('Clone Template'),
        'actionMessage' => __('Are you sure you want to clone this template? A new template will be created with "-clone" suffix.')
    ])

    <!-- Delete PIN Confirmation Modal -->
    @include('components.pin-confirmation', [
        'actionId' => 'delete',
        'actionTitle' => __('Delete Template'),
        'actionMessage' => __('Are you sure you want to delete this template? This action cannot be undone.')
    ])

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle clone button clicks
    const cloneButtons = document.querySelectorAll('.clone-template-btn');
    cloneButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const templateId = this.getAttribute('data-template-id');
            const templateName = this.getAttribute('data-template-name');
            
            // Set action URL and message
            $('#pin-action-url-clone').val('{{ route("sendportal.templates.clone", ":id") }}'.replace(':id', templateId));
            $('#pin-action-method-clone').val('GET');
            $('#pin-action-message-clone').text('Are you sure you want to clone the template "' + templateName + '"? A new template will be created with "-clone" suffix.');
            
            // Show modal
            $('#pinConfirmModal-clone').modal('show');
        });
    });
    
    // Handle delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-template-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const templateId = this.getAttribute('data-template-id');
            const templateName = this.getAttribute('data-template-name');
            
            // Set action URL and message
            $('#pin-action-url-delete').val('{{ route("sendportal.templates.destroy", ":id") }}'.replace(':id', templateId));
            $('#pin-action-method-delete').val('DELETE');
            $('#pin-action-message-delete').text('Are you sure you want to delete the template "' + templateName + '"? This action cannot be undone.');
            
            // Show modal
            $('#pinConfirmModal-delete').modal('show');
        });
    });
});
</script>
@endpush
