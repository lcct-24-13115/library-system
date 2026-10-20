<?php

namespace App\Support;

use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Admin-editable settings (Settings page), with the old config/.env values as fallback.
 *
 * Nothing here computes money. Excluded weekdays and holidays only change how many
 * "days overdue" are SHOWN to the librarian (display only).
 * Registered as a singleton in AppServiceProvider; each setting is loaded once per request.
 */
class LibrarySettings
{
    /** Carbon dayOfWeek => label (0 = Sunday). Shown in this order. */
    public const DAYS = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday',
    ];

    private ?array $values = null;
    private ?array $holidays = null;
    private ?array $excluded = null;

    public function refresh(): void
    {
        $this->values = null;
        $this->holidays = null;
        $this->excluded = null;
    }

    private function all(): array
    {
        return $this->values ??= Setting::query()->pluck('value', 'key')->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->refresh();
    }

    /** Weekdays (Carbon dayOfWeek ints) that are not counted. Default: none. */
    public function excludedDays(): array
    {
        if ($this->excluded !== null) {
            return $this->excluded;
        }

        $days = json_decode((string) $this->get('excluded_days', '[]'), true);

        return $this->excluded = array_values(array_unique(array_filter(
            is_array($days) ? array_map('intval', $days) : [],
            fn ($d) => $d >= 0 && $d <= 6
        )));
    }

    /** @return array<string,int> 'Y-m-d' => 1 */
    private function holidayDates(): array
    {
        return $this->holidays ??= Holiday::query()->get()
            ->mapWithKeys(fn ($h) => [Carbon::parse($h->date)->toDateString() => 1])
            ->all();
    }

    public function reportHeading(): ?string
    {
        return $this->get('report_heading');
    }

    public function loanDays(): int
    {
        return max(1, (int) $this->get('loan_days', config('library.loan_days', 7)));
    }

    public function maxLoans(?string $patronType): int
    {
        $patronType = (string) $patronType;
        $limits = config('library.max_loans', []);
        $fallback = (int) ($limits[$patronType] ?? $limits['default'] ?? 3);

        return (int) $this->get('max_loans.' . $patronType, $fallback);
    }

    public function isCounted(Carbon $day): bool
    {
        return ! in_array($day->dayOfWeek, $this->excludedDays(), true)
            && ! isset($this->holidayDates()[$day->toDateString()]);
    }

    /** Number of counted days in the range (after, until]. Same as a plain day difference when nothing is excluded. */
    public function countDays(Carbon $after, Carbon $until): int
    {
        $day = $after->copy()->startOfDay()->addDay();
        $end = $until->copy()->startOfDay();
        $count = 0;

        for ($i = 0; $i < 3660 && $day->lte($end); $i++) {
            if ($this->isCounted($day)) {
                $count++;
            }
            $day->addDay();
        }

        return $count;
    }
}
