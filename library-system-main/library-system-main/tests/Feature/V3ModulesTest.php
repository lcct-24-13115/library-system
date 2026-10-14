<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Version 3: attendance, reservations, patron profile, live preview lookups.
 */
class V3ModulesTest extends TestCase
{
    use RefreshDatabase;

    private function patron(array $overrides = []): Patron
    {
        return Patron::create(array_merge([
            'id_number'   => '2026-0001',
            'name'        => 'Juan Dela Cruz',
            'patron_type' => 'Student',
            'status'      => 'active',
        ], $overrides));
    }

    private function book(array $overrides = []): Book
    {
        return Book::create(array_merge([
            'accession_number' => 'ACC-001',
            'title'            => 'Noli Me Tangere',
            'author'           => 'Jose Rizal',
            'total_copies'     => 1,
            'available_copies' => 1,
        ], $overrides));
    }

    private function login(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    /* ------------------------------ Attendance ------------------------------ */

    public function test_scan_logs_a_visit_and_returns_json(): void
    {
        $this->login();
        $this->patron();

        $this->postJson(route('attendance.store'), ['patron_id_number' => '2026-0001'])
            ->assertStatus(201)
            ->assertJsonPath('status', 'logged');

        $this->assertSame(1, Attendance::count());
    }

    public function test_duplicate_scan_inside_window_is_ignored(): void
    {
        $this->login();
        $this->patron();

        $this->postJson(route('attendance.store'), ['patron_id_number' => '2026-0001'])->assertStatus(201);
        $this->postJson(route('attendance.store'), ['patron_id_number' => '2026-0001'])
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, Attendance::count());
    }

    public function test_duplicate_window_can_be_disabled(): void
    {
        config(['library.attendance_duplicate_minutes' => 0]);
        $this->login();
        $this->patron();

        $this->postJson(route('attendance.store'), ['patron_id_number' => '2026-0001'])->assertStatus(201);
        $this->postJson(route('attendance.store'), ['patron_id_number' => '2026-0001'])->assertStatus(201);

        $this->assertSame(2, Attendance::count());
    }

    public function test_unknown_patron_is_rejected(): void
    {
        $this->login();

        $this->postJson(route('attendance.store'), ['patron_id_number' => 'NOPE'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('patron_id_number');

        $this->assertSame(0, Attendance::count());
    }

    public function test_attendance_pages_render(): void
    {
        $this->login();
        $patron = $this->patron();
        Attendance::create(['patron_id' => $patron->id, 'visited_at' => now()]);

        $this->get(route('attendance.index'))->assertOk()->assertSee('Visitors per hour');
        $this->get(route('attendance.history'))->assertOk()->assertSee('Juan Dela Cruz');
    }

    /* ------------------------------ Reservations ----------------------------- */

    public function test_can_reserve_a_book_that_is_on_loan(): void
    {
        $this->login();
        $patron = $this->patron();
        $book   = $this->book(['available_copies' => 0]);

        $this->post(route('reservations.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
        ])->assertRedirect(route('reservations.index'));

        $this->assertSame(1, Reservation::pending()->count());
        // A reservation never touches stock.
        $this->assertSame(0, $book->fresh()->available_copies);
    }

    public function test_duplicate_pending_reservation_is_rejected(): void
    {
        $this->login();
        $patron = $this->patron();
        $book   = $this->book();
        $payload = ['patron_id_number' => $patron->id_number, 'accession_number' => $book->accession_number];

        $this->post(route('reservations.store'), $payload);
        $this->post(route('reservations.store'), $payload)->assertSessionHasErrors('accession_number');

        $this->assertSame(1, Reservation::count());
    }

    public function test_cannot_reserve_a_book_the_patron_already_has_on_loan(): void
    {
        $this->login();
        $patron = $this->patron();
        $book   = $this->book(['available_copies' => 0]);
        Loan::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'issued_at' => now(), 'due_date' => today()->addDays(7)]);

        $this->post(route('reservations.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
        ])->assertSessionHasErrors('accession_number');
    }

