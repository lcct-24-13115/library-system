<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Admin-only user management. Accounts are deactivated, never hard-deleted.
 * Only the five approved user groups (User::USER_TYPES) may have an account.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $userType = (string) $request->query('user_type', '');

        $users = User::query()
            ->when(in_array($userType, User::USER_TYPES, true), fn ($q) => $q->where('user_type', $userType))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users', 'userType'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password'  => ['required', 'confirmed', Password::defaults()],
            'role'      => ['required', Rule::in(User::ROLES)],
            'user_type' => ['required', Rule::in(User::USER_TYPES)],
        ]);

        $data['is_active'] = true;

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password'  => ['nullable', 'confirmed', Password::defaults()],
            'role'      => ['required', Rule::in(User::ROLES)],
            'user_type' => ['required', Rule::in(User::USER_TYPES)],
        ]);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function deactivate(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => false]);

        return back()->with('success', "{$user->name} has been deactivated.");
    }

    public function activate(User $user)
    {
        $user->update(['is_active' => true]);

        return back()->with('success', "{$user->name} has been reactivated.");
    }
}
