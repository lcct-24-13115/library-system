<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\Reservation;
use Database\Seeders\DemoLoansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The demo seeder fills the dashboard and can be run again without duplicating anything. */
class DemoLoansSeederTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): void
    {
        foreach (range(1, 15) as $n) {
            Book::create([
                'accession_number' => sprintf('TEST-%05d', $n), 'title' => "Sample Book {$n}", 'author' => 'Test Author',
                'total_copies' => 5, 'available_copies' => 5,
            ]);
        }

        foreach (range(1, 20) as $n) {
            Patron::create(['id_number' => "25-{$n}", 'name' => "Sample Patron {$n}", 'patron_type' => 'Student', 'status' => 'active']);
        }
    }

    public function test_it_fills_the_dashboard_numbers(): void
    {
        $this->prepare();
        $this->seed(DemoLoansSeeder::class);

        $this->assertSame(15, Loan::active()->count());
        $this->assertSame(5, Loan::overdue()->count());
        $this->assertSame(3, Loan::whereDate('issued_at', today())->count());
        $this->assertGreaterThanOrEqual(2, Loan::whereDate('returned_at', today())->count());
        $this->assertSame(4, Reservation::where('status', 'pending')->count());

        // available copies follow the loans still out
        foreach (Book::all() as $b) {
            $this->assertSame(max(0, 5 - Loan::active()->where('book_id', $b->id)->count()), (int) $b->available_copies);
        }

        $this->actingAs(\App\Models\User::factory()->create(['role' => 'librarian']))
            ->get('/dashboard')->assertOk();
    }

    public function test_running_it_twice_does_not_duplicate(): void
    {
        $this->prepare();
        $this->seed(DemoLoansSeeder::class);
        $first = Loan::count();

        $this->seed(DemoLoansSeeder::class);

        $this->assertSame($first, Loan::count());
        $this->assertSame(6, Reservation::count());
    }
}
