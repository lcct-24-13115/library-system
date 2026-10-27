<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * TEST DATA ONLY (for demos / the teacher's presentation).
 *
 * Fills the empty parts of the dashboard: books on loan, overdue, issued / returned today,
 * the 7-day circulation chart, most borrowed titles, recent transactions and reservations.
 * Needs books and patrons first (run DemoDataSeeder once).
 *
 *   php artisan db:seed --class=DemoLoansSeeder
 *
 * Safe to run again: the sample loans / reservations (remarks = "Test data") are replaced, never duplicated.
 *
 * To remove it later:
 *   php artisan tinker
 *   >>> App\Models\Loan::where('remarks', 'Test data')->delete();
 *   >>> App\Models\Reservation::where('remarks', 'Test data')->delete();
 */
class DemoLoansSeeder extends Seeder
{
    public const TAG = 'Test data';

    public function run(): void
    {
        if (! Patron::query()->exists()) {
            $this->call(PatronSeeder::class);
        }

        $this->clearOld();

        $popular = Book::where('accession_number', 'like', 'TEST-%')->orderBy('accession_number')->take(15)->get();
        $others  = Book::where('accession_number', 'like', 'TEST-%')->inRandomOrder()->take(40)->get();
        $pool    = $popular->concat($others)->unique('id')->values();

        $patrons = Patron::where('status', 'active')->where('patron_type', '!=', 'Outside User')->inRandomOrder()->take(60)->get();

        if ($pool->isEmpty() || $patrons->isEmpty()) {
            $this->command?->warn('No books or patrons yet. Run: php artisan db:seed --class=DemoDataSeeder');

            return;
        }

        $staff    = User::whereIn('role', ['librarian', 'admin', 'staff'])->orderByRaw("role = 'librarian' desc")->value('id');
        $loanDays = max(1, (int) config('library.loan_days', 7));

        $next = 0; // hands out patrons one by one so each open loan belongs to a different person
        $patron = function () use ($patrons, &$next) {
            return $patrons[$next++ % $patrons->count()];
        };

        // A few titles are borrowed much more often (they become "Most borrowed titles").
        $book = function () use ($popular, $pool) {
            return ($popular->count() >= 4 && random_int(1, 10) <= 6) ? $popular[random_int(0, 3)] : $pool->random();
        };

        $open  = today()->setTime(7, 30);
        $now   = now();
        $today = function () use ($open, $now) {
            return $now->lte($open)
                ? today()->setTime(0, 5)
                : $open->copy()->addSeconds(random_int(0, max(1, (int) $open->diffInSeconds($now, true))));
        };
        $day = fn (int $daysAgo) => $daysAgo === 0 ? $today() : today()->subDays($daysAgo)->setTime(random_int(8, 16), random_int(0, 59));

        $make = function (Book $b, Patron $p, $issuedAt, $returnedAt = null) use ($staff, $loanDays) {
            return Loan::create([
                'book_id'     => $b->id,
                'patron_id'   => $p->id,
                'issued_by'   => $staff,
                'received_by' => $returnedAt ? $staff : null,
                'issued_at'   => $issuedAt,
                'due_date'    => $issuedAt->copy()->startOfDay()->addDays($loanDays)->toDateString(),
                'returned_at' => $returnedAt,
                'remarks'     => self::TAG,
            ]);
        };

        // 1) Already returned: spread over the last 7 days (2 of them returned today).
        foreach (range(0, 13) as $i) {
            $returnedAgo = $i % 7;
            $issuedAgo   = $returnedAgo + random_int(1, 6);

            $make($book(), $patrons->random(), $day($issuedAgo), $day($returnedAgo));
        }

        // 2) On loan, not yet due.
        foreach (range(1, 7) as $i) {
            $make($book(), $patron(), $day(random_int(1, max(1, min(5, $loanDays - 1)))));
        }

        // 3) Issued today.
        foreach (range(1, 3) as $i) {
            $make($book(), $patron(), $day(0));
        }

        // 4) Overdue (information only; never blocks anything).
        foreach (range(1, 5) as $i) {
            $make($book(), $patron(), $day($loanDays + random_int(3, 18)));
        }

        // Keep each title's available copies in step with the loans still out.
        $this->recount(Loan::where('remarks', self::TAG)->pluck('book_id')->unique());

        $this->reservations($pool, $patrons, $staff);
    }

    private function reservations(Collection $pool, Collection $patrons, ?int $staff): void
    {
        $who = $patrons->shuffle()->values();

        $rows = [
            ['pending', 0], ['pending', 1], ['pending', 2], ['pending', 4],
            ['fulfilled', 1], ['cancelled', 3],
        ];

        foreach ($rows as $i => [$status, $daysAgo]) {
            $at = today()->subDays($daysAgo)->setTime(random_int(8, 16), random_int(0, 59));

            Reservation::create([
                'book_id'      => $pool->random()->id,
                'patron_id'    => $who[$i % $who->count()]->id,
                'status'       => $status,
                'reserved_at'  => $at,
                'fulfilled_at' => $status === 'fulfilled' ? $at->copy()->addHours(3) : null,
                'cancelled_at' => $status === 'cancelled' ? $at->copy()->addHours(2) : null,
                'reserved_by'  => $staff,
                'handled_by'   => $status === 'pending' ? null : $staff,
                'remarks'      => self::TAG,
            ]);
        }
    }

    private function clearOld(): void
    {
        $bookIds = Loan::where('remarks', self::TAG)->pluck('book_id')->unique();

        Loan::where('remarks', self::TAG)->delete();
        Reservation::where('remarks', self::TAG)->delete();

        $this->recount($bookIds);
    }

    /** available = total - loans still out (never below 0). */
    private function recount(Collection $bookIds): void
    {
        foreach (Book::whereIn('id', $bookIds)->get() as $b) {
            $out = Loan::active()->where('book_id', $b->id)->count();

            $b->forceFill(['available_copies' => max(0, (int) $b->total_copies - $out)])->save();
        }
    }
}
