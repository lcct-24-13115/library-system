<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Patron;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 4: roles, allowed user groups and the admin Users page.
 */
class RolesAndUsersTest extends TestCase
{
    use RefreshDatabase;

    private function patron(): Patron
    {
        return Patron::create([
            'id_number'   => '2026-0100',
            'name'        => 'Role Test Patron',
            'patron_type' => 'Student',
            'status'      => 'active',
        ]);
    }

    private function book(string $accession = 'ACC-R1', int $available = 1): Book
    {
        return Book::create([
            'accession_number' => $accession,
            'title'            => 'Role Test Book ' . $accession,
            'author'           => 'Some Author',
            'total_copies'     => 1,
            'available_copies' => $available,
        ]);
    }

    private function validUser(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'New Person',
            'email'                 => 'new.person@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'staff',
            'user_type'             => 'Lower School Faculty',
        ], $overrides);
    }

    public function test_staff_cannot_create_a_book(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('books.create'))->assertForbidden();

        $this->actingAs($staff)->post(route('books.store'), [
            'accession_number' => 'ACC-X',
            'title'            => 'Blocked',
            'author'           => 'Nobody',
            'total_copies'     => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('books', ['accession_number' => 'ACC-X']);
    }

    public function test_staff_cannot_edit_or_delete_books_and_patrons_but_can_view_them(): void
    {
        $staff  = User::factory()->create();
        $book   = $this->book();
        $patron = $this->patron();

        $this->actingAs($staff)->get(route('books.index'))->assertOk()->assertDontSee('+ Add New Book');
        $this->actingAs($staff)->get(route('books.show', $book))->assertOk();
        $this->actingAs($staff)->get(route('patrons.index'))->assertOk()->assertDontSee('+ Add New Patron');
        $this->actingAs($staff)->get(route('patrons.show', $patron))->assertOk();

        $this->actingAs($staff)->get(route('books.edit', $book))->assertForbidden();
        $this->actingAs($staff)->delete(route('books.destroy', $book))->assertForbidden();
        $this->actingAs($staff)->get(route('patrons.create'))->assertForbidden();
        $this->actingAs($staff)->delete(route('patrons.destroy', $patron))->assertForbidden();
    }

    public function test_librarian_can_create_a_book(): void
    {
        $this->actingAs(User::factory()->librarian()->create())
            ->post(route('books.store'), [
                'accession_number' => 'ACC-L1',
                'title'            => 'Librarian Book',
                'author'           => 'Author',
                'total_copies'     => 2,
            ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['accession_number' => 'ACC-L1']);
    }

    public function test_staff_can_issue_a_loan(): void
    {
        $staff  = User::factory()->create();
        $patron = $this->patron();
        $book   = $this->book();

        $this->actingAs($staff)->post(route('loans.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
            'loan_days'        => 7,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('loans', ['patron_id' => $patron->id, 'book_id' => $book->id]);
    }

    public function test_staff_can_scan_attendance_and_make_and_fulfil_reservations(): void
    {
        $staff  = User::factory()->create();
        $patron = $this->patron();
        $book   = $this->book('ACC-R2', 0);

        $this->actingAs($staff)
            ->postJson(route('attendance.store'), ['patron_id_number' => $patron->id_number])
            ->assertStatus(201);

        $this->actingAs($staff)->post(route('reservations.store'), [
            'patron_id_number' => $patron->id_number,
            'accession_number' => $book->accession_number,
        ])->assertSessionHasNoErrors();

        $reservation = \App\Models\Reservation::firstOrFail();

        $this->actingAs($staff)->post(route('reservations.fulfill', $reservation))->assertSessionHasNoErrors();
        $this->actingAs($staff)->get(route('reports.index'))->assertOk();
    }

    public function test_librarian_cannot_open_users(): void
    {
        $librarian = User::factory()->librarian()->create();

        $this->actingAs($librarian)->get(route('users.index'))->assertForbidden();
        $this->actingAs($librarian)->get(route('users.create'))->assertForbidden();
        $this->actingAs($librarian)->post(route('users.store'), $this->validUser())->assertForbidden();
    }

    public function test_staff_cannot_open_users(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_open_users_and_create_a_user_with_a_valid_user_type(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->validUser(['role' => 'librarian', 'user_type' => 'College Faculty']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email'     => 'new.person@example.com',
            'role'      => 'librarian',
            'user_type' => 'College Faculty',
            'is_active' => true,
        ]);
    }

    public function test_creating_a_user_with_an_invalid_user_type_fails(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->validUser(['user_type' => 'Student']))
            ->assertSessionHasErrors('user_type');

        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    public function test_creating_a_user_without_a_user_type_or_role_fails(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = $this->validUser();
        unset($payload['user_type']);
        $this->actingAs($admin)->post(route('users.store'), $payload)->assertSessionHasErrors('user_type');

        $payload = $this->validUser();
        unset($payload['role']);
        $this->actingAs($admin)->post(route('users.store'), $payload)->assertSessionHasErrors('role');

        $this->actingAs($admin)
            ->post(route('users.store'), $this->validUser(['role' => 'superuser']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    public function test_updating_a_user_also_requires_a_valid_user_type(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $target), $this->validUser(['user_type' => 'Visitor']))
            ->assertSessionHasErrors('user_type');

        $this->actingAs($admin)
            ->put(route('users.update', $target), $this->validUser(['user_type' => 'Senior High School Faculty']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Senior High School Faculty', $target->fresh()->user_type);
    }

    public function test_users_index_can_be_filtered_by_user_type(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Person']);
        User::factory()->create(['name' => 'College Person', 'user_type' => 'College Faculty']);
        User::factory()->create(['name' => 'Lower Person', 'user_type' => 'Lower School Faculty']);

        $this->actingAs($admin)
            ->get(route('users.index', ['user_type' => 'College Faculty']))
            ->assertOk()
            ->assertSee('College Person')
            ->assertDontSee('Lower Person');
    }

    public function test_admin_cannot_deactivate_or_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.deactivate', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), $this->validUser([
                'name'  => $admin->name,
                'email' => $admin->email,
                'role'  => 'staff',
            ]))
            ->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_deactivate_and_reactivate_another_user(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('users.deactivate', $target))->assertSessionHas('success');
        $this->assertFalse($target->fresh()->is_active);

        $this->actingAs($admin)->post(route('users.activate', $target))->assertSessionHas('success');
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_users_link_is_visible_to_admins_only(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('patrons.index'))->assertSee(route('utilities.index'));

        $this->actingAs(User::factory()->librarian()->create())
            ->get(route('patrons.index'))->assertDontSee(route('utilities.index'));
    }

    public function test_a_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->get('/login')->assertOk();
    }

    public function test_a_user_deactivated_while_logged_in_is_signed_out(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get(route('books.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_users_cannot_change_their_own_role_or_user_type_from_the_profile(): void
    {
        $user = User::factory()->create(['user_type' => 'College Faculty']);

        $this->actingAs($user)->patch('/profile', [
            'name'      => 'Renamed',
            'email'     => $user->email,
            'role'      => 'admin',
            'user_type' => 'Lower School Faculty',
            'is_active' => false,
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Renamed', $user->name);
        $this->assertSame('staff', $user->role);
        $this->assertSame('College Faculty', $user->user_type);
        $this->assertTrue($user->is_active);
    }

    public function test_overdue_never_blocks_borrowing_for_any_role(): void
    {
        foreach (['admin', 'librarian', 'staff'] as $i => $role) {
            $user   = User::factory()->create(['role' => $role]);
            $patron = Patron::create([
                'id_number'   => "2026-02{$i}0",
                'name'        => "Overdue Patron {$role}",
                'patron_type' => 'Student',
                'status'      => 'active',
            ]);

            $overdueBook = $this->book("ACC-OD-{$role}", 0);
            $overdue     = Loan::create([
                'book_id'   => $overdueBook->id,
                'patron_id' => $patron->id,
                'issued_at' => now()->subDays(20),
                'due_date'  => today()->subDays(10),
            ]);
            $this->assertTrue($overdue->isOverdue());

            $newBook = $this->book("ACC-NEW-{$role}");

            $this->actingAs($user)->post(route('loans.store'), [
                'patron_id_number' => $patron->id_number,
                'accession_number' => $newBook->accession_number,
                'loan_days'        => 7,
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('loans', ['patron_id' => $patron->id, 'book_id' => $newBook->id]);

            $this->actingAs($user)->post(route('loans.renew', $overdue), ['days' => 7])
                ->assertSessionHasNoErrors();
        }
    }
}
