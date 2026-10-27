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
 * Students / faculty can REQUEST (reserve) a book from the portal.
 * The librarian still issues it. A request never holds a copy and never blocks anything.
 */
class StudentReserveTest extends TestCase
{
    use RefreshDatabase;

    private function patronAccount(string $id = 'S-1', string $name = 'Juan Student'): User
    {
        $patron = Patron::create(['id_number' => $id, 'name' => $name, 'patron_type' => 'Student', 'status' => 'active']);

        $librarian = User::factory()->create(['role' => 'librarian', 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
        $this->actingAs($librarian)->post(route('patrons.account.store', $patron), [
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        auth()->logout();

        return $patron->fresh()->account;
    }

    private function book(string $acc = 'B-1', int $available = 1): Book
    {
        return Book::create([
            'accession_number' => $acc, 'title' => 'Noli Me Tangere', 'author' => 'Jose Rizal',
            'total_copies' => 2, 'available_copies' => $available,
        ]);
    }

    public function test_a_student_can_send_a_reserve_request_and_it_is_pending(): void
    {
        $account = $this->patronAccount();
        $book = $this->book();

        $this->actingAs($account)->get(route('portal.reserve.confirm', $book->id))->assertOk()->assertSee('Noli Me Tangere');

        $this->actingAs($account)->post(route('portal.reserve.store', $book->id))->assertRedirect(route('portal.index'));

        $this->assertDatabaseHas('reservations', [
            'book_id' => $book->id, 'patron_id' => $account->patron_id, 'status' => Reservation::PENDING,
        ]);
        $this->assertSame(1, $book->fresh()->available_copies, 'A request must not hold or take a copy.');
    }

    public function test_the_request_shows_in_the_librarians_reservations_list(): void
    {
        $account = $this->patronAccount('S-2', 'Maria Student');
        $book = $this->book();
        $this->actingAs($account)->post(route('portal.reserve.store', $book->id));
        auth()->logout();

        $librarian = User::factory()->create(['role' => 'librarian', 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
        $this->actingAs($librarian)->get(route('reservations.index'))->assertOk()->assertSee('Maria Student')->assertSee('Noli Me Tangere');
    }

    public function test_a_duplicate_request_or_a_book_already_on_loan_is_refused(): void
    {
        $account = $this->patronAccount();
        $book = $this->book();

        $this->actingAs($account)->post(route('portal.reserve.store', $book->id));
        $this->actingAs($account)->post(route('portal.reserve.store', $book->id))->assertSessionHasErrors('reserve');
        $this->assertSame(1, Reservation::count());

        $other = $this->book('B-2');
        Loan::create(['book_id' => $other->id, 'patron_id' => $account->patron_id, 'issued_at' => now(), 'due_date' => today()->addDays(7)]);
        $this->actingAs($account)->post(route('portal.reserve.store', $other->id))->assertSessionHasErrors('reserve');
    }

    public function test_an_overdue_student_can_still_send_a_request(): void
    {
        $account = $this->patronAccount();
        $held = $this->book('B-10');
        Loan::create(['book_id' => $held->id, 'patron_id' => $account->patron_id, 'issued_at' => now()->subDays(30), 'due_date' => today()->subDays(10)]);

        $book = $this->book('B-11');
        $this->actingAs($account)->post(route('portal.reserve.store', $book->id))->assertSessionHasNoErrors();
        $this->assertSame(1, Reservation::count());
    }

    public function test_a_student_can_cancel_only_their_own_pending_request(): void
    {
        $mine = $this->patronAccount('S-3', 'Me Myself');
        $theirs = $this->patronAccount('S-4', 'Someone Else');
        $book = $this->book();

        $this->actingAs($mine)->post(route('portal.reserve.store', $book->id));
        $this->actingAs($theirs)->post(route('portal.reserve.store', $book->id));
        $myRequest = Reservation::where('patron_id', $mine->patron_id)->first();

        $this->actingAs($theirs)->post(route('portal.reserve.cancel', $myRequest))->assertForbidden();
        $this->assertTrue($myRequest->fresh()->isPending());

        $this->actingAs($mine)->post(route('portal.reserve.cancel', $myRequest))->assertSessionHas('success');
        $this->assertSame(Reservation::CANCELLED, $myRequest->fresh()->status);
    }

    public function test_guests_and_staff_cannot_use_the_student_reserve_routes(): void
    {
        $book = $this->book();

        $this->post(route('portal.reserve.store', $book->id))->assertRedirect(route('login'));

        $staff = User::factory()->create(['role' => 'staff', 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
        $this->actingAs($staff)->post(route('portal.reserve.store', $book->id))->assertForbidden();
    }

    public function test_the_catalog_shows_a_reserve_button_only_to_signed_in_patrons(): void
    {
        $account = $this->patronAccount();
        $book = $this->book();

        $this->get(route('catalog.index'))->assertOk()->assertDontSee(route('portal.reserve.confirm', $book->id), false);
        $this->actingAs($account)->get(route('catalog.index'))->assertOk()->assertSee(route('portal.reserve.confirm', $book->id), false);
    }
}
