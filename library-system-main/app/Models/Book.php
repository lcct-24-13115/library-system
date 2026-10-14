<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        // Original catalog fields
        'accession_number',
        'title',
        'author',
        'isbn',
        'category',
        'total_copies',
        'available_copies',

        // RDA description
        'subtitle',
        'statement_of_responsibility',
        'edition_statement',
        'place_of_publication',
        'publisher',
        'year_of_publication',
        'series_statement',
        'content_type',
        'media_type',
        'carrier_type',
        'extent',
        'dimensions',
        'language',

        // Classification & subjects
        'call_number',
        'subjects',
        'genre',
        'summary',
        'notes',

        // Location & acquisition
        'library',
        'location',
        'date_acquired',
        'dealer_donor',
        'price',
    ];

    protected $casts = [
        'date_acquired' => 'date',
        'price'         => 'decimal:2',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** Subjects as an array (stored semicolon-separated). */
    public function subjectList(): array
    {
        return collect(explode(';', (string) $this->subjects))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();
    }
}
