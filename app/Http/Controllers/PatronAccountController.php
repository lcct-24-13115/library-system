<?php

namespace App\Http\Controllers;

use App\Models\Patron;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Admin / librarian: create or reset the Student / Faculty portal login of a patron,
 * and turn it off or on. The portal is read only (own loans, due dates, reservations).
 */
class PatronAccountController extends Controller
{
    public function store(Request $request, Patron $patron)
    {
        if (! $patron->isActive() || $patron->patron_type === 'Outside User') {
            return back()->with('error', 'Only active students, faculty and staff can have a portal account.');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $account = $patron->account;

        if ($account) {
            $account->update(['password' => $data['password']]);

            return back()->with('success', 'Portal password was reset.');
        }

        $email = filled($patron->email)
            ? strtolower(trim($patron->email))
            : strtolower(preg_replace('/\s+/', '', $patron->id_number)) . '@patron.local';

        if (User::where('email', $email)->exists()) {
            return back()->with('error', 'That email is already used by another account. Change the patron\'s email first.');
        }

        $account = User::create([
            'name'      => $patron->name,
            'email'     => $email,
            'password'  => $data['password'],
            'role'      => User::PATRON_ROLE,
            'patron_id' => $patron->id,
            'is_active' => true,
        ]);
        $account->forceFill(['email_verified_at' => now()])->save();

        return back()->with('success', 'Portal account created. They can sign in with their ID number or email.');
    }

    public function toggle(Patron $patron)
    {
        $account = $patron->account;
        abort_if(! $account, 404);

        $account->update(['is_active' => ! $account->is_active]);

        return back()->with('success', $account->is_active ? 'Portal account reactivated.' : 'Portal account deactivated.');
    }
}
