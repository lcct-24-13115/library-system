<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Utilities hub, Phase 1: admin-only page with three tabs (shell only).
 */
class UtilitiesHubTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create([
            'role'      => $role,
            'user_type' => 'Non-Teaching Personnel',
            'is_active' => true,
        ]);
    }

    public function test_admin_sees_the_three_tabs(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('utilities.index'))
            ->assertOk()
            ->assertSee('Click any of the tabs that you want to execute')
            ->assertSee('Administration')
            ->assertSee('Users List')
            ->assertSee('Settings');
    }

    public function test_default_tab_is_administration(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('utilities.index'))
            ->assertOk()
            ->assertViewHas('tab', 'administration');
    }

    public function test_each_tab_can_be_opened(): void
    {
        $admin = $this->user('admin');

        foreach (['administration', 'users', 'settings'] as $tab) {
            $this->actingAs($admin)
                ->get(route('utilities.index', ['tab' => $tab]))
                ->assertOk()
                ->assertViewHas('tab', $tab);
        }
    }

    public function test_an_invalid_tab_falls_back_to_the_default(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('utilities.index', ['tab' => '../../etc/passwd']))
            ->assertOk()
            ->assertViewHas('tab', 'administration');
    }

    public function test_librarian_and_staff_are_forbidden(): void
    {
        $this->actingAs($this->user('librarian'))->get(route('utilities.index'))->assertForbidden();
        $this->actingAs($this->user('staff'))->get(route('utilities.index'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('utilities.index'))->assertRedirect(route('login'));
    }

    public function test_only_admins_see_the_sidebar_link(): void
    {
        $this->actingAs($this->user('admin'))->get(route('dashboard'))
            ->assertSee(route('utilities.index'), false);

        $this->actingAs($this->user('librarian'))->get(route('dashboard'))
            ->assertDontSee(route('utilities.index'), false);
    }
}
