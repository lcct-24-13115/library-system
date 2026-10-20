<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One dated remark on a loan, with the user who wrote it. Never edited or overwritten.
 */
class LoanNote extends Model
{
    protected $fillable = ['loan_id', 'user_id', 'note'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
