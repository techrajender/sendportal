<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspaces;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Workspaces\RemoveUserFromWorkspace;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class WorkspaceUsersController extends Controller
{
    /** @var RemoveUserFromWorkspace */
    private $removeUserFromWorkspace;

    public function __construct(RemoveUserFromWorkspace $removeUserFromWorkspace)
    {
        $this->removeUserFromWorkspace = $removeUserFromWorkspace;
    }

    public function index(Request $request): ViewContract
    {
        $workspace = $request->user()->currentWorkspace;
        
        // Paginate users
        $users = $workspace->users()->paginate(10, ['*'], 'users_page');
        
        // Paginate invitations
        $invitations = $workspace->invitations()->orderBy('created_at', 'desc')->paginate(10, ['*'], 'invitations_page');
        
        return view('users.index', [
            'users' => $users,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Update a user's information in the current workspace.
     */
    public function update(Request $request, int $userId): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'role' => 'required|in:owner,member',
        ]);

        $requestUser = $request->user();
        $workspace = $requestUser->currentWorkspace();

        if ($userId === $requestUser->id) {
            return redirect()
                ->back()
                ->with('error', __('You cannot edit yourself.'));
        }

        $user = User::findOrFail($userId);

        // Update user information
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        // Update the role in the pivot table
        $workspace->users()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                __(':user has been updated successfully.', ['user' => $user->name])
            );
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(Request $request, int $userId): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $requestUser = $request->user();
        $workspace = $requestUser->currentWorkspace();

        if ($userId === $requestUser->id) {
            return redirect()
                ->back()
                ->with('error', __('You cannot reset your own password from here.'));
        }

        $user = User::findOrFail($userId);

        // Verify user belongs to workspace
        if (!$workspace->users->contains($user)) {
            return redirect()
                ->back()
                ->with('error', __('User not found in this workspace.'));
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                __('Password has been reset for :user.', ['user' => $user->name])
            );
    }

    /**
     * Remove a user from the current workspace.
     */
    public function destroy(Request $request, int $userId): RedirectResponse
    {
        /* @var $requestUser \App\Models\User */
        $requestUser = $request->user();

        if ($userId === $requestUser->id) {
            return redirect()
                ->back()
                ->with('error', __('You cannot remove yourself from your own workspace.'));
        }

        $workspace = $requestUser->currentWorkspace();

        $user = User::find($userId);

        $this->removeUserFromWorkspace->handle($user, $workspace);

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                __(':user was removed from :workspace.', ['user' => $user->name, 'workspace' => $workspace->name])
            );
    }
}
