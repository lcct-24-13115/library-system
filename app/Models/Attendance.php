<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One library visit (a scan of a patron ID). Log only - no fines, no blocks.
 */
class Attendance extends Model
{
    protected $fillable = [
        'patron_id',
        'recorded_by',
        'visited_at',
        'remarks',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeOnDate(Builder $query, $date): Builder
    {
        return $query->whereDate('visited_at', $date);
    }
}
