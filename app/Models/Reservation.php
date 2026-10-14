<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A hold request for a book (whether currently on loan or on the shelf).
 *
 * Status is managed manually: pending -> fulfilled | cancelled.
 * No fines, no automatic expiry, and a reservation never blocks borrowing.
 */
class Reservation extends Model
{
    public const PENDING   = 'pending';
    public const FULFILLED = 'fulfilled';
    public const CANCELLED = 'cancelled';

    public const STATUSES = [self::PENDING, self::FULFILLED, self::CANCELLED];

    protected $fillable = [
        'book_id',
        'patron_id',
        'status',
        'reserved_at',
        'fulfilled_at',
        'cancelled_at',
        'reserved_by',
        'handled_by',
        'remarks',
    ];

    protected $casts = [
        'reserved_at'  => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function reserver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    /**
     * Queue position (1 = first in line) of each pending reservation, per book.
     * Returns [reservation_id => position]. Pass any collection of reservations.
     */
    public static function queuePositions(Collection $reservations): array
    {
        $bookIds = $reservations->pluck('book_id')->unique()->values();

        if ($bookIds->isEmpty()) {
            return [];
        }

        $positions = [];
        $counters  = [];

        static::pending()
            ->whereIn('book_id', $bookIds)
            ->orderBy('reserved_at')
            ->orderBy('id')
            ->get(['id', 'book_id'])
            ->each(function ($r) use (&$positions, &$counters) {
                $counters[$r->book_id] = ($counters[$r->book_id] ?? 0) + 1;
                $positions[$r->id]     = $counters[$r->book_id];
            });

        return $positions;
    }
}
