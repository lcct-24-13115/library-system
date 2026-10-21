<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7: login tabs, Student / Faculty portal, public catalog (OPAC).
 */
class PortalAndCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
    }

    private function patron(string $id = 'S-100', string $type = 'Student', string $name = 'Juan Student'): Patron
    {
        return Patron::create(['id_number' => $id, 'name' => $name, 'patron_type' => $type, 'status' => 'active']);
    }

    private function book(string $acc = 'B-1', string $title = 'Noli Me Tangere'): Book
    {
        return Book::create([
            'accession_number' => $acc, 'title' => $title, 'author' => 'Jose Rizal',
            'total_copies' => 2, 'available_copies' => 1, 'call_number' => 'FIC RIZ',
        ]);
    }

    private function makeAccount(Patron $patron, string $password = 'password123'): void
    {
        $this->actingAs($this->staff('librarian'))->post(route('patrons.account.store', $patron), [
            'password' => $password, 'password_confirmation' => $password,
        ])->assertSessionHasNoErrors();
        auth()->logout();
    }

    /* ---------------- Catalog (public) ---------------- */

    public function test_the_catalog_is_public_and_searchable(): void
    {
        $this->book('B-1', 'Noli Me Tangere');
        $this->book('B-2', 'El Filibusterismo');

        $this->get(route('catalog.index'))->assertOk()->assertSee('Noli Me Tangere')->assertSee('El Filibusterismo');

        $this->get(route('catalog.index', ['q' => 'Filibus']))
            ->assertOk()->assertSee('El Filibusterismo')->assertDontSee('Noli Me Tangere');
    }

    public function test_the_catalog_shows_availability_but_no_patron_or_accession_data(): void
    {
        $book = $this->book('B-SECRET');
        $patron = $this->patron('S-777', 'Student', 'Secret Reader');
        Loan::create(['book_id' => $book->id, 'patron_id' => $patron->id, 'issued_at' => now(), 'due_date' => today()->addDays(7)]);

        $this->get(route('catalog.index'))->assertOk()
            ->assertSee('Available: 1 of 2')
            ->assertDontSee('Secret Reader')
            ->assertDontSee('B-SECRET');
    }

    /* ---------------- Login tabs ---------------- */

    public function test_login_page_has_no_role_selection_but_keeps_the_catalog_link(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertDontSee('name="portal"', false)
            ->assertDontSee('Sign in as')
            ->assertSee('images/st-augustine.jpg', false)   // left page of the open book
            ->assertSee('id="book-close-btn"', false)       // the book can be closed again
            ->assertSee(route('catalog.index'));
    }

    public function test_each_staff_role_is_detected_automatically_and_sent_to_the_dashboard(): void
    {
        foreach (['admin', 'librarian', 'staff'] as $role) {
            $user = $this->staff($role);

            $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            auth()->logout();
        }
    }

    public function test_a_stray_portal_field_is_ignored_and_the_database_role_wins(): void
    {
        $user = $this->staff('librarian');

        // Even if someone posts the old "portal" field with a different role, it has no effect.
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password', 'portal' => 'administrator'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_middleware_uses_the_database_role_not_anything_from_login(): void
    {
        $librarian = $this->staff('librarian');

        $this->post(route('login'), ['email' => $librarian->email, 'password' => 'password', 'portal' => 'administrator']);

        $this->get(route('users.index'))->assertForbidden();   // admin only
        $this->get(route('books.index'))->assertOk();          // librarian allowed
    }

    /* ---------------- Portal accounts ---------------- */

    public function test_librarian_creates_a_portal_account_and_the_student_signs_in_with_the_id_number(): void
    {
        $patron = $this->patron('2026-0001');
        $this->makeAccount($patron);

        $account = $patron->fresh()->account;
        $this->assertSame('patron', $account->role);
        $this->assertSame($patron->id, $account->patron_id);

        $this->post(route('login'), ['email' => '2026-0001', 'password' => 'password123'])
            ->assertRedirect(route('portal.index'));
        $this->assertAuthenticatedAs($account);
    }

    public function test_staff_cannot_create_portal_accounts(): void
    {
        $patron = $this->patron();

        $this->actingAs($this->staff('staff'))->post(route('patrons.account.store', $patron), [
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_student_and_faculty_accounts_are_detected_and_sent_to_the_portal(): void
    {
        $student = $this->patron('S-1', 'Student', 'Juan Student');
        $faculty = $this->patron('F-1', 'Faculty', 'Prof Cruz');
        $this->makeAccount($student);
        $this->makeAccount($faculty);

        $this->assertSame('Student', $student->fresh()->account->accountType());
        $this->assertSame('Faculty', $faculty->fresh()->account->accountType());

        $this->post(route('login'), ['email' => 'S-1', 'password' => 'password123'])->assertRedirect(route('portal.index'));
        auth()->logout();

        $this->post(route('login'), ['email' => 'F-1', 'password' => 'password123'])->assertRedirect(route('portal.index'));
    }

    public function test_outside_users_and_inactive_patrons_get_no_account(): void
    {
        $outside = $this->patron('O-1', 'Outside User');
        $this->actingAs($this->staff('librarian'))->post(route('patrons.account.store', $outside), [
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHas('error');

        $this->assertNull($outside->fresh()->account);
    }

    public function test_a_deactivated_portal_account_or_inactive_card_cannot_sign_in(): void
    {
        $patron = $this->patron('S-5');
        $this->makeAccount($patron);

        $patron->fresh()->account->update(['is_active' => false]);
        $this->post(route('login'), ['email' => 'S-5', 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $patron->fresh()->account->update(['is_active' => true]);
        $patron->update(['status' => 'inactive']);
        $this->post(route('login'), ['email' => 'S-5', 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_librarian_can_deactivate_and_reactivate_the_portal_account(): void
    {
        $patron = $this->patron('S-6');
        $this->makeAccount($patron);

        $this->actingAs($this->staff('librarian'))->post(route('patrons.account.toggle', $patron));
        $this->assertFalse($patron->fresh()->account->is_active);

        $this->actingAs($this->staff('librarian'))->post(route('patrons.account.toggle', $patron));
        $this->assertTrue($patron->fresh()->account->is_active);
    }

    /* ---------------- Portal pages ---------------- */

    public function test_a_patron_sees_only_their_own_loans_and_no_amounts(): void
    {
        $me = $this->patron('S-10', 'Student', 'Me Myself');
        $other = $this->patron('S-11', 'Student', 'Someone Else');
        $this->makeAccount($me);

        $mine = $this->book('B-10', 'My Own Book');
        $theirs = $this->book('B-11', 'Their Private Book');
        Loan::create(['book_id' => $mine->id, 'patron_id' => $me->id, 'issued_at' => now()->subDays(20), 'due_date' => today()->subDays(3)]);
        Loan::create(['book_id' => $theirs->id, 'patron_id' => $other->id, 'issued_at' => now(), 'due_date' => today()->addDays(5)]);

        $this->actingAs($me->fresh()->account)->get(route('portal.index'))
            ->assertOk()
            ->assertSee('My Own Book')
            ->assertSee('Overdue 3 days')
            ->assertDontSee('Their Private Book')
            ->assertDontSee('Someone Else')
            ->assertDontSee('₱')
            ->assertDontSeeText('fines');
    }

    public function test_a_patron_cannot_open_staff_pages(): void
    {
        $patron = $this->patron('S-20');
        $this->makeAccount($patron);
        $account = $patron->fresh()->account;

        foreach (['dashboard', 'users.index', 'settings.index', 'reports.index', 'loans.create'] as $route) {
            $this->actingAs($account)->get(route($route))->assertForbidden();
        }
        $this->actingAs($account)->get(route('books.index'))->assertForbidden();
    }

    public function test_staff_cannot_open_the_portal_and_guests_are_sent_to_login(): void
    {
        $this->actingAs($this->staff('admin'))->get(route('portal.index'))->assertForbidden();

        auth()->logout();
        $this->get(route('portal.index'))->assertRedirect(route('login'));
    }

    public function test_portal_accounts_are_not_listed_or_editable_on_the_users_page(): void
    {
        $patron = $this->patron('S-30', 'Student', 'Hidden Student');
        $this->makeAccount($patron);
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertDontSee('Hidden Student');
        $this->actingAs($admin)->get(route('users.edit', $patron->fresh()->account))->assertNotFound();
    }
}
