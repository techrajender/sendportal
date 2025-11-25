@auth()
    <li class="nav-item {{ request()->is('tags*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('sendportal.tags.index') }}">
            <i class="fa-fw fas fa-tags mr-2"></i><span>{{ __('Tags') }}</span>
        </a>
    </li>
    
    @if (auth()->user()->ownsCurrentWorkspace())
        <li class="nav-item {{ request()->is('users*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('users.index') }}">
                <i class="fa-fw fas fa-users mr-2"></i><span>{{ __('Manage Users') }}</span>
            </a>
        </li>
    @endif
@endauth
