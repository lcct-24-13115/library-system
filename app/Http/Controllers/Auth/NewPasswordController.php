<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Second step of the no-email password reset: choose a new password after verification. */
class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedPatron($request)) {
            return redirect()->route('password.request')
                ->withErrors(['id_number' => 'Please verify your details first (or your time ran out).']);
        }

        return view('auth.reset-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $patron = $this->verifiedPatron($request);

        if (! $patron) {
            return redirect()->route('password.request')
                ->withErrors(['id_number' => 'Your time ran out. Please verify your details again.']);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $account = $patron->account;

        if ($account) {
            if (! $account->is_active) {
                throw ValidationException::withMessages(['password' => 'This account has been deactivated.']);
            }

            $account->forceFill(['password' => $data['password'], 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        } elseif (! $patron->provisionAccount($data['password'])) {
            throw ValidationException::withMessages(['password' => 'Could not set up your account.']);
        }

        $request->session()->forget('pwreset');

        return redirect()->route('login')->with('status', 'Your password has been changed. You can sign in now.');
    }

    /** The patron who passed the ID + contact-number check in the last 10 minutes, if any. */
    private function verifiedPatron(Request $request): ?Patron
    {
        $state = $request->session()->get('pwreset');

        if (! $state || ($state['expires'] ?? 0) < now()->timestamp) {
            $request->session()->forget('pwreset');

            return null;
        }

        return Patron::find($state['patron_id']);
    }
}
