<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4: remarks are never overwritten. Each one is a dated note with the user's name.
 */
class LoanNotesTest extends TestCase
{
    use RefreshDatabase;

    private function patron(): Patron
    {
        return Patron::create([
            'id_number'   => 'P-1',
            'name'        => 'Zed Cruz',
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

    private function loan(Book $book, ?string $legacyRemarks = null): Loan
    {
        return Loan::create([
            'book_id'   => $book->id,
            'patron_id' => $this->patron()->id,
            'issued_at' => now()->subDay(),
            'due_date'  => today()->addDays(6),
            'remarks'   => $legacyRemarks,
        ]);
    }

    public function test_a_remark_at_issue_becomes_a_note_with_the_user(): void
    {
        $user   = User::factory()->create(['name' => 'Lia Staff']);
        $patron = $this->patron();
        $this->book('ACC-1', 2, 2);

        $this->actingAs($user)->post(route('loans.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => 'ACC-1',
            'loan_days'        => 7,
            'remarks'          => 'Cover slightly torn',
        ])->assertSessionHasNoErrors();

        $loan = Loan::firstOrFail();
        $this->assertNull($loan->remarks);        // the old column is no longer written
        $this->assertSame(1, $loan->notes()->count());
        $this->assertSame('Cover slightly torn', $loan->notes()->first()->note);
        $this->assertSame($user->id, $loan->notes()->first()->user_id);
    }

    public function test_no_remark_means_no_note(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $this->book('ACC-1', 2, 2);

        $this->actingAs($user)->post(route('loans.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => 'ACC-1',
            'loan_days'        => 7,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, Loan::firstOrFail()->notes()->count());
    }

    public function test_remarks_are_appended_never_overwritten(): void
    {
        $first  = User::factory()->create(['name' => 'Lia Staff']);
        $second = User::factory()->librarian()->create(['name' => 'Omar Librarian']);
        $loan   = $this->loan($this->book(), 'Older remark from before Phase 4');

        // Renew with a remark, then return with another remark.
        $this->actingAs($first)->post(route('loans.renew', $loan), ['days' => 7, 'remarks' => 'Asked for more time'])
            ->assertSessionHasNoErrors();
        $this->actingAs($second)->post(route('loans.return', $loan), ['remarks' => 'Returned with a stain'])
            ->assertSessionHasNoErrors();

        $loan->refresh();
        $notes = $loan->notes()->get();

        $this->assertSame('Older remark from before Phase 4', $loan->remarks); // untouched
        $this->assertCount(2, $notes);
        $this->assertSame('Asked for more time', $notes[0]->note);
        $this->assertSame($first->id, $notes[0]->user_id);
        $this->assertSame('Returned with a stain', $notes[1]->note);
        $this->assertSame($second->id, $notes[1]->user_id);
    }

    public function test_a_blank_remark_on_return_adds_nothing(): void
    {
        $user = User::factory()->create();
        $loan = $this->loan($this->book(), 'Old text');

        $this->actingAs($user)->post(route('loans.return', $loan), ['remarks' => '   '])
            ->assertSessionHasNoErrors();

        $this->assertSame('Old text', $loan->fresh()->remarks);
        $this->assertSame(0, $loan->notes()->count());
        $this->assertNotNull($loan->fresh()->returned_at);
    }

    public function test_scan_return_remark_becomes_a_note(): void
    {
        $user = User::factory()->create();
        $loan = $this->loan($this->book('ACC-5', 1, 0));

        $this->actingAs($user)->post(route('loans.return.scan'), [
            'accession_number' => 'ACC-5',
            'remarks'          => 'Fine collected offline',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Fine collected offline', $loan->notes()->first()->note);
        $this->assertSame($user->id, $loan->notes()->first()->user_id);
    }

    public function test_the_return_page_does_not_prefill_old_remarks(): void
    {
        $user = User::factory()->create();
        $this->loan($this->book(), 'Legacy remark text');

        // Pre-filling would save the same old text again as a new note.
        $this->actingAs($user)->get(route('loans.return.form'))
            ->assertOk()
            ->assertDontSee('Legacy remark text');
    }

    public function test_the_loans_list_shows_the_old_remark_and_the_dated_notes_with_names(): void
    {
        $user = User::factory()->create(['name' => 'Lia Staff']);
        $loan = $this->loan($this->book(), 'Legacy remark text');
        $loan->notes()->create(['user_id' => $user->id, 'note' => 'Patron called about the book']);

        $this->actingAs($user)->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Legacy remark text')
            ->assertSee('Patron called about the book')
            ->assertSee('Lia Staff');
    }

    public function test_the_loan_history_report_shows_notes(): void
    {
        $user = User::factory()->create(['name' => 'Lia Staff']);
        $loan = $this->loan($this->book());
        $loan->notes()->create(['user_id' => $user->id, 'note' => 'Reported at the desk']);

        $range = ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString()];

        $this->actingAs($user)->get(route('reports.loan-history', $range))
            ->assertOk()
            ->assertSee('Reported at the desk')
            ->assertSee('Lia Staff');
    }
}
