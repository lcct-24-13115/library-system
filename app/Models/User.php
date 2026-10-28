<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'user_type', 'library', 'patron_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'librarian', 'staff'];

    /** Student / Faculty portal accounts (read-only access to their own loans). Not in ROLES: created from the patron page. */
    public const PATRON_ROLE = 'patron';

    /** The only groups that may have an account (client requirement). */
    public const USER_TYPES = [
        'Lower School Faculty',
        'Middle / Junior High School Faculty',
        'Senior High School Faculty',
        'College Faculty',
        'Non-Teaching Personnel',
    ];

    // Libraries an account can be assigned to: see App\Support\Libraries. Blank (NULL) = all libraries.

    protected $attributes = [
        'role'      => 'staff',
        'is_active' => true,
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function isPatron(): bool
    {
        return $this->role === self::PATRON_ROLE;
    }

    /** Does this account have one of the given roles? (Read from the users table, never from the session.) */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Where this account lands after login (and when an already signed-in user opens /login).
     * Add a case here when a role gets its own dashboard.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::PATRON_ROLE => 'portal.index',            // students / faculty
            'admin', 'librarian', 'staff' => 'dashboard',   // library staff
            default => 'dashboard',
        };
    }

    /** Detected account type for display: Administrator, Librarian, Staff, Student or Faculty. */
    public function accountType(): string
    {
        return match (true) {
            $this->isAdmin()     => 'Administrator',
            $this->isLibrarian() => 'Librarian',
            $this->isStaff()     => 'Staff',
            $this->isPatron()    => $this->patron?->patron_type === 'Student' ? 'Student' : 'Faculty',
            default              => 'User',
        };
    }

    public function libraryLabel(): string
    {
        return $this->library ?: 'All libraries';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLibrarian(): bool
    {
        return $this->role === 'librarian';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /** Admin or librarian: may create / edit / delete books and patrons. */
    public function canManage(): bool
    {
        return $this->isAdmin() || $this->isLibrarian();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
