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
 * Phase 3: Lost / Damaged. No money anywhere; only copy counts and history labels.
 */
class LostDamagedTest extends TestCase
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

    private function book(string $accession = 'ACC-1', int $total = 3, int $available = 2): Book
    {
        return Book::create([
            'accession_number' => $accession,
            'title'            => "Title {$accession}",
            'author'           => 'Author',
            'total_copies'     => $total,
            'available_copies' => $available,
        ]);
    }

    private function loan(Patron $patron, Book $book, int $daysLate = 0, ?string $remarks = null): Loan
    {
        return Loan::create([
            'book_id'   => $book->id,
            'patron_id' => $patron->id,
            'issued_at' => now()->subDays($daysLate + 1),
            'due_date'  => today()->subDays($daysLate),
            'remarks'   => $remarks,
        ]);
    }

    private function lost(User $user, Loan $loan, array $data = ['remarks' => 'Patron says it was lost'])
    {
        return $this->actingAs($user)->post(route('loans.lost', $loan), $data);
    }

    private function damaged(User $user, Loan $loan, array $data)
    {
        return $this->actingAs($user)->post(route('loans.damaged', $loan), $data);
    }

    public function test_lost_closes_the_loan_and_removes_one_copy(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->lost($user, $loan)->assertSessionHas('success');

        $loan->refresh();
        $this->assertNotNull($loan->returned_at);
        $this->assertSame('lost', $loan->outcome);
        $this->assertSame($user->id, $loan->received_by);

        $book->refresh();
        $this->assertSame(2, $book->total_copies);
        $this->assertSame(2, $book->available_copies); // NOT increased
    }

    public function test_lost_requires_a_remark_and_changes_nothing_without_it(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->lost($user, $loan, ['remarks' => ''])->assertSessionHasErrors('remarks');

        $this->assertNull($loan->fresh()->returned_at);
        $this->assertNull($loan->fresh()->outcome);
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_damaged_restock_puts_the_copy_back_on_the_shelf(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->damaged($user, $loan, ['remarks' => 'Torn cover', 'restock' => '1'])
            ->assertSessionHas('success');

        $this->assertSame('damaged', $loan->fresh()->outcome);
        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->assertSame(3, $book->fresh()->available_copies);
    }

    public function test_damaged_no_restock_counts_like_lost(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->damaged($user, $loan, ['remarks' => 'Water damage', 'restock' => '0'])
            ->assertSessionHas('success');

        $this->assertSame('damaged', $loan->fresh()->outcome);
        $this->assertSame(2, $book->fresh()->total_copies);
        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_damaged_requires_remark_and_a_restock_choice(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->damaged($user, $loan, ['remarks' => 'Torn', ])->assertSessionHasErrors('restock');
        $this->damaged($user, $loan, ['remarks' => '', 'restock' => '1'])->assertSessionHasErrors('remarks');

        $this->assertNull($loan->fresh()->returned_at);
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_an_already_returned_loan_cannot_be_marked_lost_or_damaged(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 3);
        $loan = $this->loan($this->patron(), $book);
        $loan->update(['returned_at' => now(), 'outcome' => 'returned']);

        $this->lost($user, $loan)->assertSessionHasErrors('loan');
        $this->damaged($user, $loan, ['remarks' => 'Torn', 'restock' => '0'])->assertSessionHasErrors('loan');

        $this->assertSame('returned', $loan->fresh()->outcome);
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->assertSame(3, $book->fresh()->available_copies);
    }

    public function test_a_lost_loan_cannot_be_lost_twice(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->lost($user, $loan);
        $this->lost($user, $loan)->assertSessionHasErrors('loan');

        $this->assertSame(2, $book->fresh()->total_copies);
    }

    public function test_counts_never_go_negative(): void
    {
        $user = User::factory()->create();

        // Last copy of the title is lost: total goes 1 -> 0, available stays 0.
        $book = $this->book('ACC-1', 1, 0);
        $this->lost($user, $this->loan($this->patron(), $book));
        $this->assertSame(0, $book->fresh()->total_copies);
        $this->assertSame(0, $book->fresh()->available_copies);

        // A catalog already at 0 (manual edit) must not go below 0.
        $odd = $this->book('ACC-2', 0, 0);
        $this->lost($user, $this->loan($this->patron('P-2', 'Ben Reyes'), $odd));
        $this->assertSame(0, $odd->fresh()->total_copies);
        $this->assertSame(0, $odd->fresh()->available_copies);
    }

    public function test_available_never_exceeds_the_new_total(): void
    {
        $user = User::factory()->create();
        // Inconsistent catalog (everything "available" although one is on loan).
        $book = $this->book('ACC-1', 2, 2);

        $this->lost($user, $this->loan($this->patron(), $book));

        $this->assertSame(1, $book->fresh()->total_copies);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_overdue_never_blocks_lost_or_damaged_and_shows_the_offline_notice(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book, 5);

        $this->lost($user, $loan)
            ->assertSessionHas('overdue_notice', '5 days overdue. Collect any fine offline.');

        $this->assertSame('lost', $loan->fresh()->outcome);
    }

    public function test_the_earlier_remark_is_kept_and_the_new_one_is_added_as_a_note(): void
    {
        $user = User::factory()->create();
        $loan = $this->loan($this->patron(), $this->book(), 0, 'Fine collected offline');

        $this->lost($user, $loan, ['remarks' => 'Patron reported lost']);

        // Phase 4: the old remark is untouched; the new one is a dated note with the user.
        $this->assertSame('Fine collected offline', $loan->fresh()->remarks);

        $note = $loan->notes()->first();
        $this->assertNotNull($note);
        $this->assertSame('LOST: Patron reported lost', $note->note);
        $this->assertSame($user->id, $note->user_id);
    }

    public function test_a_lost_copy_does_not_touch_reservations_or_show_a_hold_aside_notice(): void
    {
        $user    = User::factory()->create();
        $book    = $this->book('ACC-1', 2, 0);
        $waiting = $this->patron('P-2', 'Ben Reyes');
        $loan    = $this->loan($this->patron(), $book);

        $reservation = Reservation::create([
            'book_id'     => $book->id,
            'patron_id'   => $waiting->id,
            'status'      => Reservation::PENDING,
            'reserved_at' => now(),
        ]);

        $this->lost($user, $loan)->assertSessionMissing('reservation_notice');

        $this->assertSame(Reservation::PENDING, $reservation->fresh()->status);
    }

    public function test_history_shows_lost_and_damaged_labels_and_old_loans_stay_returned(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 5, 2);

        $lostLoan    = $this->loan($this->patron('P-1', 'Ana Santos'), $book);
        $damagedLoan = $this->loan($this->patron('P-2', 'Ben Reyes'), $book);
        $oldLoan     = $this->loan($this->patron('P-3', 'Carla Lim'), $book);
        $oldLoan->update(['returned_at' => now()]); // old row: outcome stays NULL

        $this->lost($user, $lostLoan);
        $this->damaged($user, $damagedLoan, ['remarks' => 'Torn', 'restock' => '1']);

        $this->assertNull($oldLoan->fresh()->outcome);
        $this->assertSame('Returned', $oldLoan->fresh()->outcome_label);
        $this->assertSame('Lost', $lostLoan->fresh()->outcome_label);
        $this->assertSame('Damaged', $damagedLoan->fresh()->outcome_label);

        $range = ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString()];

        // Match the badge markup itself, so patron names or headings cannot satisfy the test.
        $this->actingAs($user)->get(route('reports.loan-history', $range))
            ->assertOk()
            ->assertSee('>Lost</span>', false)
            ->assertSee('>Damaged</span>', false)
            ->assertSee('>Checked in</span>', false);

        $this->actingAs($user)->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('>Lost</span>', false)
            ->assertSee('>Damaged</span>', false);
    }

    public function test_return_page_renders_with_the_lost_damaged_controls(): void
    {
        $user = User::factory()->create();
        $this->loan($this->patron(), $this->book());

        $this->actingAs($user)->get(route('loans.return.form'))
            ->assertOk()
            ->assertSee('Mark as Lost')
            ->assertSee('Mark as Damaged');
    }

    public function test_a_normal_return_still_works_and_is_recorded_as_returned(): void
    {
        $user = User::factory()->create();
        $book = $this->book('ACC-1', 3, 2);
        $loan = $this->loan($this->patron(), $book);

        $this->actingAs($user)->post(route('loans.return', $loan))->assertSessionHas('success');

        $this->assertSame('returned', $loan->fresh()->outcome);
        $this->assertSame(3, $book->fresh()->available_copies);
        $this->assertSame(3, $book->fresh()->total_copies);
    }
}
