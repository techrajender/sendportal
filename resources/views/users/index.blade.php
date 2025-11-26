@extends('sendportal::layouts.app')

@section('heading')
    {{ __('Workspace Members') }}
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="usersTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="current-users-tab" data-toggle="tab" href="#current-users" role="tab" aria-controls="current-users" aria-selected="true">
                                {{ __('Current Users') }} 
                                <span class="badge badge-secondary ml-1">{{ $users->total() }}</span>
                            </a>
                        </li>
                        @if (auth()->user()->ownsCurrentWorkspace())
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="invited-users-tab" data-toggle="tab" href="#invited-users" role="tab" aria-controls="invited-users" aria-selected="false">
                                    {{ __('Invited Users') }}
                                    <span class="badge badge-secondary ml-1">{{ $invitations->total() }}</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="invite-user-tab" data-toggle="tab" href="#invite-user" role="tab" aria-controls="invite-user" aria-selected="false">
                                    {{ __('Invite User') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="usersTabsContent">
                        <!-- Current Users Tab -->
                        <div class="tab-pane fade show active" id="current-users" role="tabpanel" aria-labelledby="current-users-tab">
                            <div class="card-table table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('Role') }}</th>
                                        <th>{{ __('Date Joined') }}</th>
                                        <th>{{ __('Account Created') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @forelse ($users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge badge-{{ $user->pivot->role === 'owner' ? 'primary' : 'secondary' }}">
                                                    {{ ucwords($user->pivot->role) }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $user->pivot->created_at ? $user->pivot->created_at->format('Y-m-d H:i') : 'N/A' }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : 'N/A' }}
                                                </small>
                                            </td>
                                            <td>
                                                @if (auth()->user()->ownsCurrentWorkspace())
                                                    @if ($user->id === auth()->user()->id)
                                                        <button
                                                            class="btn btn-sm btn-light"
                                                            disabled
                                                            title="{{ __('You cannot edit or remove yourself.') }}"
                                                        >
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    @else
                                                        <div class="btn-group" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-primary" 
                                                                    onclick="editUser({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->pivot->role }}')"
                                                                    title="{{ __('Edit User') }}">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-warning" 
                                                                    onclick="resetPassword({{ $user->id }}, '{{ $user->name }}')"
                                                                    title="{{ __('Reset Password') }}">
                                                                <i class="fas fa-key"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger" 
                                                                    onclick="confirmDeleteUser({{ $user->id }}, '{{ $user->name }}')"
                                                                    title="{{ __('Remove User') }}">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <p class="empty-table-text">{{ __('No users found.') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($users->hasPages())
                                <div class="mt-3">
                                    {{ $users->links() }}
                                </div>
                            @endif
                        </div>

                        <!-- Invited Users Tab -->
                        @if (auth()->user()->ownsCurrentWorkspace())
                            <div class="tab-pane fade" id="invited-users" role="tabpanel" aria-labelledby="invited-users-tab">
                                <div class="card-table table-responsive">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>{{ __('Email') }}</th>
                                            <th>{{ __('Expires') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        @forelse ($invitations as $invitation)
                                            <tr>
                                                <td>{{ $invitation->email }}</td>
                                                <td>{{ $invitation->expires_at->format('Y-m-d') }}</td>
                                                <td class="td-fit">
                                                    <form
                                                        action="{{ route('users.invitations.destroy', $invitation) }}"
                                                        method="post" class="d-inline">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit"
                                                                class="btn btn-sm btn-light">{{ __('Retract') }}</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">
                                                    <p class="empty-table-text">{{ __('No invitations found.') }}</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($invitations->hasPages())
                                    <div class="mt-3">
                                        {{ $invitations->links() }}
                                    </div>
                                @endif
                            </div>

                            <!-- Invite User Tab -->
                            <div class="tab-pane fade" id="invite-user" role="tabpanel" aria-labelledby="invite-user-tab">
                                <div class="card-body p-0">
                                    @if(config('sendportal-host.auth.register'))
                                        <form action="{{ route('users.invitations.store') }}" method="post">
                                            @csrf
                                            <div class="form-group row">
                                                <label for="create-invitation-email" class="col-sm-3 col-form-label">{{ __('Email Address') }}</label>
                                                <div class="col-sm-9">
                                                    <input type="email" id="create-invitation-email" class="form-control" name="email" required>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="offset-sm-3 col-sm-9">
                                                    <input type="submit" class="btn btn-md btn-primary" value="{{ __('Send Invite') }}">
                                                </div>
                                            </div>
                                        </form>
                                    @else
                                        <p class="empty-table-text">{{ __('In order to invite users, you have to enable registration in the Sendportal configuration file.') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PIN Confirmation for Delete User -->
    @if (auth()->user()->ownsCurrentWorkspace())
        @include('components.pin-confirmation', [
            'actionId' => 'delete-user',
            'actionTitle' => __('Delete User'),
            'actionMessage' => __('Are you sure you want to remove this user from the workspace? This action cannot be undone.')
        ])
    @endif

    <!-- Right Side Drawer for Edit User and Reset Password -->
    @if (auth()->user()->ownsCurrentWorkspace())
    <div class="user-drawer" id="userDrawer">
        <div class="user-drawer-overlay" onclick="closeUserDrawer()"></div>
        <div class="user-drawer-content">
            <div class="user-drawer-header">
                <h5 class="mb-0" id="userDrawerTitle">{{ __('Edit User') }}</h5>
                <button type="button" class="btn btn-sm btn-link text-muted" onclick="closeUserDrawer(event)" title="{{ __('Close') }}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="user-drawer-body" id="userDrawerBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
    @endif

    <style>
        .user-drawer {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1050;
        }
        .user-drawer.active {
            display: block;
        }
        .user-drawer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        .user-drawer-content {
            position: absolute;
            right: 0;
            top: 0;
            width: 450px;
            max-width: 90%;
            height: 100%;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(0);
            }
        }
        .user-drawer-header {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        .user-drawer-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .user-drawer-body .form-group {
            margin-bottom: 1.25rem;
        }
    </style>

@endsection

@push('js')
<script>
    function editUser(userId, userName, userEmail, userRole) {
        const drawer = document.getElementById('userDrawer');
        const drawerBody = document.getElementById('userDrawerBody');
        const drawerTitle = document.getElementById('userDrawerTitle');
        
        drawerTitle.textContent = '{{ __("Edit User") }}';
        
        const updateUrl = '{{ route("users.update", ":id") }}'.replace(':id', userId);
        const formHtml = `
            <form id="editUserForm" method="POST" action="${updateUrl}" onsubmit="return submitUserForm(event)">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="userId" value="${userId}">
                <div class="form-group">
                    <label for="editName">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editName" name="name" value="${userName}" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="editEmail" name="email" value="${userEmail}" required>
                </div>
                <div class="form-group">
                    <label for="editRole">Role <span class="text-danger">*</span></label>
                    <select class="form-control" id="editRole" name="role" required>
                        <option value="member" ${userRole === 'member' ? 'selected' : ''}>Member</option>
                        <option value="owner" ${userRole === 'owner' ? 'selected' : ''}>Owner</option>
                    </select>
                </div>
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary btn-block">Update User</button>
                    <button type="button" class="btn btn-secondary btn-block mt-2" onclick="closeUserDrawer()">Cancel</button>
                </div>
            </form>
        `;
        
        drawerBody.innerHTML = formHtml;
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function resetPassword(userId, userName) {
        const drawer = document.getElementById('userDrawer');
        const drawerBody = document.getElementById('userDrawerBody');
        const drawerTitle = document.getElementById('userDrawerTitle');
        
        drawerTitle.textContent = '{{ __("Reset Password") }}';
        
        const resetUrl = '{{ route("users.reset-password", ":id") }}'.replace(':id', userId);
        const formHtml = `
            <form id="resetPasswordForm" method="POST" action="${resetUrl}" onsubmit="return submitPasswordForm(event)">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="userId" value="${userId}">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Resetting password for: <strong>${userName}</strong>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="newPassword" name="password" required minlength="8">
                    <small class="form-text text-muted">Minimum 8 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required minlength="8">
                </div>
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-warning btn-block">Reset Password</button>
                    <button type="button" class="btn btn-secondary btn-block mt-2" onclick="closeUserDrawer()">Cancel</button>
                </div>
            </form>
        `;
        
        drawerBody.innerHTML = formHtml;
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeUserDrawer(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const drawer = document.getElementById('userDrawer');
        if (drawer) {
            drawer.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function submitUserForm(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (response.ok) {
                return response.json().catch(() => ({ success: true }));
            }
            return response.json().then(data => {
                throw new Error(data.message || '{{ __("Update failed") }}');
            });
        })
        .then(data => {
            alert('{{ __("User updated successfully!") }}');
            closeUserDrawer();
            window.location.reload();
        })
        .catch(error => {
            alert(error.message || '{{ __("An error occurred") }}');
        });
        
        return false;
    }

    function submitPasswordForm(e) {
        e.preventDefault();
        const form = e.target;
        const password = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            alert('{{ __("Passwords do not match!") }}');
            return false;
        }
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (response.ok) {
                return response.json().catch(() => ({ success: true }));
            }
            return response.json().then(data => {
                throw new Error(data.message || '{{ __("Password reset failed") }}');
            });
        })
        .then(data => {
            alert('{{ __("Password reset successfully!") }}');
            closeUserDrawer();
            window.location.reload();
        })
        .catch(error => {
            alert(error.message || '{{ __("An error occurred") }}');
        });
        
        return false;
    }

    // Close drawer on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUserDrawer();
        }
    });


    // Confirm delete user with PIN
    function confirmDeleteUser(userId, userName) {
        const modal = $('#pinConfirmModal-delete-user');
        const actionUrl = $('#pin-action-url-delete-user');
        const actionMethod = $('#pin-action-method-delete-user');
        const actionMessage = $('#pin-action-message-delete-user');
        
        // Set the delete URL
        actionUrl.val('{{ route("users.destroy", ":id") }}'.replace(':id', userId));
        actionMethod.val('DELETE');
        actionMessage.text('{{ __("Are you sure you want to remove") }} ' + userName + ' {{ __("from the workspace? This action cannot be undone.") }}');
        
        // Show modal
        modal.modal('show');
    }
</script>
@endpush
