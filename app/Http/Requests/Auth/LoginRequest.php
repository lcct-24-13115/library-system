<?php

namespace App\Http\Requests\Auth;

use App\Models\Patron;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Staff sign in with their email; students / faculty may use their ID number too.
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $this->provisionPortalAccountIfNeeded();

        $credentials = ['email' => $this->resolveLogin(), 'password' => $this->input('password')];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Correct credentials, but the account has been deactivated by an admin.
        if (! Auth::user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact the library administrator.',
            ]);
        }

        // A student / faculty login only works while the library card is active.
        if (Auth::user()->isPatron() && ! Auth::user()->patron?->isActive()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This library card is inactive. Please see the librarian.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Automatic portal login: a student / faculty who signs in for the first time with their
     * ID number as BOTH username and password gets their portal account created on the spot.
     * Only when no account exists yet, only for an active card, and only when the password equals the ID number.
     */
    private function provisionPortalAccountIfNeeded(): void
    {
        $login = trim((string) $this->input('email'));

        if ($login === '' || str_contains($login, '@') || ! hash_equals($login, (string) $this->input('password'))) {
            return;
        }

        $patron = Patron::where('id_number', $login)->first();

        if ($patron && $patron->canHavePortalAccount() && ! $patron->account()->exists()) {
            $patron->provisionAccount($login);
        }
    }

    /** An ID number (no "@") is translated to the email of that patron's portal account. */
    private function resolveLogin(): string
    {
        $login = trim((string) $this->input('email'));

        if ($login !== '' && ! str_contains($login, '@')) {
            $patron = Patron::where('id_number', $login)->first();
            $account = $patron ? User::where('patron_id', $patron->id)->first() : null;

            if ($account) {
                return $account->email;
            }
        }

        return $login;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
