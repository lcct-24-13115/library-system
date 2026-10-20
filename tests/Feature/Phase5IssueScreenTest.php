<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 5: Issue screen polish (multi-book flow, "issued this session" list, Done)
 * and the overdue list page. No amounts and no fine computation anywhere.
 */
class Phase5IssueScreenTest extends TestCase
{
    use RefreshDatabase;

    private function patron(string $idNumber = 'P-001', string $name = 'Juan Dela Cruz', array $extra = []): Patron
    {
        return Patron::create(array_merge([
            'id_number'   => $idNumber,
            'name'        => $name,
            'patron_type' => 'Student',
            'status'      => 'active',
        ], $extra));
    }

    private function book(string $accession, string $title, int $copies = 2): Book
    {
        return Book::create([
            'accession_number' => $accession,
            'title'            => $title,
            'author'           => 'Some Author',
            'total_copies'     => $copies,
            'available_copies' => $copies,
        ]);
    }

    private function issue(User $user, Patron $patron, Book $book)
    {
        return $this->actingAs($user)->post(route('loans.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
            'loan_days'        => 7,
        ]);
    }

    private function loan(Patron $patron, Book $book, int $daysOverdue = 0, array $extra = []): Loan
    {
        return Loan::create(array_merge([
            'book_id'   => $book->id,
            'patron_id' => $patron->id,
            'issued_at' => now()->subDays($daysOverdue + 7),
            'due_date'  => today()->subDays($daysOverdue),
        ], $extra));
    }

    /** The full <input ...> tag with the given name, to check value / autofocus. */
    private function inputTag(string $html, string $name): string
    {
        preg_match('/<input[^>]*name="' . preg_quote($name, '/') . '"[^>]*>/s', $html, $m);

        return $m[0] ?? '';
    }

    /* ---------------------------------------------------------------
     | Issue screen
     |---------------------------------------------------------------*/

