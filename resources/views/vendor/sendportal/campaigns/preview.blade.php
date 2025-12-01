@extends('sendportal::layouts.app')

@section('title', __('Confirm Campaign'))

@section('heading')
    {{ __('Preview Campaign') }}: {{ $campaign->name }}
@stop

@section('content')

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header card-header-accent">
                <div class="card-header-inner">
                    {{ __('Content') }}
                </div>
            </div>
            <div class="card-body">
                <form class="form-horizontal">
                    <div class="row">
                        <label class="col-sm-2 col-form-label">{{ __('From') }}:</label>
                        <div class="col-sm-10">
                            <b>
                                <span class="form-control-plaintext">{{ $campaign->from_name . ' <' . $campaign->from_email . '>' }}</span>
                            </b>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">{{ __('Subject') }}:</label>
                        <div class="col-sm-10">
                            <b>
                                <span class="form-control-plaintext">{{ $campaign->subject }}</span>
                            </b>
                        </div>
                    </div>

                    <div style="border: 1px solid #ddd; height: 600px">
                        <iframe id="js-template-iframe" srcdoc="{{ $campaign->merged_content }}" class="embed-responsive-item" frameborder="0" style="height: 100%; width: 100%"></iframe>
                    </div>

                </form>
            </div>
        </div>

    </div>

    <div class="col-md-4">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-3" id="campaignTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="test-email-tab" data-toggle="tab" href="#test-email" role="tab" aria-controls="test-email" aria-selected="true">
                    {{ __('Test Email') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="recipients-tab" data-toggle="tab" href="#recipients" role="tab" aria-controls="recipients" aria-selected="false">
                    {{ __('Recipients') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="sending-options-tab" data-toggle="tab" href="#sending-options" role="tab" aria-controls="sending-options" aria-selected="false">
                    {{ __('Sending Options') }}
                </a>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="campaignTabsContent">
            <!-- Test Email Tab -->
            <div class="tab-pane fade show active" id="test-email" role="tabpanel" aria-labelledby="test-email-tab">
                <form action="{{ route('sendportal.campaigns.test', $campaign->id) }}" method="POST">
                    @csrf
                    <div class="card mb-4">
                        <div class="card-header">
                            {{ __('Test Email') }}
                        </div>
                        <div class="card-body">
                            <div class="pb-2"><b>{{ __('RECIPIENT') }}</b></div>
                            <div class="form-group row form-group-schedule">
                                <div class="col-sm-12">
                                    <input name="recipient_email" id="test-email-recipient" type="email" class="form-control" placeholder="{{ __('Recipient email address') }}">
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Send Test Email') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recipients Tab (Combined Selected and Exclude) -->
            <div class="tab-pane fade" id="recipients" role="tabpanel" aria-labelledby="recipients-tab">
                <!-- Selected Recipients Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        {{ __('Selected Recipients') }}
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label><b>{{ __('RECIPIENTS') }}</b></label>
                            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons" id="recipients-toggle-group">
                                <label class="btn btn-outline-primary flex-fill {{ $campaign->send_to_all ? 'active' : '' }}" id="all-recipients-btn">
                                    <input type="radio" name="recipients_type" id="recipients-all" value="send_to_all" {{ $campaign->send_to_all ? 'checked' : '' }} autocomplete="off">
                                    {{ __('All Recipients') }} ({{ $subscriberCount ?? 0 }})
                                </label>
                                <label class="btn btn-outline-primary flex-fill {{ !$campaign->send_to_all ? 'active' : '' }}" id="tags-recipients-btn">
                                    <input type="radio" name="recipients_type" id="recipients-tags" value="send_to_tags" {{ !$campaign->send_to_all ? 'checked' : '' }} autocomplete="off">
                                    {{ __('Select Tags') }}
                                </label>
                            </div>
                        </div>

                        <div id="tags-selection-container" style="display: {{ $campaign->send_to_all ? 'none' : 'block' }};">
                            <p class="text-muted mt-2">{{ __('Select tags to view recipients who will receive this campaign.') }}</p>
                            
                            <div id="selected-tags-list" class="mb-3">
                                @if(!$campaign->send_to_all && $campaign->tags && $campaign->tags->count() > 0)
                                    @foreach($campaign->tags as $tag)
                                        <div class="badge badge-primary mr-2 mb-2 p-2" data-tag-id="{{ $tag->id }}">
                                            {{ $tag->name }} ({{ $tag->activeSubscribers()->count() }} {{ __('subscribers') }})
                                            <button type="button" class="btn btn-sm btn-link text-white ml-2 p-0" onclick="removeTag({{ $tag->id }})" style="font-size: 0.8rem;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted">{{ __('No tags selected yet.') }}</p>
                                @endif
                            </div>

                            <button type="button" class="btn btn-primary btn-sm" id="select-tags-btn" data-toggle="modal" data-target="#selectTagsModal" {{ $campaign->send_to_all ? 'disabled' : '' }}>
                                <i class="fas fa-plus"></i> {{ __('Select Tags') }}
                            </button>
                        </div>

                        <div id="recipients-container" class="mt-4" style="display: none;">
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">{{ __('Recipients') }} (<span id="recipients-count">0</span>)</h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-primary" id="show-all-recipients-btn" onclick="showAllRecipientsModal()" style="display: none;">
                                        <i class="fas fa-eye"></i> {{ __('Show More') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link" onclick="clearRecipients()">
                                        <i class="fas fa-times"></i> {{ __('Clear') }}
                                    </button>
                                </div>
                            </div>
                            <div id="recipients-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                <p class="text-muted text-center">{{ __('Select recipients option to view list') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exclude Recipients Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        {{ __('Exclude Recipients from Campaigns') }}
                    </div>
                    <div class="card-body">
                        <p class="text-muted">{{ __('Select campaigns to exclude recipients from. Recipients who received emails in selected campaigns will be excluded from this campaign.') }}</p>
                        
                        <div id="excluded-campaigns-list" class="mb-3">
                            @php
                                $excludedCampaignsArray = ($excludedCampaigns ?? [])->toArray();
                                $maxDisplay = 3; // Show only first 3 campaigns
                                $displayCampaigns = array_slice($excludedCampaignsArray, 0, $maxDisplay);
                                $remainingCount = count($excludedCampaignsArray) - $maxDisplay;
                            @endphp
                            
                            @if(count($excludedCampaignsArray) > 0)
                                @foreach($displayCampaigns as $excludedCampaign)
                                    <div class="badge badge-secondary mr-2 mb-2 p-2 d-inline-flex align-items-center" data-campaign-id="{{ $excludedCampaign['id'] }}" style="max-width: 100%;">
                                        <span class="text-truncate" style="max-width: 200px;" title="{{ $excludedCampaign['name'] }}">
                                            {{ $excludedCampaign['name'] }}
                                        </span>
                                        <button type="button" class="btn btn-sm btn-link text-white ml-2 p-0 flex-shrink-0" onclick="removeExcludedCampaign({{ $excludedCampaign['id'] }})" style="font-size: 0.8rem;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                                
                                @if($remainingCount > 0)
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-sm btn-link text-primary p-0" data-toggle="modal" data-target="#excludedCampaignsListModal">
                                            <i class="fas fa-ellipsis-h"></i> {{ __('Show') }} {{ $remainingCount }} {{ __('more') }}
                                        </button>
                                    </div>
                                @endif
                            @else
                                <p class="text-muted">{{ __('No campaigns excluded yet.') }}</p>
                            @endif
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#excludeCampaignsModal">
                            <i class="fas fa-plus"></i> {{ __('Select Campaigns to Exclude') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sending Options Tab -->
            <div class="tab-pane fade" id="sending-options" role="tabpanel" aria-labelledby="sending-options-tab">
                <form action="{{ route('sendportal.campaigns.send', $campaign->id) }}" method="POST" id="send-campaign-form">
                    @csrf
                    @method('PUT')
                    
                    <!-- Hidden fields for recipients and tags (will be updated by JavaScript) -->
                    <input type="hidden" name="recipients" id="hidden-recipients" value="{{ $campaign->send_to_all ? 'send_to_all' : 'send_to_tags' }}">
                    <div id="hidden-tags-container">
                        @if(!$campaign->send_to_all && $campaign->tags)
                            @foreach($campaign->tags as $tag)
                                <input type="hidden" name="tags[]" value="{{ $tag->id }}">
                            @endforeach
                        @endif
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            {{ __('Sending options') }}
                        </div>
                        <div class="card-body">

                    <div class="pb-2"><b>{{ __('SCHEDULE') }}</b></div>
                    <div class="form-group row form-group-schedule">
                        <div class="col-sm-12">
                            <select id="id-field-schedule" class="form-control" name="schedule">
                                <option value="now" {{ old('schedule') === 'now' || is_null($campaign->scheduled_at) ? 'selected' : '' }}>
                                    {{ __('Dispatch now') }}
                                </option>
                                <option value="scheduled" {{ old('schedule') === 'now' || $campaign->scheduled_at ? 'selected' : '' }}>
                                    {{ __('Dispatch at a specific time') }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <input id="input-field-scheduled_at" class="form-control hide mb-3" name="scheduled_at" type="text" value="{{ $campaign->scheduled_at ?: now() }}">

                    <div class="pb-2"><b>{{ __('SENDING BEHAVIOUR') }}</b></div>
                    <div class="form-group row form-group-schedule">
                        <div class="col-sm-12">
                            <select id="id-field-behaviour" class="form-control" name="behaviour">
                                <option value="draft">{{ __('Queue draft') }}</option>
                                <option value="auto">{{ __('Send automatically') }}</option>
                            </select>
                        </div>
                    </div>

                        <div>
                            <a href="{{ route('sendportal.campaigns.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Send campaign') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exclude Campaigns Modal -->
    <div class="modal fade" id="excludeCampaignsModal" tabindex="-1" role="dialog" aria-labelledby="excludeCampaignsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excludeCampaignsModalLabel">{{ __('Select Campaigns to Exclude') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="campaign-search">{{ __('Search Campaigns') }}</label>
                        <input type="text" class="form-control" id="campaign-search" placeholder="{{ __('Type to search...') }}">
                    </div>
                    <div class="form-group">
                        <div id="campaigns-list" style="max-height: 400px; overflow-y: auto;">
                            @php
                                $availableCampaigns = $excludeCampaigns ?? collect();
                            @endphp
                            @forelse($availableCampaigns as $excludeCampaign)
                                <div class="checkbox campaign-item" data-campaign-id="{{ $excludeCampaign->id }}" data-campaign-name="{{ strtolower($excludeCampaign->name) }}">
                                    <label>
                                        <input type="checkbox" value="{{ $excludeCampaign->id }}" 
                                               {{ in_array($excludeCampaign->id, ($excludedCampaignIds ?? [])) ? 'checked' : '' }}>
                                        {{ $excludeCampaign->name }}
                                        <small class="text-muted">({{ $excludeCampaign->sent_count ?? 0 }} {{ __('sent') }})</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">{{ __('No other campaigns available') }}</p>
                                @if(config('app.debug'))
                                    <p class="text-muted small">Debug: excludeCampaigns count = {{ $availableCampaigns->count() }}</p>
                                @endif
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="saveExcludedCampaigns()">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Select Tags Modal -->
    <div class="modal fade" id="selectTagsModal" tabindex="-1" role="dialog" aria-labelledby="selectTagsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectTagsModalLabel">{{ __('Select Tags') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tag-search">{{ __('Search Tags') }}</label>
                        <input type="text" class="form-control" id="tag-search" placeholder="{{ __('Type to search...') }}">
                    </div>
                    <div class="form-group">
                        <div id="tags-list" style="max-height: 400px; overflow-y: auto;">
                            @forelse($tags ?? [] as $tag)
                                <div class="checkbox tag-item" data-tag-id="{{ $tag->id }}" data-tag-name="{{ strtolower($tag->name) }}">
                                    <label>
                                        <input type="checkbox" value="{{ $tag->id }}" class="tag-checkbox">
                                        {{ $tag->name }}
                                        <small class="text-muted">({{ $tag->activeSubscribers()->count() }} {{ __('subscribers') }})</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">{{ __('No tags available') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="loadRecipients()">{{ __('View Recipients') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Excluded Campaigns List Modal -->
    <div class="modal fade" id="excludedCampaignsListModal" tabindex="-1" role="dialog" aria-labelledby="excludedCampaignsListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excludedCampaignsListModalLabel">{{ __('Excluded Campaigns') }} (<span id="excluded-campaigns-modal-count">{{ count($excludedCampaigns ?? []) }}</span>)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="excluded-campaigns-modal-list" style="max-height: 500px; overflow-y: auto;">
                        @forelse($excludedCampaigns ?? [] as $excludedCampaign)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2 mb-2" data-campaign-id="{{ $excludedCampaign->id }}">
                                <div class="flex-grow-1">
                                    <strong class="d-block">{{ $excludedCampaign->name }}</strong>
                                    <small class="text-muted">{{ __('Created') }}: {{ $excludedCampaign->created_at->format('Y-m-d H:i:s') }}</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger ml-2" onclick="removeExcludedCampaign({{ $excludedCampaign->id }})">
                                    <i class="fas fa-times"></i> {{ __('Remove') }}
                                </button>
                            </div>
                        @empty
                            <p class="text-muted text-center">{{ __('No campaigns excluded yet.') }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- All Recipients Modal -->
    <div class="modal fade" id="allRecipientsModal" tabindex="-1" role="dialog" aria-labelledby="allRecipientsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allRecipientsModalLabel">{{ __('All Recipients') }} (<span id="modal-recipients-count">0</span>)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <input type="text" class="form-control" id="recipient-search" placeholder="{{ __('Search by email or name...') }}">
                    </div>
                    <div id="modal-recipients-list" style="max-height: 500px; overflow-y: auto;">
                        <p class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}</p>
                    </div>
                    <div id="modal-pagination" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>

@stop

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        var element = $('#input-field-scheduled_at');
        $('#id-field-schedule').change(function() {
            if (this.value == 'now') {
                element.addClass('hide');
            } else {
                element.removeClass('hide');
            }
        });

        $('#input-field-scheduled_at').flatpickr({
            enableTime: true,
            time_24hr: true,
            dateFormat: "Y-m-d H:i",
        });

        // Campaign search filter
        $('#campaign-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.campaign-item').filter(function() {
                $(this).toggle($(this).data('campaign-name').indexOf(value) > -1);
            });
        });

        // Sync modal checkboxes with currently excluded campaigns when modal opens
        $('#excludeCampaignsModal').on('show.bs.modal', function() {
            var excludedCampaignIds = [];
            $('#excluded-campaigns-list .badge[data-campaign-id]').each(function() {
                excludedCampaignIds.push($(this).data('campaign-id'));
            });
            
            // Uncheck all first
            $('#campaigns-list input[type="checkbox"]').prop('checked', false);
            
            // Check the excluded ones
            excludedCampaignIds.forEach(function(campaignId) {
                $('#campaigns-list input[value="' + campaignId + '"]').prop('checked', true);
            });
        });

        // Save excluded campaigns
        function saveExcludedCampaigns() {
            var selectedCampaigns = [];
            var selectedCampaignData = [];
            $('#campaigns-list input[type="checkbox"]:checked').each(function() {
                var campaignId = $(this).val();
                var $label = $(this).closest('label');
                var campaignName = $label.clone().children('small').remove().end().text().trim();
                selectedCampaigns.push(campaignId);
                selectedCampaignData.push({
                    id: campaignId,
                    name: campaignName
                });
            });

            $.ajax({
                url: '{{ route("sendportal.campaigns.exclusions.store", $campaign->id) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    excluded_campaign_ids: selectedCampaigns
                },
                success: function(response) {
                    // Update the excluded campaigns list without reloading
                    // Convert to array format with id and name
                    var campaignData = selectedCampaignData.map(function(c) {
                        return {id: c.id, name: c.name};
                    });
                    updateExcludedCampaignsList(campaignData);
                    $('#excludeCampaignsModal').modal('hide');
                    
                    // Reload recipients if they are currently displayed
                    var recipientsType = $('input[name="recipients_type"]:checked').val();
                    if (recipientsType === 'send_to_all' && $('#recipients-container').is(':visible')) {
                        loadAllRecipients();
                    } else if (recipientsType === 'send_to_tags' && $('#recipients-container').is(':visible')) {
                        var selectedTagIds = [];
                        $('#tags-list input.tag-checkbox:checked').each(function() {
                            selectedTagIds.push($(this).val());
                        });
                        if (selectedTagIds.length > 0) {
                            // Temporarily set checkboxes and reload
                            var tempCheckboxes = $('#tags-list input.tag-checkbox');
                            tempCheckboxes.prop('checked', false);
                            selectedTagIds.forEach(function(id) {
                                $('#tags-list input.tag-checkbox[value="' + id + '"]').prop('checked', true);
                            });
                            loadRecipients();
                        }
                    }
                },
                error: function(xhr) {
                    alert('Error saving exclusions: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }

        // Remove excluded campaign
        function removeExcludedCampaign(campaignId) {
            $.ajax({
                url: '{{ route("sendportal.campaigns.exclusions.destroy", $campaign->id) }}',
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    excluded_campaign_id: campaignId
                },
                success: function(response) {
                    // Get all remaining excluded campaigns
                    var remainingCampaigns = [];
                    $('#excluded-campaigns-list .badge[data-campaign-id]').each(function() {
                        var id = $(this).data('campaign-id');
                        if (id != campaignId) {
                            var name = $(this).find('span').text().trim();
                            remainingCampaigns.push({id: id, name: name});
                        }
                    });
                    
                    // Also check modal for any we might have missed
                    $('#excluded-campaigns-modal-list [data-campaign-id]').each(function() {
                        var id = $(this).data('campaign-id');
                        if (id != campaignId) {
                            var exists = remainingCampaigns.find(function(c) { return c.id == id; });
                            if (!exists) {
                                var name = $(this).find('strong').text().trim();
                                remainingCampaigns.push({id: id, name: name});
                            }
                        }
                    });
                    
                    // Update the UI with remaining campaigns
                    updateExcludedCampaignsList(remainingCampaigns);
                    
                    // Uncheck in modal if it's open
                    $('#campaigns-list input[value="' + campaignId + '"]').prop('checked', false);
                    
                    // Reload recipients if they are currently displayed
                    var recipientsType = $('input[name="recipients_type"]:checked').val();
                    if (recipientsType === 'send_to_all' && $('#recipients-container').is(':visible')) {
                        loadAllRecipients();
                    } else if (recipientsType === 'send_to_tags' && $('#recipients-container').is(':visible')) {
                        var selectedTagIds = [];
                        $('#tags-list input.tag-checkbox:checked').each(function() {
                            selectedTagIds.push($(this).val());
                        });
                        if (selectedTagIds.length > 0) {
                            // Temporarily set checkboxes and reload
                            var tempCheckboxes = $('#tags-list input.tag-checkbox');
                            tempCheckboxes.prop('checked', false);
                            selectedTagIds.forEach(function(id) {
                                $('#tags-list input.tag-checkbox[value="' + id + '"]').prop('checked', true);
                            });
                            loadRecipients();
                        }
                    }
                },
                error: function(xhr) {
                    alert('Error removing exclusion: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }

        // Update excluded campaigns list in UI
        function updateExcludedCampaignsList(campaignData) {
            var maxDisplay = 3;
            var displayCampaigns = campaignData.slice(0, maxDisplay);
            var remainingCount = campaignData.length - maxDisplay;
            
            var html = '';
            if (campaignData.length > 0) {
                // Display first few campaigns
                displayCampaigns.forEach(function(campaign) {
                    html += '<div class="badge badge-secondary mr-2 mb-2 p-2 d-inline-flex align-items-center" data-campaign-id="' + campaign.id + '" style="max-width: 100%;">';
                    html += '<span class="text-truncate" style="max-width: 200px;" title="' + escapeHtml(campaign.name) + '">';
                    html += escapeHtml(campaign.name);
                    html += '</span>';
                    html += '<button type="button" class="btn btn-sm btn-link text-white ml-2 p-0 flex-shrink-0" onclick="removeExcludedCampaign(' + campaign.id + ')" style="font-size: 0.8rem;">';
                    html += '<i class="fas fa-times"></i>';
                    html += '</button>';
                    html += '</div>';
                });
                
                // Show "more" button if there are more campaigns
                if (remainingCount > 0) {
                    html += '<div class="mb-2">';
                    html += '<button type="button" class="btn btn-sm btn-link text-primary p-0" data-toggle="modal" data-target="#excludedCampaignsListModal">';
                    html += '<i class="fas fa-ellipsis-h"></i> {{ __('Show') }} ' + remainingCount + ' {{ __('more') }}';
                    html += '</button>';
                    html += '</div>';
                }
            } else {
                html = '<p class="text-muted">{{ __('No campaigns excluded yet.') }}</p>';
            }
            $('#excluded-campaigns-list').html(html);
            
            // Update modal list
            updateExcludedCampaignsModalList(campaignData);
        }
        
        // Update excluded campaigns modal list
        function updateExcludedCampaignsModalList(campaignData) {
            var html = '';
            var modalCount = campaignData.length;
            
            if (campaignData.length > 0) {
                campaignData.forEach(function(campaign) {
                    html += '<div class="d-flex justify-content-between align-items-center border-bottom py-2 mb-2" data-campaign-id="' + campaign.id + '">';
                    html += '<div class="flex-grow-1">';
                    html += '<strong class="d-block">' + escapeHtml(campaign.name) + '</strong>';
                    html += '<small class="text-muted">{{ __('ID') }}: ' + campaign.id + '</small>';
                    html += '</div>';
                    html += '<button type="button" class="btn btn-sm btn-link text-danger ml-2" onclick="removeExcludedCampaign(' + campaign.id + ')">';
                    html += '<i class="fas fa-times"></i> {{ __('Remove') }}';
                    html += '</button>';
                    html += '</div>';
                });
            } else {
                html = '<p class="text-muted text-center">{{ __('No campaigns excluded yet.') }}</p>';
            }
            
            $('#excluded-campaigns-modal-list').html(html);
            $('#excluded-campaigns-modal-count').text(modalCount);
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Tag search filter
        $('#tag-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.tag-item').filter(function() {
                $(this).toggle($(this).data('tag-name').indexOf(value) > -1);
            });
        });

        // Load all recipients (when "All Recipients" is selected)
        function loadAllRecipients() {
            // Show loading
            $('#recipients-list').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading recipients...') }}</p>');
            $('#recipients-container').show();

            $.ajax({
                url: '{{ route("sendportal.campaigns.recipients.get", $campaign->id) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    recipients_type: 'send_to_all',
                    tag_ids: []
                },
                success: function(response) {
                    if (response.success) {
                        displayRecipients(response.recipients, response.total_count);
                    } else {
                        alert('Error: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    alert('Error loading recipients: ' + (xhr.responseJSON?.error || 'Unknown error'));
                    $('#recipients-list').html('<p class="text-muted text-center">{{ __('Error loading recipients') }}</p>');
                }
            });
        }

        // Load recipients for selected tags
        function loadRecipients() {
            var selectedTagIds = [];
            $('#tags-list input.tag-checkbox:checked').each(function() {
                selectedTagIds.push($(this).val());
            });

            if (selectedTagIds.length === 0) {
                alert('{{ __('Please select at least one tag') }}');
                return;
            }

            // Update hidden tags in form
            updateHiddenTags(selectedTagIds);

            // Show loading
            $('#recipients-list').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading recipients...') }}</p>');
            $('#recipients-container').show();

            $.ajax({
                url: '{{ route("sendportal.campaigns.recipients.get", $campaign->id) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    recipients_type: 'send_to_tags',
                    tag_ids: selectedTagIds
                },
                success: function(response) {
                    if (response.success) {
                        displayRecipients(response.recipients, response.total_count);
                        updateSelectedTags(selectedTagIds);
                        $('#selectTagsModal').modal('hide');
                    } else {
                        alert('Error: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    alert('Error loading recipients: ' + (xhr.responseJSON?.error || 'Unknown error'));
                    $('#recipients-list').html('<p class="text-muted text-center">{{ __('Error loading recipients') }}</p>');
                }
            });
        }

        // Update hidden tags in the send form
        function updateHiddenTags(tagIds) {
            var container = $('#hidden-tags-container');
            container.empty();
            tagIds.forEach(function(tagId) {
                container.append('<input type="hidden" name="tags[]" value="' + tagId + '">');
            });
        }

        // Store all recipients globally for modal
        var allRecipientsData = [];

        // Display recipients in the list (show only first 3)
        function displayRecipients(recipients, totalCount) {
            allRecipientsData = recipients; // Store all recipients
            $('#recipients-count').text(totalCount);
            
            if (recipients.length === 0) {
                $('#recipients-list').html('<p class="text-muted text-center">{{ __('No recipients found for selected tags') }}</p>');
                $('#show-all-recipients-btn').hide();
                return;
            }

            // Show only first 3 recipients
            var displayCount = Math.min(3, recipients.length);
            var displayRecipients = recipients.slice(0, displayCount);

            var html = '<table class="table table-sm table-hover">';
            html += '<thead><tr><th>{{ __('Email') }}</th><th>{{ __('Name') }}</th></tr></thead>';
            html += '<tbody>';
            
            displayRecipients.forEach(function(recipient) {
                html += '<tr>';
                html += '<td>' + (recipient.email || '') + '</td>';
                html += '<td>' + (recipient.first_name || '') + ' ' + (recipient.last_name || '') + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            // Show "Show More" button if there are more than 3 recipients
            if (recipients.length > 3) {
                html += '<p class="text-center mt-2"><small class="text-muted">{{ __('Showing') }} ' + displayCount + ' {{ __('of') }} ' + totalCount + ' {{ __('recipients') }}</small></p>';
                $('#show-all-recipients-btn').show();
            } else {
                $('#show-all-recipients-btn').hide();
            }
            
            $('#recipients-list').html(html);
        }

        // Show all recipients in modal with pagination
        function showAllRecipientsModal() {
            $('#modal-recipients-count').text(allRecipientsData.length);
            $('#allRecipientsModal').modal('show');
            displayModalRecipients(allRecipientsData, 1);
        }

        // Display recipients in modal with pagination
        function displayModalRecipients(recipients, page) {
            var perPage = 20;
            var totalPages = Math.ceil(recipients.length / perPage);
            var start = (page - 1) * perPage;
            var end = start + perPage;
            var pageRecipients = recipients.slice(start, end);

            var html = '<table class="table table-sm table-hover">';
            html += '<thead><tr><th>{{ __('Email') }}</th><th>{{ __('Name') }}</th></tr></thead>';
            html += '<tbody>';
            
            if (pageRecipients.length === 0) {
                html += '<tr><td colspan="2" class="text-center text-muted">{{ __('No recipients found') }}</td></tr>';
            } else {
                pageRecipients.forEach(function(recipient) {
                    html += '<tr class="recipient-row">';
                    html += '<td class="recipient-email">' + (recipient.email || '') + '</td>';
                    html += '<td class="recipient-name">' + (recipient.first_name || '') + ' ' + (recipient.last_name || '') + '</td>';
                    html += '</tr>';
                });
            }
            
            html += '</tbody></table>';
            $('#modal-recipients-list').html(html);

            // Pagination
            if (totalPages > 1) {
                var paginationHtml = '<nav><ul class="pagination justify-content-center">';
                
                // Previous button
                if (page > 1) {
                    paginationHtml += '<li class="page-item"><a class="page-link" href="#" onclick="displayModalRecipients(allRecipientsData, ' + (page - 1) + '); return false;">{{ __('Previous') }}</a></li>';
                } else {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">{{ __('Previous') }}</span></li>';
                }
                
                // Page numbers
                for (var i = 1; i <= totalPages; i++) {
                    if (i === page) {
                        paginationHtml += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
                    } else if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                        paginationHtml += '<li class="page-item"><a class="page-link" href="#" onclick="displayModalRecipients(allRecipientsData, ' + i + '); return false;">' + i + '</a></li>';
                    } else if (i === page - 3 || i === page + 3) {
                        paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                // Next button
                if (page < totalPages) {
                    paginationHtml += '<li class="page-item"><a class="page-link" href="#" onclick="displayModalRecipients(allRecipientsData, ' + (page + 1) + '); return false;">{{ __('Next') }}</a></li>';
                } else {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">{{ __('Next') }}</span></li>';
                }
                
                paginationHtml += '</ul></nav>';
                $('#modal-pagination').html(paginationHtml);
            } else {
                $('#modal-pagination').html('');
            }
        }

        // Search recipients in modal
        $(document).on('keyup', '#recipient-search', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm === '') {
                displayModalRecipients(allRecipientsData, 1);
                return;
            }
            
            var filteredRecipients = allRecipientsData.filter(function(recipient) {
                var email = (recipient.email || '').toLowerCase();
                var firstName = (recipient.first_name || '').toLowerCase();
                var lastName = (recipient.last_name || '').toLowerCase();
                var fullName = (firstName + ' ' + lastName).trim();
                
                return email.indexOf(searchTerm) > -1 || fullName.indexOf(searchTerm) > -1;
            });
            
            displayModalRecipients(filteredRecipients, 1);
        });

        // Clear search when modal is closed
        $('#allRecipientsModal').on('hidden.bs.modal', function() {
            $('#recipient-search').val('');
        });

        // Update selected tags display
        function updateSelectedTags(selectedTagIds) {
            var selectedTags = [];
            selectedTagIds.forEach(function(tagId) {
                var tagItem = $('.tag-item[data-tag-id="' + tagId + '"]');
                var tagName = tagItem.find('label').text().trim();
                var subscriberCount = tagItem.find('small').text();
                selectedTags.push({
                    id: tagId,
                    name: tagName.replace(subscriberCount, '').trim(),
                    count: subscriberCount
                });
            });

            var html = '';
            if (selectedTags.length > 0) {
                selectedTags.forEach(function(tag) {
                    html += '<div class="badge badge-primary mr-2 mb-2 p-2" data-tag-id="' + tag.id + '">';
                    html += tag.name + ' ' + tag.count;
                    html += '<button type="button" class="btn btn-sm btn-link text-white ml-2 p-0" onclick="removeSelectedTag(' + tag.id + ')" style="font-size: 0.8rem;">';
                    html += '<i class="fas fa-times"></i>';
                    html += '</button>';
                    html += '</div>';
                });
            } else {
                html = '<p class="text-muted">{{ __('No tags selected yet.') }}</p>';
            }
            
            $('#selected-tags-list').html(html);
        }

        // Remove selected tag
        function removeSelectedTag(tagId) {
            $('#tags-list input.tag-checkbox[value="' + tagId + '"]').prop('checked', false);
            $('.badge[data-tag-id="' + tagId + '"]').remove();
            
            // Update hidden tags
            var remainingTagIds = [];
            $('#tags-list input.tag-checkbox:checked').each(function() {
                remainingTagIds.push($(this).val());
            });
            updateHiddenTags(remainingTagIds);
            
            // Reload recipients if there are still selected tags
            if (remainingTagIds.length > 0) {
                // Temporarily set checkboxes and reload
                var tempCheckboxes = $('#tags-list input.tag-checkbox');
                tempCheckboxes.prop('checked', false);
                remainingTagIds.forEach(function(id) {
                    $('#tags-list input.tag-checkbox[value="' + id + '"]').prop('checked', true);
                });
                loadRecipients();
            } else {
                $('#recipients-container').hide();
                $('#selected-tags-list').html('<p class="text-muted">{{ __('No tags selected yet.') }}</p>');
            }
        }

        // Clear all recipients
        function clearRecipients() {
            $('#tags-list input.tag-checkbox').prop('checked', false);
            $('#selected-tags-list').html('<p class="text-muted">{{ __('No tags selected yet.') }}</p>');
            $('#recipients-container').hide();
            updateHiddenTags([]);
        }

        // Handle recipients type toggle
        $('input[name="recipients_type"]').on('change', function() {
            var selectedType = $(this).val();
            
            if (selectedType === 'send_to_all') {
                // Disable Select Tags button and hide tags container
                $('#select-tags-btn').prop('disabled', true);
                $('#tags-selection-container').hide();
                // Clear selected tags
                $('#tags-list input.tag-checkbox').prop('checked', false);
                $('#selected-tags-list').html('<p class="text-muted">{{ __('No tags selected yet.') }}</p>');
                updateHiddenTags([]);
                // Load all recipients automatically
                loadAllRecipients();
            } else if (selectedType === 'send_to_tags') {
                // Enable Select Tags button and show tags container
                $('#select-tags-btn').prop('disabled', false);
                $('#tags-selection-container').show();
                // Hide recipients if no tags selected
                if ($('#selected-tags-list .badge').length === 0) {
                    $('#recipients-container').hide();
                } else {
                    // If tags are already selected, reload recipients
                    var selectedTagIds = [];
                    $('#selected-tags-list .badge').each(function() {
                        selectedTagIds.push($(this).data('tag-id'));
                    });
                    if (selectedTagIds.length > 0) {
                        // Set checkboxes and reload
                        $('#tags-list input.tag-checkbox').prop('checked', false);
                        selectedTagIds.forEach(function(id) {
                            $('#tags-list input.tag-checkbox[value="' + id + '"]').prop('checked', true);
                        });
                        loadRecipients();
                    }
                }
            }
            
            // Update hidden form field
            $('#hidden-recipients').val(selectedType);
        });

        // Remove tag function
        function removeTag(tagId) {
            removeSelectedTag(tagId);
        }

        // Initialize: Load recipients if "All Recipients" is selected
        $(document).ready(function() {
            var selectedType = $('input[name="recipients_type"]:checked').val();
            if (selectedType === 'send_to_all') {
                loadAllRecipients();
            }
        });
    </script>
@endpush
