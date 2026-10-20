<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1: per-type borrow limit and duplicate-title block.
 * Overdue must STILL never block (see OverdueDoesNotBlockTest).
 */
class BorrowLimitTest extends TestCase
{
    use RefreshDatabase;

    private function patron(string $type = 'Student'): Patron
    {
        return Patron::create([
            'id_number'   => 'P-' . $type,
            'name'        => "Test {$type}",
            'patron_type' => $type,
            'status'      => 'active',
        ]);
    }

    private function book(string $accession, int $copies = 2): Book
    {
        return Book::create([
            'accession_number' => $accession,
            'title'            => "Title {$accession}",
            'author'           => 'Author',
            'total_copies'     => $copies,
            'available_copies' => $copies,
        ]);
    }

    private function loanOut(Patron $patron, Book $book, bool $overdue = false): Loan
    {
        return Loan::create([
            'book_id'   => $book->id,
            'patron_id' => $patron->id,
            'issued_at' => now()->subDays($overdue ? 20 : 1),
            'due_date'  => $overdue ? today()->subDays(10) : today()->addDays(6),
        ]);
    }

    private function issue(User $user, Patron $patron, Book $book, array $extra = [])
    {
        return $this->actingAs($user)->post(route('loans.store'), array_merge([
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
            'loan_days'        => 7,
        ], $extra));
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->role = $role;
        $u->save();

        return $u;
    }

    public function test_student_can_borrow_up_to_the_limit(): void
    {
        config(['library.max_loans.Student' => 3]);
        $staff   = $this->user('staff');
        $patron  = $this->patron();

        foreach (['A1', 'A2', 'A3'] as $acc) {
            $this->issue($staff, $patron, $this->book($acc))->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Loan::active()->where('patron_id', $patron->id)->count());
    }

    public function test_fourth_book_is_blocked_and_nothing_changes(): void
    {
        config(['library.max_loans.Student' => 3]);
        $staff  = $this->user('staff');
        $patron = $this->patron();

        foreach (['B1', 'B2', 'B3'] as $acc) {
            $this->loanOut($patron, $this->book($acc));
        }
        $fourth = $this->book('B4');

        $this->issue($staff, $patron, $fourth)->assertSessionHasErrors('patron_id_number');

        $this->assertSame(3, Loan::where('patron_id', $patron->id)->count());
        $this->assertSame(2, $fourth->fresh()->available_copies);
    }

    public function test_returning_a_book_frees_a_slot(): void
    {
        config(['library.max_loans.Student' => 1]);
        $staff  = $this->user('staff');
        $patron = $this->patron();
        $loan   = $this->loanOut($patron, $this->book('C1'));
        $next   = $this->book('C2');

        $this->issue($staff, $patron, $next)->assertSessionHasErrors('patron_id_number');

        $loan->update(['returned_at' => now()]);

        $this->issue($staff, $patron, $next)->assertSessionHasNoErrors();
        $this->assertSame(1, Loan::active()->where('patron_id', $patron->id)->count());
    }

    public function test_limit_depends_on_patron_type(): void
    {
        config(['library.max_loans.Student' => 1, 'library.max_loans.Faculty' => 2]);
        $staff   = $this->user('staff');
        $faculty = $this->patron('Faculty');

        $this->loanOut($faculty, $this->book('D1'));
        $this->issue($staff, $faculty, $this->book('D2'))->assertSessionHasNoErrors();
        $this->issue($staff, $faculty, $this->book('D3'))->assertSessionHasErrors('patron_id_number');
    }

    public function test_unknown_type_uses_default_limit(): void
    {
        config(['library.max_loans.default' => 1]);
        $patron = $this->patron();
        $patron->update(['patron_type' => 'Visitor']);

        $this->assertSame(1, $patron->fresh()->maxLoans());
    }

    public function test_staff_cannot_override_the_limit(): void
    {
        config(['library.max_loans.Student' => 1]);
        $staff  = $this->user('staff');
        $patron = $this->patron();
        $this->loanOut($patron, $this->book('E1'));

        $this->issue($staff, $patron, $this->book('E2'), ['override_limit' => 1, 'remarks' => 'please'])
            ->assertSessionHasErrors('patron_id_number');

        $this->assertSame(1, Loan::where('patron_id', $patron->id)->count());
    }

    public function test_librarian_override_needs_a_remark(): void
    {
        config(['library.max_loans.Student' => 1]);
        $lib    = $this->user('librarian');
        $patron = $this->patron();
        $this->loanOut($patron, $this->book('F1'));
        $book2  = $this->book('F2');

        $this->issue($lib, $patron, $book2, ['override_limit' => 1])->assertSessionHasErrors('remarks');
        $this->assertSame(1, Loan::where('patron_id', $patron->id)->count());
        $this->assertSame(2, $book2->fresh()->available_copies);

        $this->issue($lib, $patron, $book2, ['override_limit' => 1, 'remarks' => 'Thesis week, approved'])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Loan::where('patron_id', $patron->id)->count());
    }

    public function test_same_title_cannot_be_borrowed_twice_even_with_override(): void
    {
        $lib    = $this->user('librarian');
        $patron = $this->patron();
        $book   = $this->book('G1', 3);
        $this->loanOut($patron, $book);

        $this->issue($lib, $patron, $book, ['override_limit' => 1, 'remarks' => 'x'])
            ->assertSessionHasErrors('accession_number');

        $this->assertSame(1, Loan::where('patron_id', $patron->id)->count());
    }

    public function test_same_title_can_be_borrowed_again_after_return(): void
    {
        $staff  = $this->user('staff');
        $patron = $this->patron();
        $book   = $this->book('H1', 2);
        $this->loanOut($patron, $book)->update(['returned_at' => now()]);

        $this->issue($staff, $patron, $book)->assertSessionHasNoErrors();
    }

    public function test_overdue_loans_count_toward_limit_but_overdue_alone_never_blocks(): void
    {
        config(['library.max_loans.Student' => 3]);
        $staff  = $this->user('staff');
        $patron = $this->patron();

        // One overdue loan, well under the limit: must still be able to borrow.
        $this->loanOut($patron, $this->book('I1'), true);
        $this->issue($staff, $patron, $this->book('I2'))->assertSessionHasNoErrors();
    }

    public function test_lookup_reports_limit(): void
    {
        config(['library.max_loans.Student' => 3]);
        $staff  = $this->user('staff');
        $patron = $this->patron();
        $this->loanOut($patron, $this->book('J1'));

        $this->actingAs($staff)
            ->getJson(route('lookup.patron', ['id_number' => $patron->id_number]))
            ->assertOk()
            ->assertJson(['found' => true, 'active_loans_count' => 1, 'max_loans' => 3]);
    }
}
