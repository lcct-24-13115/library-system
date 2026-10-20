<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4: void a loan that was issued by mistake. The row is never deleted.
 */
class VoidLoanTest extends TestCase
{
    use RefreshDatabase;

    private function patron(string $id = 'P-1', string $name = 'Zed Cruz', string $type = 'Student'): Patron
    {
        return Patron::create([
            'id_number'   => $id,
            'name'        => $name,
            'patron_type' => $type,
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

    private function loan(Patron $patron, Book $book, int $daysLate = 0): Loan
    {
        return Loan::create([
            'book_id'   => $book->id,
            'patron_id' => $patron->id,
            'issued_at' => now()->subDays($daysLate + 1),
            'due_date'  => today()->subDays($daysLate),
        ]);
    }

    private function void(User $user, Loan $loan, array $data = ['reason' => 'Wrong patron scanned'])
    {
        return $this->actingAs($user)->post(route('loans.void', $loan), $data);
    }

    public function test_admin_can_void_an_active_loan_and_the_row_is_kept(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 3, 2);
        $loan  = $this->loan($this->patron(), $book);

        $this->void($admin, $loan)->assertSessionHasNoErrors()->assertSessionHas('success');

        $loan->refresh();
        $this->assertNotNull($loan->voided_at);
        $this->assertSame($admin->id, $loan->voided_by);
        $this->assertSame('Wrong patron scanned', $loan->void_reason);
        $this->assertNull($loan->returned_at);   // it was never returned
        $this->assertNull($loan->outcome);
        $this->assertSame(1, Loan::count());      // nothing deleted
        $this->assertSame('voided', $loan->status);
    }

    public function test_librarian_can_void(): void
    {
        $librarian = User::factory()->librarian()->create();
        $loan      = $this->loan($this->patron(), $this->book());

        $this->void($librarian, $loan)->assertSessionHasNoErrors();

        $this->assertNotNull($loan->fresh()->voided_at);
        $this->assertSame($librarian->id, $loan->fresh()->voided_by);
    }

    public function test_staff_cannot_void(): void
    {
        $staff = User::factory()->create(); // default role: staff
        $book  = $this->book('ACC-1', 3, 2);
        $loan  = $this->loan($this->patron(), $book);

        $this->void($staff, $loan)->assertForbidden();

        $this->assertNull($loan->fresh()->voided_at);
        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_a_reason_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 3, 2);
        $loan  = $this->loan($this->patron(), $book);

        $this->void($admin, $loan, ['reason' => ''])->assertSessionHasErrors('reason');
        $this->void($admin, $loan, [])->assertSessionHasErrors('reason');

        $this->assertNull($loan->fresh()->voided_at);
        $this->assertSame(2, $book->fresh()->available_copies);
    }

    public function test_the_copy_goes_back_on_the_shelf(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 3, 2);

        $this->void($admin, $this->loan($this->patron(), $book));

        $this->assertSame(3, $book->fresh()->available_copies);
        $this->assertSame(3, $book->fresh()->total_copies);
    }

    public function test_available_never_goes_above_total(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 2, 2); // inconsistent catalog: nothing is "out"

        $this->void($admin, $this->loan($this->patron(), $book));

