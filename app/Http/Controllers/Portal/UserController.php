<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    private function authorizedRoles(): array
    {
        $user = Auth::guard('portal')->user();
        // adm2 cannot assign administrator or adm2 roles
        if ($user->isAdministrator()) {
            return Role::orderByDesc('level')->pluck('name')->toArray();
        }
        return ['pengurus', 'timker', 'singers'];
    }

    public function index(Request $request)
    {
        $users = PortalUser::with('roles')
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('username', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::whereIn('name', $this->authorizedRoles())->get();
        return view('portal.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $allowedRoles = $this->authorizedRoles();

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => ['required', 'string', 'max:60', 'unique:portal_users'],
            'email'                => ['required', 'email', 'unique:portal_users'],
            'password'             => ['required', Password::min(8)->mixedCase()->numbers()],
            'roles'                => ['required', 'array', 'min:1'],
            'roles.*'              => ['exists:roles,id'],
            'is_active'            => ['boolean'],
            'can_upload_documents' => ['boolean'],
            'voice'                => ['nullable', 'in:Sopran,Alto,Tenor,Bass'],
        ]);

        // Ensure no privilege escalation
        $requestedRoleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
        foreach ($requestedRoleNames as $roleName) {
            if (!in_array($roleName, $allowedRoles)) {
                abort(403, "You cannot assign the '{$roleName}' role.");
            }
        }

        $user = PortalUser::create([
            'name'                 => $validated['name'],
            'username'             => $validated['username'],
            'email'                => $validated['email'],
            'password'             => $validated['password'],
            'is_active'            => $request->boolean('is_active', true),
            'can_upload_documents' => $request->boolean('can_upload_documents'),
            'voice'                => $validated['voice'] ?? null,
        ]);

        $user->roles()->sync($validated['roles']);

        return redirect()->route('portal.users.index')
            ->with('success', "User '{$user->name}' created successfully.");
    }

    public function edit(PortalUser $user)
    {
        $roles = Role::whereIn('name', $this->authorizedRoles())->get();
        $user->load('roles');
        return view('portal.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, PortalUser $user)
    {
        $allowedRoles = $this->authorizedRoles();
        $currentUser  = Auth::guard('portal')->user();

        // Prevent demoting/editing admins unless you're admin
        if ($user->isAdministrator() && !$currentUser->isAdministrator()) {
            abort(403, 'You cannot edit an Administrator account.');
        }

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => ['required', 'string', 'max:60', 'unique:portal_users,username,' . $user->id],
            'email'                => ['required', 'email', 'unique:portal_users,email,' . $user->id],
            'password'             => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'roles'                => ['required', 'array', 'min:1'],
            'roles.*'              => ['exists:roles,id'],
            'is_active'            => ['boolean'],
            'can_upload_documents' => ['boolean'],
            'voice'                => ['nullable', 'in:Sopran,Alto,Tenor,Bass'],
        ]);

        $requestedRoleNames = Role::whereIn('id', $validated['roles'])->pluck('name')->toArray();
        foreach ($requestedRoleNames as $roleName) {
            if (!in_array($roleName, $allowedRoles)) {
                abort(403, "You cannot assign the '{$roleName}' role.");
            }
        }

        $updateData = [
            'name'                 => $validated['name'],
            'username'             => $validated['username'],
            'email'                => $validated['email'],
            'is_active'            => $request->boolean('is_active', true),
            'can_upload_documents' => $request->boolean('can_upload_documents'),
            'voice'                => $validated['voice'] ?? null,
        ];
        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);
        $user->roles()->sync($validated['roles']);

        return redirect()->route('portal.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(PortalUser $user)
    {
        $currentUser = Auth::guard('portal')->user();

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isAdministrator() && !$currentUser->isAdministrator()) {
            abort(403);
        }

        $user->delete();
        return redirect()->route('portal.users.index')
            ->with('success', "User '{$user->name}' has been removed.");
    }

    /** AJAX autocomplete for username/email */
    public function search(Request $request)
    {
        $term  = $request->get('q', '');
        $users = PortalUser::where('is_active', true)
            ->where(fn($q) =>
                $q->where('username', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%"))
            ->select('id', 'name', 'username', 'email')
            ->limit(8)
            ->get();

        return response()->json($users);
    }
}
