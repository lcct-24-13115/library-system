<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