    public function test_issue_page_renders_with_side_panel_and_done_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('loans.create'))
            ->assertOk()
            ->assertSee('Issued this session')
            ->assertSee('Nothing issued yet')
            ->assertSee(route('loans.issue.done'), false)
            ->assertSee('Done');
    }

    public function test_empty_screen_puts_the_cursor_in_the_patron_box(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('loans.create'))->getContent();

        $this->assertStringContainsString('autofocus', $this->inputTag($html, 'patron_id_number'));
        $this->assertStringNotContainsString('autofocus', $this->inputTag($html, 'accession_number'));
    }

    public function test_after_an_issue_the_patron_stays_and_the_cursor_goes_to_the_book_box(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $book   = $this->book('A1', 'First Book');

        $this->issue($user, $patron, $book)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('loans.create'));

        $html = $this->actingAs($user)->get(route('loans.create'))->getContent();

        $patronTag = $this->inputTag($html, 'patron_id_number');
        $bookTag   = $this->inputTag($html, 'accession_number');

        $this->assertStringContainsString('value="P-001"', $patronTag);
        $this->assertStringNotContainsString('autofocus', $patronTag);
        $this->assertStringContainsString('autofocus', $bookTag);
        $this->assertStringNotContainsString('value="A1"', $bookTag, 'the book box must be empty for the next scan');
    }

    public function test_the_side_list_collects_every_book_issued_to_the_same_patron(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();

        $this->issue($user, $patron, $this->book('A1', 'First Book'))->assertSessionHasNoErrors();
        $this->issue($user, $patron, $this->book('A2', 'Second Book'))->assertSessionHasNoErrors();

        $this->actingAs($user)->get(route('loans.create'))
            ->assertOk()
            ->assertSee('Juan Dela Cruz')
            ->assertSee('2 books')
            ->assertSeeInOrder(['First Book', 'Second Book']);

        $this->assertSame(2, Loan::active()->where('patron_id', $patron->id)->count());
    }

    public function test_a_different_patron_starts_a_fresh_list(): void
    {
        $user = User::factory()->create();
        $juan = $this->patron('P-001', 'Juan Dela Cruz');
        $ana  = $this->patron('P-002', 'Ana Reyes');

        $this->issue($user, $juan, $this->book('A1', 'Juan Book'))->assertSessionHasNoErrors();
        $this->issue($user, $ana, $this->book('A2', 'Ana Book'))->assertSessionHasNoErrors();

        $page = $this->actingAs($user)->get(route('loans.create'))->assertOk();
        $html = $page->getContent();

        $this->assertStringContainsString('value="P-002"', $this->inputTag($html, 'patron_id_number'));
        $this->assertStringContainsString('1 book', $html);

        // Juan's book is only in "Latest Transactions" now, not in the side list.
        $side = substr($html, strpos($html, 'id="issued-session"'), strpos($html, '</aside>') - strpos($html, 'id="issued-session"'));
        $this->assertStringContainsString('Ana Book', $side);
        $this->assertStringNotContainsString('Juan Book', $side);
    }

    public function test_a_failed_issue_does_not_add_to_the_list(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $gone   = $this->book('A1', 'No Copies Left', 1);
        $gone->update(['available_copies' => 0]);

        $this->issue($user, $patron, $gone)->assertSessionHasErrors('accession_number');

        $this->actingAs($user)->get(route('loans.create'))
            ->assertSee('Nothing issued yet');
    }

    public function test_done_clears_the_patron_and_the_list_but_changes_no_loan(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $a      = $this->book('A1', 'First Book');

        $this->issue($user, $patron, $a)->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('loans.issue.done'))
            ->assertRedirect(route('loans.create'));

        $html = $this->actingAs($user)->get(route('loans.create'))->getContent();

        $this->assertStringNotContainsString('value="P-001"', $this->inputTag($html, 'patron_id_number'));
        $this->assertStringContainsString('autofocus', $this->inputTag($html, 'patron_id_number'));
        $this->assertStringContainsString('Nothing issued yet', $html);

        // Done is screen-only: the loan and the copy count are untouched.
        $this->assertSame(1, Loan::active()->where('patron_id', $patron->id)->count());
        $this->assertSame(1, $a->fresh()->available_copies);
    }

    public function test_done_requires_login(): void
    {
        $this->post(route('loans.issue.done'))->assertRedirect(route('login'));
    }

    public function test_an_idle_session_is_dropped(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $loan   = $this->loan($patron, $this->book('A1', 'Old Book'), 0, ['due_date' => today()->addDays(7)]);

        $this->actingAs($user)
            ->withSession(['issue_session' => [
                'patron_id' => $patron->id,
                'loan_ids'  => [$loan->id],
                'at'        => now()->subHour()->timestamp,
            ]])
            ->get(route('loans.create'))
            ->assertOk()
            ->assertSee('Nothing issued yet');
    }

    public function test_the_side_list_only_shows_loans_of_the_session_patron(): void
    {
        $user  = User::factory()->create();
        $juan  = $this->patron('P-001', 'Juan Dela Cruz');
        $ana   = $this->patron('P-002', 'Ana Reyes');
        $mine  = $this->loan($juan, $this->book('A1', 'Juan Book'), 0, ['due_date' => today()->addDays(7)]);
        $other = $this->loan($ana, $this->book('A2', 'Ana Book'), 0, ['due_date' => today()->addDays(7)]);

        $html = $this->actingAs($user)
            ->withSession(['issue_session' => [
                'patron_id' => $juan->id,
                'loan_ids'  => [$mine->id, $other->id],
                'at'        => now()->timestamp,
            ]])
            ->get(route('loans.create'))
            ->getContent();

        $start = strpos($html, 'id="issued-session"');
        $side  = substr($html, $start, strpos($html, '</aside>') - $start);

        $this->assertStringContainsString('Juan Book', $side);
        $this->assertStringNotContainsString('Ana Book', $side);
    }

    public function test_the_issue_page_shows_no_amounts(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $this->issue($user, $patron, $this->book('A1', 'First Book'));

        $html = $this->actingAs($user)->get(route('loans.create'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/₱|\bamount\b|\bbalance\b|\bpenalt/i', $html);
    }

    /* ---------------------------------------------------------------
     | Overdue list
     |---------------------------------------------------------------*/

    public function test_overdue_page_renders_and_lists_only_overdue_loans(): void
    {
        $user = User::factory()->create();   // plain staff may view it

        $juan = $this->patron('P-001', 'Juan Dela Cruz', ['contact_no' => '0917-111-2222', 'email' => 'juan@example.test']);
        $ana  = $this->patron('P-002', 'Ana Reyes');

        $this->loan($juan, $this->book('A1', 'Late Book'), 5);
        $this->loan($ana, $this->book('A2', 'On Time Book'), 0, ['due_date' => today()->addDays(3)]);
        $this->loan($ana, $this->book('A3', 'Returned Late Book'), 9, ['returned_at' => now()]);
        $this->loan($ana, $this->book('A4', 'Voided Late Book'), 9, ['voided_at' => now()]);

        $response = $this->actingAs($user)->get(route('loans.overdue'))
            ->assertOk()
            ->assertViewIs('loans.overdue')
            ->assertSee('Overdue List')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Late Book')
            ->assertSee(today()->subDays(5)->format('M d, Y'))
            ->assertSee('text-amber-700">5</td>', false)
            ->assertSee('0917-111-2222')
            ->assertSee('juan@example.test')
            ->assertDontSee('On Time Book')
            ->assertDontSee('Returned Late Book')
            ->assertDontSee('Voided Late Book');

        $response->assertViewHas('loans', fn ($loans) => $loans->count() === 1 && $loans->first()->daysOverdue() === 5);
    }

    public function test_overdue_page_lists_the_oldest_first(): void
    {
        $user = User::factory()->create();
        $a    = $this->patron('P-001', 'Newer Patron');
        $b    = $this->patron('P-002', 'Oldest Patron');

        $this->loan($a, $this->book('A1', 'Newer Book'), 2);
        $this->loan($b, $this->book('A2', 'Oldest Book'), 20);

        $this->actingAs($user)->get(route('loans.overdue'))
            ->assertSeeInOrder(['Oldest Patron', 'Newer Patron']);
    }

    public function test_overdue_page_empty_state_and_login_required(): void
    {
        $this->get(route('loans.overdue'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee('No overdue books');
    }

    public function test_overdue_page_has_no_amounts_or_fines(): void
    {
        $user = User::factory()->create();
        $this->loan($this->patron(), $this->book('A1', 'Late Book'), 30);

        $html = $this->actingAs($user)->get(route('loans.overdue'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/₱|\bfines?\b|\bamount\b|\bbalance\b|\bpenalt|\bpayment/i', $html);
    }

    public function test_no_money_columns_were_added_to_the_database(): void
    {
        foreach (['loans', 'patrons', 'books'] as $table) {
            foreach (Schema::getColumnListing($table) as $column) {
                $this->assertDoesNotMatchRegularExpression('/fine|amount|penalt|balance|payment|fee/i', $column, "{$table}.{$column}");
            }
        }
    }

    public function test_an_overdue_patron_can_still_issue_in_the_multi_book_flow(): void
    {
        $user   = User::factory()->create();
        $patron = $this->patron();
        $this->loan($patron, $this->book('A1', 'Late Book', 1), 15);

        $this->issue($user, $patron, $this->book('A2', 'New Book'))->assertSessionHasNoErrors();

        $this->actingAs($user)->get(route('loans.create'))->assertSee('New Book');
    }
}