    public function test_overdue_loans_never_block_a_reservation(): void
    {
        $this->login();
        $patron = $this->patron();
        $other  = $this->book(['accession_number' => 'ACC-002', 'title' => 'El Filibusterismo', 'available_copies' => 0]);
        Loan::create(['book_id' => $other->id, 'patron_id' => $patron->id, 'issued_at' => now()->subDays(30), 'due_date' => today()->subDays(20)]);
        $book = $this->book();

        $this->post(route('reservations.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Reservation::count());
    }

    public function test_fulfill_and_cancel_only_work_on_pending(): void
    {
        $this->login();
        $patron = $this->patron();
        $book   = $this->book();

        $a = Reservation::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'status' => 'pending', 'reserved_at' => now()]);
        $this->post(route('reservations.fulfill', $a))->assertSessionHasNoErrors();
        $this->assertSame('fulfilled', $a->fresh()->status);
        $this->assertNotNull($a->fresh()->fulfilled_at);

        $this->post(route('reservations.cancel', $a))->assertSessionHasErrors('reservation');
        $this->assertSame('fulfilled', $a->fresh()->status);

        $b = Reservation::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'status' => 'pending', 'reserved_at' => now()]);
        $this->post(route('reservations.cancel', $b))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $b->fresh()->status);
    }

    public function test_reservation_pages_render(): void
    {
        $this->login();
        $patron = $this->patron();
        $book   = $this->book();
        Reservation::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'status' => 'pending', 'reserved_at' => now()]);

        $this->get(route('reservations.index'))->assertOk()->assertSee('Noli Me Tangere');
        $this->get(route('reservations.create'))->assertOk();
    }

    /* ------------------------------ Patron profile --------------------------- */

    public function test_patron_profile_renders_all_sections(): void
    {
        $this->login();
        $patron = $this->patron(['email' => 'juan@example.com']);
        $book   = $this->book(['available_copies' => 0]);
        Loan::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'issued_at' => now(), 'due_date' => today()->addDays(7)]);
        Attendance::create(['patron_id' => $patron->id, 'visited_at' => now()]);

        $this->get(route('patrons.show', $patron))
            ->assertOk()
            ->assertSee('juan@example.com')
            ->assertSee('Active Loans')
            ->assertSee('Borrowing History')
            ->assertSee('Attendance History')
            ->assertSee('Active Reservations');
    }

    public function test_patrons_create_route_still_works(): void
    {
        $this->login();
        $this->get(route('patrons.create'))->assertOk();
    }

    public function test_patron_with_attendance_cannot_be_deleted(): void
    {
        $this->login();
        $patron = $this->patron();
        Attendance::create(['patron_id' => $patron->id, 'visited_at' => now()]);

        $this->delete(route('patrons.destroy', $patron))->assertSessionHasErrors('delete');
        $this->assertNotNull($patron->fresh());
    }

    /* ------------------------------ Live preview ----------------------------- */

    public function test_patron_lookup(): void
    {
        $this->login();
        $this->patron();

        $this->getJson(route('lookup.patron', ['id_number' => '2026-0001']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('name', 'Juan Dela Cruz');

        $this->getJson(route('lookup.patron', ['id_number' => 'missing']))
            ->assertOk()
            ->assertJsonPath('found', false);
    }

    public function test_book_lookup_reports_availability_and_queue(): void
    {
        $this->login();
        $patron = $this->patron();
        $book   = $this->book(['available_copies' => 0]);
        Reservation::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'status' => 'pending', 'reserved_at' => now()]);

        $this->getJson(route('lookup.book', ['accession_number' => 'ACC-001', 'patron_id_number' => '2026-0001']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('is_available', false)
            ->assertJsonPath('pending_reservations_count', 1)
            ->assertJsonPath('patron_reservation_position', 1);

        $this->getJson(route('lookup.book', ['accession_number' => 'nope']))
            ->assertOk()
            ->assertJsonPath('found', false);
    }

    public function test_lookups_require_login(): void
    {
        $this->getJson(route('lookup.patron', ['id_number' => 'x']))->assertUnauthorized();
    }

    public function test_issue_form_contains_live_preview_and_prefill(): void
    {
        $this->login();

        $this->get(route('loans.create', ['patron_id_number' => '2026-0001', 'accession_number' => 'ACC-001']))
            ->assertOk()
            ->assertSee('lookup-preview', false)
            ->assertSee('value="2026-0001"', false)
            ->assertSee('value="ACC-001"', false);
    }
}
