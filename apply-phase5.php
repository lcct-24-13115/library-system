<?php
// Utilities Phase 5 installer. Run from the project root:  php apply-phase5.php
// Requires Phases 1-4 to be applied first.
if (!file_exists('artisan')) { fwrite(STDERR, "Run this from the project root (the folder that has the 'artisan' file).\n"); exit(1); }
if (!file_exists('tests/Feature/UtilitiesPolicyGroupTest.php')) { fwrite(STDERR, "Apply Phases 1-4 first.\n"); exit(1); }
function put($path, $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) { mkdir($dir, 0777, true); }
    file_put_contents($path, $content . "\n");
    echo "  wrote  $path\n";
}
function patch($path, $old, $new, $marker = null) {
    if (!file_exists($path)) { echo "  FAILED $path not found\n"; return; }
    $s = file_get_contents($path);
    if ($marker !== null && strpos($s, $marker) !== false) { echo "  skip   $path (already patched)\n"; return; }
    if (strpos($s, $old) === false) { echo "  skip/FAILED $path (line not found or already patched: " . substr(trim($old), 0, 40) . "...)\n"; return; }
    file_put_contents($path, str_replace($old, $new, $s));
    echo "  patched $path\n";
}
echo "Files:\n";
put('tests/Feature/UtilitiesSidebarTest.php', <<<'__FILE0__'
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
__FILE0__
);
put('UPDATE-UTILITIES.md', <<<'__FILE1__'
# Utilities module (legacy "Utilities" screen) - summary

Admin only. One page with three tabs, `/utilities`:

| Tab | URL | What it does |
|---|---|---|
| Administration | `/utilities?tab=administration` (`&edit={userId}`) | Add / edit a staff account: User Name, Email, Password, Access Right, User Type, Library |
| Users List | `/utilities?tab=users` | Accounts with Current User, Access right, Library, Status; Edit, Reset password, Deactivate / Reactivate |
| Settings | `/utilities?tab=settings` | Excluded days, Report heading, Picture path, Holiday / Suspension dates (Add, Edit, Delete, Empty) |
| Settings > Group | `/utilities?tab=settings&panel=group` | Read-only list of the 4 patron groups with counts and policy |
| Settings > Policy/Services | `/utilities?tab=settings&panel=policy` | Default loan days and books allowed per group |

## How it fits the existing code
- The tabs reuse the existing controllers and validation (`UserController`, `SettingsController`, `LibrarySettings`). A hidden `from=utilities` field makes them redirect back into Utilities; without it they behave exactly as before.
- The old pages (`/users`, `/users/create`, `/settings`, `/settings/policy`) still work, so bookmarks and the existing tests are unaffected. They are no longer in the sidebar; "Utilities" is highlighted on them.
- Accounts are never deleted (the old "Delete" = Deactivate). The role of your own account cannot be changed or deactivated.
- No fines, amounts or payments anywhere. Excluded days / holidays only change the "days overdue" shown.
- No database migration was needed (Picture path uses the `settings` table).

## Files added / changed over all phases
- New: `UtilitiesController`, `resources/views/utilities/*`, tests `UtilitiesHubTest`, `UtilitiesAdminUsersTest`, `UtilitiesSettingsTest`, `UtilitiesPolicyGroupTest`, `UtilitiesSidebarTest`, and the `UPDATE-UTILITIES-PHASE1..4.md` notes.
- Changed: `routes/web.php`, `UserController` (2 redirects), `SettingsController` (redirects, picture path, holiday update, policy redirect), `LibrarySettings` (`picturePath()`), `_sidebar`, `_topbar`, `icon`.

## Not done on purpose
- Adding / renaming patron groups (see `UPDATE-UTILITIES-PHASE4.md`).
- Login by username instead of email (the system signs in with email).
- Wiring "Picture path" to patron photos (photos are uploaded per patron and stored in `storage`).
__FILE1__
);
echo "Patched:\n";
$u1 = <<<'U1'
            ['Utilities', 'utilities', route('utilities.index'), request()->routeIs('utilities.*')],
U1;
$u2 = <<<'U2'
            ['Utilities', 'utilities', route('utilities.index'), request()->routeIs('utilities.*', 'users.*', 'settings.*')],
U2;
patch('resources/views/layouts/_sidebar.blade.php', $u1, $u2);
$r1 = <<<'R1'
            ['Users', 'admin', route('users.index'), request()->routeIs('users.*')],
R1;
$r2 = <<<'R2'
            ['Settings', 'settings', route('settings.index'), request()->routeIs('settings.*')],
R2;
patch('resources/views/layouts/_sidebar.blade.php', $r1, '');
patch('resources/views/layouts/_sidebar.blade.php', $r2, '');
$t1 = <<<'T1'
        'users.*'           => 'Users',
T1;
$t2 = <<<'T2'
        'users.*'           => 'Users',
        'utilities.*'       => 'Utilities',
        'settings.*'        => 'Settings',
T2;
patch('resources/views/layouts/_topbar.blade.php', $t1, $t2, "'utilities.*'");
echo "\nDone. Next:\n  php artisan view:clear\n  php artisan test\n";
