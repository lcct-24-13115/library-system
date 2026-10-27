<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\Patron;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard lists who checked in today (attendance log), and the demo seeder fills in test data.
 */
class DashboardVisitorsTest extends TestCase
{
    use RefreshDatabase;

    private function librarian(): User
    {
        return User::factory()->librarian()->create();
    }

    public function test_dashboard_shows_who_checked_in_today(): void
    {
        $juan  = Patron::create(['id_number' => '2026-0001', 'name' => 'Juan Dela Cruz', 'patron_type' => 'Student', 'status' => 'active']);
        $maria = Patron::create(['id_number' => '2026-0002', 'name' => 'Maria Santos', 'patron_type' => 'Student', 'status' => 'active']);

        Attendance::create(['patron_id' => $juan->id, 'visited_at' => now()]);
        Attendance::create(['patron_id' => $juan->id, 'visited_at' => now()->subMinutes(30)]);
        Attendance::create(['patron_id' => $maria->id, 'visited_at' => now()->subDay()]); // yesterday: not listed

        $this->actingAs($this->librarian())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Library visitors today')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('2026-0001')
            ->assertSee('2 check-ins today')
            ->assertSee('1 different visitors')
            ->assertDontSee('Maria Santos');
    }

    public function test_dashboard_has_a_live_date_and_time(): void
    {
        $this->actingAs($this->librarian())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="live-date"', false)
            ->assertSee('id="live-time"', false)
            ->assertSee('setInterval(tick, 1000)', false)
            ->assertSee(now()->format('l, F j, Y'));
    }

    public function test_dashboard_says_so_when_nobody_checked_in(): void
    {
        $this->actingAs($this->librarian())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No one has checked in yet today.');
    }

    public function test_demo_seeder_adds_books_and_visits_and_can_run_twice(): void
    {
        // The real database has thousands of patrons; give the test enough to pick from.
        foreach (range(1, 15) as $i) {
            Patron::create(['id_number' => sprintf('2026-%04d', $i), 'name' => "Student {$i}", 'patron_type' => 'Student', 'status' => 'active']);
        }

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(DemoDataSeeder::TOTAL_BOOKS, Book::where('accession_number', 'like', 'TEST-%')->count());
        $this->assertGreaterThan(0, Attendance::whereDate('visited_at', today())->count());
        $this->assertSame(12, Attendance::whereDate('visited_at', today())->where('remarks', 'Test data')->count());
    }
}
