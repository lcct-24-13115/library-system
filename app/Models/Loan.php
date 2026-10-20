<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'outcome',
        'voided_at',
        'voided_by',
        'void_reason',
        'remarks',
        'renewal_count',
        'original_due_date',
        'last_renewed_at',
    ];

    protected $casts = [
        'issued_at'   => 'datetime',
        'due_date'    => 'date',
        'returned_at' => 'datetime',
        'voided_at'   => 'datetime',
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

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Phase 4: dated remarks history (oldest first). */
    public function notes(): HasMany
    {
        return $this->hasMany(LoanNote::class)->orderBy('id');
    }

    /* ---------------------------------------------------------------
     | Query scopes
     |---------------------------------------------------------------*/

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->whereNull('voided_at');
    }

    /** Phase 4: loans voided because they were issued by mistake. */
    public function scopeVoided(Builder $query): Builder
    {
        return $query->whereNotNull('voided_at');
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->whereNotNull('returned_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->whereNull('voided_at')->whereDate('due_date', '<', today());
    }

    /* ---------------------------------------------------------------
     | Helpers (informational only - nothing here charges anyone)
     |---------------------------------------------------------------*/

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isReturned() && ! $this->isVoided() && $this->due_date->lt(today());
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    /** Days late when it was returned (0 if on time or still out). Display only. */
    public function daysLateAtReturn(): int
    {
        if (! $this->isReturned()) {
            return 0;
        }

        $returnDay = $this->returned_at->copy()->startOfDay();

        return $this->due_date->lt($returnDay) ? (int) $this->due_date->diffInDays($returnDay) : 0;
    }

    /** Phase 3: how a closed loan ended. NULL (older rows) counts as 'returned'. */
    public const OUTCOME_RETURNED = 'returned';
    public const OUTCOME_LOST     = 'lost';
    public const OUTCOME_DAMAGED  = 'damaged';

    public function outcomeKey(): string
    {
        return $this->outcome ?: self::OUTCOME_RETURNED;
    }

    /** Returned | Lost | Damaged (display only). */
    public function getOutcomeLabelAttribute(): string
    {
        return ucfirst($this->outcomeKey());
    }

    /** voided | returned | overdue | active */
    public function getStatusAttribute(): string
    {
        if ($this->isVoided()) {
            return 'voided';
        }

        if ($this->isReturned()) {
            return 'returned';
        }

        return $this->isOverdue() ? 'overdue' : 'active';
    }
}
