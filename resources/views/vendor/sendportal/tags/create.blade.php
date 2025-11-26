@extends('sendportal::layouts.app')

@section('title', __('New Tag'))

@section('heading')
    {{ __('Tags') }}
@stop

@section('content')

    @component('sendportal::layouts.partials.card')
        @slot('cardHeader', __('Create Tag'))

        @slot('cardBody')
            <form action="{{ route('sendportal.tags.store') }}" method="POST" class="form-horizontal" id="tag-form">
                @csrf

                <div class="row">
                    <!-- Left Side: Tag Name -->
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="name">{{ __('Tag Name') }}</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="{{ __('Enter tag name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Side: Subscribers Selection -->
                    <div class="col-md-7">
                        <div class="form-group">
                            <label>{{ __('Select Subscribers') }}</label>
                            <input type="text" 
                                   class="form-control mb-2" 
                                   id="subscriber-search" 
                                   placeholder="{{ __('Search subscribers...') }}"
                                   autocomplete="off">
                            
                            <!-- Tabs for Selected/Not Selected -->
                            <ul class="nav nav-tabs mb-2" id="subscriberTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all-subscribers" role="tab" aria-controls="all-subscribers" aria-selected="true">
                                        {{ __('All') }} (<span id="all-count">{{ count($subscribers ?? []) }}</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="selected-tab" data-toggle="tab" href="#selected-subscribers" role="tab" aria-controls="selected-subscribers" aria-selected="false">
                                        {{ __('Selected') }} (<span id="selected-count">0</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="not-selected-tab" data-toggle="tab" href="#not-selected-subscribers" role="tab" aria-controls="not-selected-subscribers" aria-selected="false">
                                        {{ __('Not Selected') }} (<span id="not-selected-count">{{ count($subscribers ?? []) }}</span>)
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="subscriberTabsContent">
                                <!-- All Subscribers Tab -->
                                <div class="tab-pane fade show active" id="all-subscribers" role="tabpanel" aria-labelledby="all-tab">
                                    <div id="all-subscribers-list" style="max-height: 600px; min-height: 500px; overflow-y: auto; overflow-x: hidden; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                                        @forelse($subscribers ?? [] as $subscriber)
                                            <div class="checkbox subscriber-item" 
                                                 data-subscriber-id="{{ $subscriber->id }}"
                                                 data-subscriber-email="{{ strtolower($subscriber->email) }}"
                                                 data-subscriber-name="{{ strtolower(trim($subscriber->first_name . ' ' . $subscriber->last_name)) }}">
                                                <label>
                                                    <input type="checkbox" 
                                                           name="subscribers[]" 
                                                           value="{{ $subscriber->id }}" 
                                                           class="subscriber-checkbox">
                                                    <strong>{{ $subscriber->email }}</strong>
                                                    @if($subscriber->first_name || $subscriber->last_name)
                                                        <span class="text-muted">- {{ trim($subscriber->first_name . ' ' . $subscriber->last_name) }}</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @empty
                                            <p class="text-muted">{{ __('No subscribers available') }}</p>
                                        @endforelse
                                    </div>
                                </div>
                                
                                <!-- Selected Subscribers Tab -->
                                <div class="tab-pane fade" id="selected-subscribers" role="tabpanel" aria-labelledby="selected-tab">
                                    <div id="selected-subscribers-list" style="max-height: 600px; min-height: 500px; overflow-y: auto; overflow-x: hidden; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                                        <p class="text-muted">{{ __('No subscribers selected yet') }}</p>
                                    </div>
                                </div>
                                
                                <!-- Not Selected Subscribers Tab -->
                                <div class="tab-pane fade" id="not-selected-subscribers" role="tabpanel" aria-labelledby="not-selected-tab">
                                    <div id="not-selected-subscribers-list" style="max-height: 600px; min-height: 500px; overflow-y: auto; overflow-x: hidden; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
                                        <p class="text-muted">{{ __('All subscribers are selected') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <x-sendportal.submit-button :label="__('Save')" />
            </form>
        @endSlot
    @endcomponent

@push('js')
<script>
    (function() {
        // Subscriber search filter - works across all tabs
        var subscriberSearch = document.getElementById('subscriber-search');
        
        if (subscriberSearch) {
            subscriberSearch.addEventListener('keyup', function() {
                var searchTerm = this.value.toLowerCase();
                
                // Filter in all tabs
                var allTabs = ['all-subscribers-list', 'selected-subscribers-list', 'not-selected-subscribers-list'];
                
                allTabs.forEach(function(tabId) {
                    var tabList = document.getElementById(tabId);
                    if (tabList) {
                        var items = tabList.querySelectorAll('.subscriber-item');
                        items.forEach(function(item) {
                            var email = item.getAttribute('data-subscriber-email') || '';
                            var name = item.getAttribute('data-subscriber-name') || '';
                            var text = (email + ' ' + name).toLowerCase();
                            
                            if (text.indexOf(searchTerm) > -1 || searchTerm === '') {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }
                });
            });
        }
        
        // Update selected/not selected tabs when checkboxes change in "All" tab
        var allList = document.getElementById('all-subscribers-list');
        if (allList) {
            // Use event delegation to handle dynamically added checkboxes
            allList.addEventListener('change', function(e) {
                if (e.target && e.target.classList.contains('subscriber-checkbox')) {
                    console.log('Checkbox changed in All tab:', e.target.value, e.target.checked);
                    updateSubscriberTabs();
                }
            });
            
            // Also listen for click events as a fallback
            allList.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('subscriber-checkbox')) {
                    // Small delay to ensure checkbox state is updated
                    setTimeout(function() {
                        console.log('Checkbox clicked in All tab:', e.target.value, e.target.checked);
                        updateSubscriberTabs();
                    }, 10);
                }
            });
        }
        
        function updateSubscriberTabs() {
            var selectedList = document.getElementById('selected-subscribers-list');
            var notSelectedList = document.getElementById('not-selected-subscribers-list');
            var allList = document.getElementById('all-subscribers-list');
            var selectedCount = document.getElementById('selected-count');
            var notSelectedCount = document.getElementById('not-selected-count');
            var allCount = document.getElementById('all-count');
            
            if (!allList) {
                console.error('all-subscribers-list not found');
                return;
            }
            
            // Get all subscriber items from the "All" tab (source of truth)
            var allSubscriberItems = allList.querySelectorAll('.subscriber-item');
            
            var selectedItems = [];
            var notSelectedItems = [];
            
            allSubscriberItems.forEach(function(item) {
                var checkbox = item.querySelector('.subscriber-checkbox');
                var email = item.querySelector('strong').textContent;
                var name = item.querySelector('.text-muted') ? item.querySelector('.text-muted').textContent.replace('- ', '') : '';
                var subscriberId = item.getAttribute('data-subscriber-id');
                var isChecked = checkbox && checkbox.checked;
                
                // Create a clean HTML for the item (without name attribute for selected/not selected tabs to avoid duplicates)
                var itemHtml = '<label>' +
                    '<input type="checkbox" value="' + subscriberId + '" class="subscriber-checkbox"' + (isChecked ? ' checked' : '') + '> ' +
                    '<strong>' + email + '</strong>' +
                    (name ? ' <span class="text-muted">- ' + name + '</span>' : '') +
                    '</label>';
                
                if (isChecked) {
                    selectedItems.push({
                        id: subscriberId,
                        email: email,
                        name: name,
                        html: itemHtml
                    });
                } else {
                    notSelectedItems.push({
                        id: subscriberId,
                        email: email,
                        name: name,
                        html: itemHtml
                    });
                }
            });
            
            // Update counts - ensure they're updated
            if (selectedCount) {
                selectedCount.textContent = selectedItems.length;
            }
            if (notSelectedCount) {
                notSelectedCount.textContent = notSelectedItems.length;
            }
            if (allCount) {
                allCount.textContent = allSubscriberItems.length;
            }
            
            console.log('Counts updated:', {
                all: allSubscriberItems.length,
                selected: selectedItems.length,
                notSelected: notSelectedItems.length
            });
            
            // Update selected list (only show selected items)
            if (selectedList) {
                if (selectedItems.length > 0) {
                    var selectedHtml = '';
                    selectedItems.forEach(function(item) {
                        selectedHtml += '<div class="checkbox subscriber-item" data-subscriber-id="' + item.id + '" data-subscriber-email="' + item.email.toLowerCase() + '" data-subscriber-name="' + (item.name || '').toLowerCase() + '">' + item.html + '</div>';
                    });
                    selectedList.innerHTML = selectedHtml;
                    
                    // Re-attach event listeners using event delegation
                    selectedList.addEventListener('change', function(e) {
                        if (e.target && e.target.classList.contains('subscriber-checkbox')) {
                            // Update the checkbox in the "All" tab
                            var subscriberId = e.target.value;
                            var allCheckbox = allList.querySelector('.subscriber-item[data-subscriber-id="' + subscriberId + '"] .subscriber-checkbox');
                            if (allCheckbox) {
                                allCheckbox.checked = e.target.checked;
                                console.log('Updated All tab checkbox:', subscriberId, e.target.checked);
                            }
                            updateSubscriberTabs();
                        }
                    });
                } else {
                    selectedList.innerHTML = '<p class="text-muted">{{ __('No subscribers selected yet') }}</p>';
                }
            }
            
            // Update not selected list (only show not selected items)
            if (notSelectedList) {
                if (notSelectedItems.length > 0) {
                    var notSelectedHtml = '';
                    notSelectedItems.forEach(function(item) {
                        notSelectedHtml += '<div class="checkbox subscriber-item not-selected-item" data-subscriber-id="' + item.id + '" data-subscriber-email="' + item.email.toLowerCase() + '" data-subscriber-name="' + (item.name || '').toLowerCase() + '">' + item.html + '</div>';
                    });
                    notSelectedList.innerHTML = notSelectedHtml;
                    
                    // Re-attach event listeners using event delegation
                    notSelectedList.addEventListener('change', function(e) {
                        if (e.target && e.target.classList.contains('subscriber-checkbox')) {
                            // Update the checkbox in the "All" tab
                            var subscriberId = e.target.value;
                            var allCheckbox = allList.querySelector('.subscriber-item[data-subscriber-id="' + subscriberId + '"] .subscriber-checkbox');
                            if (allCheckbox) {
                                allCheckbox.checked = e.target.checked;
                                console.log('Updated All tab checkbox:', subscriberId, e.target.checked);
                            }
                            updateSubscriberTabs();
                        }
                    });
                } else {
                    notSelectedList.innerHTML = '<p class="text-muted">{{ __('All subscribers are selected') }}</p>';
                }
            }
        }
        
        // Initialize tabs on load - wait for DOM to be ready
        function initializeTabs() {
            // Wait a bit longer to ensure Bootstrap tabs are initialized
            setTimeout(function() {
                updateSubscriberTabs();
            }, 300);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeTabs);
        } else {
            initializeTabs();
        }
        
        // Also call when tabs are switched (Bootstrap tab event)
        if (typeof jQuery !== 'undefined') {
            jQuery('#subscriberTabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
                updateSubscriberTabs();
            });
        }
        
        // Ensure form submits correctly - only submit checkboxes from "All" tab
        var tagForm = document.getElementById('tag-form');
        if (tagForm) {
            tagForm.addEventListener('submit', function(e) {
                // Disable all checkboxes that are NOT in the "All" tab to prevent duplicates
                var allList = document.getElementById('all-subscribers-list');
                var allCheckboxes = allList ? allList.querySelectorAll('.subscriber-checkbox') : [];
                var allCheckboxIds = Array.from(allCheckboxes).map(function(cb) { return cb.value; });
                
                // Disable checkboxes in selected/not selected tabs
                var selectedList = document.getElementById('selected-subscribers-list');
                var notSelectedList = document.getElementById('not-selected-subscribers-list');
                
                [selectedList, notSelectedList].forEach(function(list) {
                    if (list) {
                        list.querySelectorAll('.subscriber-checkbox').forEach(function(cb) {
                            cb.disabled = true; // Disable so they're not submitted
                        });
                    }
                });
            });
        }
    })();
</script>
@endpush

@stop
