<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Patron extends Model
{
    use HasFactory;

    public const TYPES = ['Student', 'Faculty', 'Staff', 'Outside User'];

    protected $fillable = [
        'id_number',
        'name',
        'patron_type',
        'email',
        'contact_no',
        'status',
        'photo',
    ];

    /** Student / Faculty portal login (role 'patron'), if one was created. */
    public function account(): HasOne
    {
        return $this->hasOne(User::class, 'patron_id');
    }

    /** Student / Faculty / Staff cards may have a portal login. Outside Users and inactive cards may not. */
    public function canHavePortalAccount(): bool
    {
        return $this->isActive() && $this->patron_type !== 'Outside User';
    }

    /**
     * Create the portal login (role 'patron') for this patron with the given password.
     * Returns null when not allowed, when one already exists, or when the email is taken by someone else.
     */
    public function provisionAccount(string $password): ?User
    {
        if (! $this->canHavePortalAccount() || $this->account()->exists()) {
            return null;
        }

        $email = filled($this->email)
            ? strtolower(trim($this->email))
            : strtolower(preg_replace('/\s+/', '', $this->id_number)) . '@patron.local';

        if (User::where('email', $email)->exists()) {
            return null;
        }

        $account = User::create([
            'name'      => $this->name,
            'email'     => $email,
            'password'  => $password,
            'role'      => User::PATRON_ROLE,
            'patron_id' => $this->id,
            'is_active' => true,
        ]);
        $account->forceFill(['email_verified_at' => now()])->save();

        return $account;
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Max books this patron may have on loan at once (see config/library.php). */
    public function maxLoans(): int
    {
        return app(\App\Support\LibrarySettings::class)->maxLoans($this->patron_type);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Public URL of the ID photo, or null when none was uploaded. */
    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    /** Up to two initials, used as the avatar when there is no photo. */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(collect($words)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    }
}
