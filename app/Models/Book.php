<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /** Columns the staff catalog search looks in. */
    public const SEARCH_COLUMNS = [
        'title', 'subtitle', 'author', 'accession_number', 'isbn', 'call_number',
        'publisher', 'subjects', 'series_statement', 'category',
    ];

    /**
     * Search: every word the visitor types must be found in at least one column.
     * "little prince" finds "The Little Prince"; the order of the words does not matter.
     */
    public function scopeSearch(Builder $query, ?string $term, ?array $columns = null): Builder
    {
        $words = array_slice(preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 6);

        foreach ($words as $word) {
            $like = '%' . self::escapeLike($word) . '%';

            $query->where(function ($w) use ($columns, $like) {
                foreach ($columns ?? self::SEARCH_COLUMNS as $column) {
                    $w->orWhere($column, 'like', $like);
                }
            });
        }

        return $query;
    }

    /**
     * Best matches first: title starts with the word ("The Little Prince" for "the"),
     * then the word is inside the title ("Lord of the Rings"), then part of a longer word
     * ("Mathematics"), then matches found only in author, subject, etc.
     */
    public function scopeOrderByRelevance(Builder $query, ?string $term): Builder
    {
        $term = trim((string) preg_replace('/\s+/', ' ', (string) $term));

        if ($term === '') {
            return $query;
        }

        $e = self::escapeLike($term);

        return $query->orderByRaw(
            'CASE WHEN title LIKE ? OR title LIKE ? THEN 0 WHEN title LIKE ? OR title LIKE ? THEN 1 WHEN title LIKE ? THEN 2 ELSE 3 END',
            [$e, $e . ' %', '% ' . $e . ' %', '% ' . $e, '%' . $e . '%']
        );
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
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
