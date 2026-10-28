<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Utilities hub, Phase 5: one "Utilities" sidebar entry replaces "Users" and "Settings".
 * The old pages and routes keep working (bookmarks, existing forms).
 */
class UtilitiesSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private const ACTIVE_LINK = '/<a href="[^"]*\/utilities" class="nav-link active"/';

    public function test_the_sidebar_has_utilities_and_no_separate_users_or_settings_links(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('utilities.index'), false)
            ->assertDontSee('href="' . route('users.index') . '"', false)
            ->assertDontSee('href="' . route('settings.index') . '"', false);
    }

    public function test_utilities_is_highlighted_on_its_own_pages_and_on_the_old_pages(): void
    {
        $admin = $this->admin();

        foreach ([
            route('utilities.index'),
            route('utilities.index', ['tab' => 'users']),
            route('users.index'),
            route('users.create'),
            route('settings.index'),
            route('settings.policy'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
            $this->assertMatchesRegularExpression(
                self::ACTIVE_LINK,
                $this->actingAs($admin)->get($url)->getContent(),
                "Utilities should be highlighted on $url"
            );
        }
    }

    public function test_the_breadcrumb_says_utilities(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('utilities.index'))->assertSee('<strong>Utilities</strong>', false);
        $this->actingAs($admin)->get(route('settings.index'))->assertSee('<strong>Settings</strong>', false);
    }

    public function test_librarians_and_staff_see_no_utilities_link(): void
    {
        foreach (['librarian', 'staff'] as $role) {
            $user = User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);

            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee(route('utilities.index'), false);
        }
    }
}
