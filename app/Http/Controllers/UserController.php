<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            ->where('role', '!=', User::PATRON_ROLE)
            ->when(in_array($userType, User::USER_TYPES, true), fn ($q) => $q->where('user_type', $userType))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // "Current user" = has an active session in the last 5 minutes (needs SESSION_DRIVER=database).
        $onlineIds = [];
        if (config('session.driver') === 'database') {
            $onlineIds = DB::table(config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();
        }

        return view('users.index', compact('users', 'userType', 'onlineIds'));
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
            'library'   => ['nullable', Rule::in(User::LIBRARIES)],
        ]);

        $data['library'] = $data['library'] ?? null;
        $data['is_active'] = true;

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        abort_if($user->isPatron(), 404); // portal accounts are managed on the patron page

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->isPatron(), 404); // portal accounts are managed on the patron page

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password'  => ['nullable', 'confirmed', Password::defaults()],
            'role'      => ['required', Rule::in(User::ROLES)],
            'user_type' => ['required', Rule::in(User::USER_TYPES)],
            'library'   => ['nullable', Rule::in(User::LIBRARIES)],
        ]);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
        }

        $data['library'] = $data['library'] ?? null;

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function deactivate(Request $request, User $user)
    {
        abort_if($user->isPatron(), 404); // portal accounts are managed on the patron page

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => false]);

        return back()->with('success', "{$user->name} has been deactivated.");
    }

    public function activate(User $user)
    {
        abort_if($user->isPatron(), 404); // portal accounts are managed on the patron page

        $user->update(['is_active' => true]);

        return back()->with('success', "{$user->name} has been reactivated.");
    }

    /** Forgot-password for staff: the admin sets a random temporary password and gives it to the person. */
    public function resetPassword(Request $request, User $user)
    {
        abort_if($user->isPatron(), 404); // students / faculty reset theirs on the sign-in page or the patron page

        $temp = Str::password(10, symbols: false);
        $user->forceFill(['password' => $temp, 'remember_token' => Str::random(60)])->save();

        return back()->with('success', "New temporary password for {$user->name}: {$temp} — give it to them now (it is shown only once) and ask them to change it after signing in.");
    }
}
