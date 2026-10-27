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

        if ($request->boolean('use_id_number')) {
            // One click: the patron's ID number becomes the password (the librarian's choice).
            $password = trim($patron->id_number);

            if (strlen($password) < 4) {
                return back()->with('error', 'This ID number is too short to use as a password. Type a password instead.');
            }

            $data = ['password' => $password];
        } else {
            $data = $request->validate([
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);
        }

        $account = $patron->account;

        if ($account) {
            $account->update(['password' => $data['password']]);

            return back()->with('success', $request->boolean('use_id_number') ? 'Portal password was reset to the ID number.' : 'Portal password was reset.');
        }

        $account = $patron->provisionAccount($data['password']);

        if (! $account) {
            return back()->with('error', 'That email is already used by another account. Change the patron\'s email first.');
        }

        return back()->with('success', $request->boolean('use_id_number') ? 'Portal login created. The password is the ID number.' : 'Portal account created. They can sign in with their ID number or email.');
    }

    public function toggle(Patron $patron)
    {
        $account = $patron->account;
        abort_if(! $account, 404);

        $account->update(['is_active' => ! $account->is_active]);

        return back()->with('success', $account->is_active ? 'Portal account reactivated.' : 'Portal account deactivated.');
    }
}
