<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    // `date` is kept as a plain 'Y-m-d' string (no cast) so the unique check matches exactly.
    protected $fillable = ['date', 'reason'];
}
