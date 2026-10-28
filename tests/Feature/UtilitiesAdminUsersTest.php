<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Utilities hub, Phase 2: Administration tab (add / edit user) and Users List tab.
 */
class UtilitiesAdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): User
    {
        return User::factory()->admin()->create($attrs);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'name'                  => 'New Person',
            'email'                 => 'new.person@example.com',
            'password'              => 'S3cure-Pass-99',
            'password_confirmation' => 'S3cure-Pass-99',
            'role'                  => 'librarian',
            'user_type'             => 'College Faculty',
            'library'               => '',
            'from'                  => 'utilities',
        ], $over);
    }

    private function adminTab(): string
    {
        return route('utilities.index', ['tab' => 'administration']);
    }

    public function test_creating_a_user_from_the_tab_goes_back_to_the_users_list(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('utilities.index', ['tab' => 'users']));

        $user = User::where('email', 'new.person@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('librarian', $user->role);
        $this->assertNull($user->library);
        $this->assertTrue($user->is_active);
    }

    public function test_the_old_users_pages_still_redirect_to_the_old_list(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->payload(['from' => '']))
            ->assertRedirect(route('users.index'));
    }

    public function test_an_unknown_from_value_does_not_redirect_anywhere_else(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->payload(['from' => 'https://evil.example']))
            ->assertRedirect(route('users.index'));
    }

    public function test_a_validation_error_returns_to_the_administration_tab_with_the_error(): void
    {
        $this->actingAs($this->admin())
            ->from($this->adminTab())
            ->post(route('users.store'), $this->payload(['user_type' => 'Student']))
            ->assertRedirect($this->adminTab())
            ->assertSessionHasErrors('user_type');

        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    public function test_the_administration_tab_shows_the_new_user_form_by_default(): void
    {
        $this->actingAs($this->admin())
            ->get($this->adminTab())
            ->assertOk()
            ->assertSee('New user')
            ->assertSee('Access Right')
            ->assertSee('Administrator')
            ->assertSee('name="from" value="utilities"', false);
    }

    public function test_edit_mode_loads_the_chosen_user(): void
    {
        $target = User::factory()->create([
            'name' => 'Zed Librarian', 'email' => 'zed@example.com',
            'role' => 'librarian', 'user_type' => 'College Faculty', 'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('utilities.index', ['tab' => 'administration', 'edit' => $target->id]))
            ->assertOk()
            ->assertSee('Edit user: Zed Librarian')
            ->assertSee('value="zed@example.com"', false);
    }

    public function test_edit_mode_ignores_unknown_ids_and_portal_accounts(): void
    {
        $portal = User::factory()->create([
            'name' => 'Portal Pat', 'role' => User::PATRON_ROLE, 'is_active' => true,
        ]);
        $admin = $this->admin();

        foreach ([999999, $portal->id, 'abc'] as $id) {
            $this->actingAs($admin)
                ->get(route('utilities.index', ['tab' => 'administration', 'edit' => $id]))
                ->assertOk()
                ->assertSee('New user')
                ->assertDontSee('Portal Pat');
        }
    }

    public function test_updating_a_user_from_the_tab_goes_back_to_the_users_list(): void
    {
        $target = User::factory()->create([
            'role' => 'staff', 'user_type' => 'Non-Teaching Personnel', 'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('users.update', $target), $this->payload([
                'name' => 'Renamed Person', 'email' => $target->email, 'password' => '', 'password_confirmation' => '',
                'role' => 'librarian', 'library' => 'College Library',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('utilities.index', ['tab' => 'users']));

        $target->refresh();
        $this->assertSame('Renamed Person', $target->name);
        $this->assertSame('librarian', $target->role);
        $this->assertSame('College Library', $target->library);
    }

    public function test_your_own_role_is_locked_in_the_edit_form_and_on_the_server(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('utilities.index', ['tab' => 'administration', 'edit' => $admin->id]))
            ->assertOk()
            ->assertSee('You cannot change your own role.');

        $this->actingAs($admin)
            ->from($this->adminTab())
            ->put(route('users.update', $admin), $this->payload([
                'name' => $admin->name, 'email' => $admin->email, 'password' => '', 'password_confirmation' => '',
                'role' => 'staff', 'user_type' => 'Non-Teaching Personnel',
            ]))
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_users_list_shows_staff_accounts_only_with_labels(): void
    {
        $this->admin(['name' => 'Alice Admin', 'library' => null]);
        User::factory()->create([
            'name' => 'Lib Larry', 'role' => 'librarian', 'user_type' => 'College Faculty',
            'library' => 'College Library', 'is_active' => true,
        ]);
        User::factory()->create(['name' => 'Portal Pat', 'role' => User::PATRON_ROLE, 'is_active' => true]);

        $this->actingAs(User::where('name', 'Alice Admin')->first())
            ->get(route('utilities.index', ['tab' => 'users']))
            ->assertOk()
            ->assertSee('Alice Admin')
            ->assertSee('Lib Larry')
            ->assertSee('Administrator')
            ->assertSee('Librarian')
            ->assertSee('All libraries')
            ->assertSee('College Library')
            ->assertSee('Online')
            ->assertDontSee('Portal Pat');
    }

    public function test_users_list_can_be_filtered_by_user_type(): void
    {
        User::factory()->create(['name' => 'College Carl', 'role' => 'staff', 'user_type' => 'College Faculty', 'is_active' => true]);
        User::factory()->create(['name' => 'Senior Sue', 'role' => 'staff', 'user_type' => 'Senior High School Faculty', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get(route('utilities.index', ['tab' => 'users', 'user_type' => 'College Faculty']))
            ->assertOk()
            ->assertSee('College Carl')
            ->assertDontSee('Senior Sue');
    }

    public function test_deactivate_and_reactivate_from_the_users_tab(): void
    {
        $target = User::factory()->create(['role' => 'staff', 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
        $usersTab = route('utilities.index', ['tab' => 'users']);
        $admin = $this->admin();

        $this->actingAs($admin)->from($usersTab)
            ->post(route('users.deactivate', $target))
            ->assertRedirect($usersTab);
        $this->assertFalse($target->fresh()->is_active);

        $this->actingAs($admin)->from($usersTab)
            ->post(route('users.activate', $target))
            ->assertRedirect($usersTab);
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_you_cannot_deactivate_yourself(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('utilities.index', ['tab' => 'users']))
            ->post(route('users.deactivate', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_staff_cannot_use_the_tabs(): void
    {
        foreach (['staff'] as $role) {
            $user = User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);

            foreach (['administration', 'users'] as $tab) {
                $this->actingAs($user)->get(route('utilities.index', ['tab' => $tab]))->assertForbidden();
            }
        }
    }
}
