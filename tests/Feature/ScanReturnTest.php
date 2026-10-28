<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2: scan-first Return. Overdue never blocks a return.
 */
class ScanReturnTest extends TestCase
{
    use RefreshDatabase;

    private function patron(string $id = 'P-1', string $name = 'Juan Dela Cruz'): Patron
    {
        return Patron::create([
            'id_number'   => $id,
            'name'        => $name,
            'patron_type' => 'Student',
            'status'      => 'active',
        ]);
    }

    private function book(string $accession = 'ACC-1', int $total = 1, int $available = 0): Book
    {
        return Book::create([
            'accession_number' => $accession,
            'title'            => "Title {$accession}",
            'author'           => 'Author',
            'total_copies'     => $total,
            'available_copies' => $available,
        ]);
    }

    private function loan(Patron $patron, Book $book, int $daysLate = 0): Loan
    {
        return Loan::create([
            'book_id'   => $book->id,
            'patron_id' => $patron->id,
            'issued_at' => now()->subDays($daysLate + 7),
            'due_date'  => today()->subDays($daysLate),
        ]);
    }

    private function scan(User $user, string $accession, array $extra = [])
    {
        return $this->actingAs($user)->post(route('loans.return.scan'), array_merge([
            'accession_number' => $accession,
        ], $extra));
    }

    public function test_scan_returns_the_book_and_restores_the_copy(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 1, 0);
        $loan = $this->loan($this->patron(), $book);

        $this->scan($user, 'ACC-1')
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('loans.return.form'))
            ->assertSessionHas('success');

        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame($user->id, $loan->fresh()->received_by);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_scan_trims_whitespace_around_the_accession(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-2', 1, 0);
        $loan = $this->loan($this->patron(), $book);

        $this->scan($user, "  ACC-2 \n")->assertSessionHasNoErrors();

        $this->assertNotNull($loan->fresh()->returned_at);
    }

    public function test_unknown_accession_changes_nothing(): void
    {
        $user = User::factory()->create();

        $this->scan($user, 'NOPE')->assertSessionHasErrors('accession_number');
    }

    public function test_book_with_no_active_loan_changes_nothing(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-3', 1, 1);

        $this->scan($user, 'ACC-3')->assertSessionHasErrors('accession_number');

        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_already_returned_loan_cannot_be_returned_again(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-4', 1, 1);
        $this->loan($this->patron(), $book)->update(['returned_at' => now()]);

        $this->scan($user, 'ACC-4')->assertSessionHasErrors('accession_number');

        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_overdue_return_works_and_shows_an_offline_notice_without_amounts(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-5', 1, 0);
        $loan = $this->loan($this->patron(), $book, 5);

        $this->scan($user, 'ACC-5')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('overdue_notice', '5 days overdue. Collect any fine offline.');

        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_on_time_return_has_no_overdue_notice(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-6', 1, 0);
        $this->loan($this->patron(), $book, 0);

        $this->scan($user, 'ACC-6')->assertSessionMissing('overdue_notice');
    }

    public function test_multiple_active_loans_are_not_guessed(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-7', 2, 0);
        $a    = $this->loan($this->patron('P-1', 'Ana'), $book);
        $b    = $this->loan($this->patron('P-2', 'Ben'), $book);

        $this->scan($user, 'ACC-7')
            ->assertRedirect(route('loans.return.form', ['search' => 'ACC-7']))
            ->assertSessionHas('error');

        $this->assertNull($a->fresh()->returned_at);
        $this->assertNull($b->fresh()->returned_at);
        $this->assertSame(0, $book->fresh()->available_copies);
    }

    public function test_reservation_notice_is_shown_but_reservation_is_untouched(): void
    {
        $user    = User::factory()->create();
        $book    = $this->book('ACC-8', 1, 0);
        $this->loan($this->patron('P-1', 'Ana'), $book);
        $waiting = $this->patron('P-2', 'Ben Reyes');

        $reservation = Reservation::create([
            'book_id'     => $book->id,
            'patron_id'   => $waiting->id,
            'status'      => Reservation::PENDING,
            'reserved_at' => now(),
        ]);

        $this->scan($user, 'ACC-8')
            ->assertSessionHas('reservation_notice');

        $this->assertSame(Reservation::PENDING, $reservation->fresh()->status);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_remarks_are_saved_and_kept_when_blank(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-9', 1, 0);
        $loan = $this->loan($this->patron(), $book);
        $loan->update(['remarks' => 'Issued with cover torn']); // older remark (read-only since Phase 4)

        $this->scan($user, 'ACC-9');

        // A blank remark changes nothing and adds no note.
        $this->assertSame('Issued with cover torn', $loan->fresh()->remarks);
        $this->assertSame(0, $loan->notes()->count());
    }

    public function test_return_page_shows_scan_box_and_todays_returns(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-10', 1, 0);
        $this->loan($this->patron(), $book);
        $this->scan($user, 'ACC-10');

        $this->actingAs($user)->get(route('loans.return.form'))
            ->assertOk()
            ->assertSee('Scan accession number')
            ->assertSee('Checked in today')
            ->assertSee('Title ACC-10');
    }

    public function test_scan_requires_login(): void
    {
        $this->post(route('loans.return.scan'), ['accession_number' => 'X'])
            ->assertRedirect(route('login'));
    }

    public function test_existing_click_return_still_works(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-11', 1, 0);
        $loan = $this->loan($this->patron(), $book, 3);

        $this->actingAs($user)->post(route('loans.return', $loan))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('overdue_notice');

        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame(1, $book->fresh()->available_copies);
    }
}
