<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Forgot password WITHOUT email.
 *  - Students / faculty: enter their ID number (must be an active library card), then they may
 *    choose a new password right away.
 *  - Staff (admin / librarian / staff accounts): cannot self-reset; an administrator resets
 *    them from the Users page.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_number' => ['required', 'string', 'max:100'],
        ]);

        $idNumber = trim($data['id_number']);

        if (str_contains($idNumber, '@')) {
            throw ValidationException::withMessages([
                'id_number' => 'Staff accounts cannot be reset here. Please ask the library administrator to reset your password.',
            ]);
        }

        $key = 'pwreset|'.strtolower($idNumber).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages([
                'id_number' => "Too many attempts. Please try again in {$minutes} minute(s) or see the librarian.",
            ]);
        }

        $patron = Patron::where('id_number', $idNumber)->first();

        if (! $patron || ! $patron->canHavePortalAccount()) {
            RateLimiter::hit($key, 900);

            throw ValidationException::withMessages([
                'id_number' => 'We could not find an active library card with that ID number. Check it and try again, or see the librarian.',
            ]);
        }

        RateLimiter::clear($key);

        // Verified: allow ONE password change for this patron within 10 minutes.
        $request->session()->put('pwreset', [
            'patron_id' => $patron->id,
            'expires'   => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->route('password.reset');
    }
}
