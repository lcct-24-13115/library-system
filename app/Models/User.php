<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'user_type', 'library', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'librarian', 'staff'];

    /** The only groups that may have an account (client requirement). */
    public const USER_TYPES = [
        'Lower School Faculty',
        'Middle / Junior High School Faculty',
        'Senior High School Faculty',
        'College Faculty',
        'Non-Teaching Personnel',
    ];

    /** Libraries an account can be assigned to. Blank (NULL) = all libraries. */
    public const LIBRARIES = [
        'Grade School Library',
        'Junior High School Library',
        'Senior High School Library',
        'College Library',
    ];

    protected $attributes = [
        'role'      => 'staff',
        'is_active' => true,
    ];

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
