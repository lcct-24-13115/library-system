<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Business rule: overdue items never block borrowing or renewing.
 */
class OverdueDoesNotBlockTest extends TestCase
{
    use RefreshDatabase;

    private function makePatron(): Patron
    {
        return Patron::create([
            'id_number'   => '2024-0001',
            'name'        => 'Test Patron',
            'patron_type' => 'Student',
            'status'      => 'active',
        ]);
    }

    private function makeBook(string $accession, string $title, int $total = 1, int $available = 1): Book
    {
        return Book::create([
            'accession_number' => $accession,
            'title'            => $title,
            'author'           => 'Some Author',
            'total_copies'     => $total,
            'available_copies' => $available,
        ]);
    }

    private function makeOverdueLoan(Patron $patron, Book $book, int $daysLate = 10): Loan
    {
        return Loan::create([
            'book_id'   => $book->id,
            'patron_id' => $patron->id,
            'issued_at' => now()->subDays($daysLate + 7),
            'due_date'  => today()->subDays($daysLate),
        ]);
    }

    public function test_patron_with_overdue_loan_can_still_borrow_another_book(): void
    {
        $user   = User::factory()->create();
        $patron = $this->makePatron();

        $overdueBook = $this->makeBook('ACC-001', 'Overdue Book', 1, 0);
        $overdueLoan = $this->makeOverdueLoan($patron, $overdueBook);
        $this->assertTrue($overdueLoan->isOverdue());

        $newBook = $this->makeBook('ACC-002', 'Another Book', 1, 1);

        $response = $this->actingAs($user)->post(route('loans.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $newBook->accession_number,
            'loan_days'        => 7,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('loans.create'));

        $this->assertDatabaseHas('loans', [
            'patron_id'   => $patron->id,
            'book_id'     => $newBook->id,
            'returned_at' => null,
        ]);
        $this->assertSame(2, Loan::where('patron_id', $patron->id)->count());
        $this->assertSame(0, $newBook->fresh()->available_copies);
    }

    public function test_overdue_loan_can_be_renewed(): void
    {
        $user   = User::factory()->create();
        $patron = $this->makePatron();
        $book   = $this->makeBook('ACC-003', 'Late Book', 1, 0);

        $loan        = $this->makeOverdueLoan($patron, $book, 10);
        $originalDue = $loan->due_date->toDateString();
        $this->assertTrue($loan->isOverdue());

        $response = $this->actingAs($user)->post(route('loans.renew', $loan), [
            'days' => 7,
        ]);

        $response->assertSessionHasNoErrors();

        $loan->refresh();

        // Overdue loan renews from today, not from the old due date.
        $this->assertSame(today()->addDays(7)->toDateString(), $loan->due_date->toDateString());
        $this->assertSame($originalDue, $loan->original_due_date->toDateString());
        $this->assertSame(1, $loan->renewal_count);
        $this->assertFalse($loan->isOverdue());
        $this->assertNull($loan->returned_at);
    }
}
