<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single borrowing transaction (one copy of one book to one patron).
 *
 * No fines or penalties are computed anywhere in this model.
 * "Overdue" is purely informational for the librarian.
 */
class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'patron_id',
        'issued_by',
        'received_by',
        'issued_at',
        'due_date',
        'returned_at',
        'remarks',
        'renewal_count',
        'original_due_date',
        'last_renewed_at',
    ];

    protected $casts = [
        'issued_at'   => 'datetime',
        'due_date'    => 'date',
        'returned_at' => 'datetime',
        'original_due_date' => 'date',
        'last_renewed_at'   => 'datetime',
    ];

    /* ---------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------*/

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /* ---------------------------------------------------------------
     | Query scopes
     |---------------------------------------------------------------*/

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->whereNotNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->whereDate('due_date', '<', today());
    }

    /* ---------------------------------------------------------------
     | Helpers (informational only - nothing here charges anyone)
     |---------------------------------------------------------------*/

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isReturned() && $this->due_date->lt(today());
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    /** returned | overdue | active */
    public function getStatusAttribute(): string
    {
        if ($this->isReturned()) {
            return 'returned';
        }

        return $this->isOverdue() ? 'overdue' : 'active';
    }
}