        $this->assertSame(2, $book->fresh()->available_copies);
        $this->assertSame(2, $book->fresh()->total_copies);
    }

    public function test_only_a_loan_that_is_still_out_can_be_voided(): void
    {
        $admin  = User::factory()->admin()->create();
        $book   = $this->book('ACC-1', 3, 3);
        $loan   = $this->loan($this->patron(), $book);
        $loan->update(['returned_at' => now(), 'outcome' => 'returned']);

        $this->void($admin, $loan)->assertSessionHasErrors('loan');

        $this->assertNull($loan->fresh()->voided_at);
        $this->assertSame(3, $book->fresh()->available_copies);
    }

    public function test_a_loan_cannot_be_voided_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 3, 2);
        $loan  = $this->loan($this->patron(), $book);

        $this->void($admin, $loan);
        $this->void($admin, $loan)->assertSessionHasErrors('loan');

        $this->assertSame(3, $book->fresh()->available_copies); // only put back once
    }

    public function test_voiding_frees_the_borrow_limit_slot(): void
    {
        config(['library.max_loans.Student' => 2]);

        $staff     = User::factory()->create();
        $librarian = User::factory()->librarian()->create();
        $patron    = $this->patron();
        $a1        = $this->book('A1', 2, 1);
        $a2        = $this->book('A2', 2, 1);
        $b1        = $this->book('B1', 2, 2);

        $first = $this->loan($patron, $a1);
        $this->loan($patron, $a2);

        $issue = fn () => $this->actingAs($staff)->post(route('loans.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => 'B1',
            'loan_days'        => 7,
        ]);

        // At the limit: blocked.
        $issue()->assertSessionHasErrors('patron_id_number');
        $this->assertSame(2, Loan::count());

        // Void one: the slot is free again.
        $this->void($librarian, $first)->assertSessionHasNoErrors();
        $issue()->assertSessionHasNoErrors();

        $this->assertSame(3, Loan::count());
        $this->assertSame(1, $b1->fresh()->available_copies);
    }

    public function test_voided_loans_are_not_active_or_overdue_and_not_in_the_lists(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 3, 2);
        $loan  = $this->loan($this->patron(), $book, 5); // overdue

        $this->assertSame(1, Loan::overdue()->count());

        $this->void($admin, $loan);

        // The "Voided the loan of ... to Zed Cruz" flash message would still be in the session
        // and show on the next page, so start the page checks with a clean session.
        $this->flushSession();

        $this->assertSame(0, Loan::active()->count());
        $this->assertSame(0, Loan::overdue()->count());
        $this->assertSame(1, Loan::voided()->count());
        $this->assertFalse($loan->fresh()->isOverdue());

        $this->actingAs($admin)->get(route('loans.return.form'))
            ->assertOk()
            ->assertDontSee('Zed Cruz');

        $this->actingAs($admin)->get(route('loans.index', ['status' => 'active']))
            ->assertOk()
            ->assertDontSee('Zed Cruz');

        $this->actingAs($admin)->get(route('reports.overdue'))
            ->assertOk()
            ->assertDontSee('Zed Cruz');
    }

    public function test_voided_loans_do_not_count_in_the_patron_list(): void
    {
        $admin = User::factory()->admin()->create();
        $loan  = $this->loan($this->patron(), $this->book());

        $this->void($admin, $loan);

        $this->actingAs($admin)->get(route('patrons.index'))
            ->assertOk()
            ->assertViewHas('patrons', fn ($p) => (int) $p->items()[0]->active_loans_count === 0);
    }

    public function test_voided_loans_show_in_loan_history_with_a_voided_label_and_the_reason(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Rita Admin']);
        $loan  = $this->loan($this->patron(), $this->book());

        $this->void($admin, $loan, ['reason' => 'Issued to the wrong patron']);

        $range = ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString()];

        $this->actingAs($admin)->get(route('reports.loan-history', $range))
            ->assertOk()
            ->assertSee('Zed Cruz')
            ->assertSee('>Voided</span>', false)
            ->assertSee('Issued to the wrong patron')
            ->assertSee('Rita Admin');

        $this->actingAs($admin)->get(route('reports.loan-history', $range + ['status' => 'voided']))
            ->assertSee('Zed Cruz');

        $this->actingAs($admin)->get(route('reports.loan-history', $range + ['status' => 'active']))
            ->assertDontSee('Zed Cruz');

        $this->actingAs($admin)->get(route('loans.index', ['status' => 'voided']))
            ->assertSee('Zed Cruz')
            ->assertSee('>Voided</span>', false);
    }

    public function test_a_voided_loan_cannot_be_returned_renewed_lost_or_scanned(): void
    {
        $admin = User::factory()->admin()->create();
        $book  = $this->book('ACC-1', 3, 2);
        $loan  = $this->loan($this->patron(), $book);

        $this->void($admin, $loan);

        $this->actingAs($admin)->post(route('loans.return', $loan))->assertSessionHasErrors('loan');
        $this->actingAs($admin)->post(route('loans.renew', $loan), ['days' => 7])->assertSessionHasErrors('loan');
        $this->actingAs($admin)->post(route('loans.lost', $loan), ['remarks' => 'x'])->assertSessionHasErrors('loan');
        $this->actingAs($admin)->post(route('loans.damaged', $loan), ['remarks' => 'x', 'restock' => '1'])->assertSessionHasErrors('loan');
        $this->actingAs($admin)->post(route('loans.return.scan'), ['accession_number' => 'ACC-1'])->assertSessionHasErrors('accession_number');

        $loan->refresh();
        $this->assertNull($loan->returned_at);
        $this->assertSame(0, $loan->renewal_count);
        $this->assertSame(3, $book->fresh()->available_copies);
        $this->assertSame(3, $book->fresh()->total_copies);
    }

    public function test_voided_loans_are_left_out_of_the_summary_report(): void
    {
        $admin = User::factory()->admin()->create();
        $good  = $this->loan($this->patron('P-1', 'Zed Cruz'), $this->book('ACC-1'));
        $bad   = $this->loan($this->patron('P-2', 'Ben Reyes'), $this->book('ACC-2'));

        $this->void($admin, $bad);

        $range = ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString()];

        $this->actingAs($admin)->get(route('reports.summary', $range))
            ->assertOk()
            ->assertViewHas('totals', fn ($t) => $t['issued'] === 1 && $t['patrons'] === 1);
    }

    public function test_void_button_is_shown_to_admin_and_librarian_but_not_staff(): void
    {
        $loan = $this->loan($this->patron(), $this->book());
        $url  = route('loans.void', $loan);

        $this->actingAs(User::factory()->admin()->create())->get(route('loans.index'))
            ->assertOk()->assertSee($url, false);

        $this->actingAs(User::factory()->librarian()->create())->get(route('loans.index'))
            ->assertOk()->assertSee($url, false);

        $this->actingAs(User::factory()->create())->get(route('loans.index'))
            ->assertOk()->assertDontSee($url, false);
    }

    public function test_overdue_never_blocks_a_void(): void
    {
        $admin = User::factory()->admin()->create();
        $loan  = $this->loan($this->patron(), $this->book(), 30);

        $this->void($admin, $loan)->assertSessionHasNoErrors();

        $this->assertNotNull($loan->fresh()->voided_at);
    }
}
