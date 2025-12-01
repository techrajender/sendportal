<div class="row">
    @foreach($templates as $template)
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div class="float-left">
                        {{ $template->name }}
                    </div>
                    <div class="float-right">
                        <a href="#"
                           class="btn btn-xs btn-light mr-1 clone-template-btn"
                           data-template-id="{{ $template->id }}"
                           data-template-name="{{ $template->name }}">
                            <i class="fa fa-copy mr-1"></i>{{ __('Clone') }}
                        </a>
                        <a href="{{ route('sendportal.templates.edit', $template->id) }}"
                           class="btn btn-xs btn-light">{{ __('Edit') }}</a>
                        @if ( ! $template->is_in_use)
                            <a href="#"
                               class="btn btn-xs btn-light delete-template-btn"
                               data-template-id="{{ $template->id }}"
                               data-template-name="{{ $template->name }}">
                                <i class="fa fa-trash mr-1"></i>{{ __('Delete') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @include('sendportal::templates.partials.griditem')
                </div>
            </div>
        </div>
    @endforeach
</div>

{{ $templates->links() }}
