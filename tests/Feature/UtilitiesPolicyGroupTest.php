<?php

namespace Tests\Feature;

use App\Models\Patron;
use App\Models\User;
use App\Support\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Utilities hub, Phase 4: Group and Policy/Services panels inside the Settings tab.
 */
class UtilitiesPolicyGroupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function tab(array $extra = []): string
    {
        return route('utilities.index', ['tab' => 'settings'] + $extra);
    }

    private function patron(string $id, string $type, string $status = 'active'): Patron
    {
        return Patron::create(['id_number' => $id, 'name' => 'P ' . $id, 'patron_type' => $type, 'status' => $status]);
    }

    public function test_the_settings_tab_has_group_and_policy_buttons(): void
    {
        $this->actingAs($this->admin())
            ->get($this->tab())
            ->assertOk()
            ->assertSee('Policy/Services')
            ->assertSee(e($this->tab(['panel' => 'group'])), false)
            ->assertSee(e($this->tab(['panel' => 'policy'])), false);
    }

    public function test_the_policy_panel_shows_loan_days_and_a_limit_for_every_group(): void
    {
        $this->actingAs($this->admin())
            ->get($this->tab(['panel' => 'policy']))
            ->assertOk()
            ->assertSee('Default loan period (days)')
            ->assertSee('name="from" value="utilities"', false)
            ->assertSee('name="max_loans[Student]"', false)
            ->assertSee('name="max_loans[Faculty]"', false)
            ->assertSee('name="max_loans[Staff]"', false)
            ->assertSee('name="max_loans[Outside User]"', false);
    }

    public function test_saving_the_policy_from_the_tab_stays_in_utilities(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.policy.update'), [
                'from'      => 'utilities',
                'loan_days' => 10,
                'max_loans' => ['Student' => 4, 'Faculty' => 6, 'Staff' => 2, 'Outside User' => 1],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->tab(['panel' => 'policy']));

        $s = new LibrarySettings();
        $this->assertSame(10, $s->loanDays());
        $this->assertSame(4, $s->maxLoans('Student'));
        $this->assertSame(6, $s->maxLoans('Faculty'));
        $this->assertSame(1, $s->maxLoans('Outside User'));
    }

    public function test_the_old_policy_page_redirect_is_unchanged(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.policy.update'), [
                'loan_days' => 7,
                'max_loans' => ['Student' => 3, 'Faculty' => 5, 'Staff' => 3, 'Outside User' => 3],
            ])
            ->assertRedirect(route('settings.policy'));
    }

    public function test_a_bad_policy_value_returns_to_the_panel_with_an_error(): void
    {
        $this->actingAs($this->admin())
            ->from($this->tab(['panel' => 'policy']))
            ->put(route('settings.policy.update'), [
                'from'      => 'utilities',
                'loan_days' => 0,
                'max_loans' => ['Student' => 3, 'Faculty' => 5, 'Staff' => 3, 'Outside User' => 3],
            ])
            ->assertSessionHasErrors('loan_days')
            ->assertRedirect($this->tab(['panel' => 'policy']));
    }

    public function test_the_group_panel_lists_each_group_with_its_patron_counts_and_policy(): void
    {
        $this->patron('S-1', 'Student');
        $this->patron('S-2', 'Student', 'inactive');
        $this->patron('F-1', 'Faculty');
        (new LibrarySettings())->set('max_loans.Student', '4');

        $this->actingAs($this->admin())
            ->get($this->tab(['panel' => 'group']))
            ->assertOk()
            ->assertSee('Groups')
            ->assertSee('Outside User')
            ->assertViewHas('groups', function (array $groups) {
                $by = collect($groups)->keyBy('type');

                return count($groups) === 4
                    && $by['Student']['total'] === 2
                    && $by['Student']['active'] === 1
                    && $by['Student']['max_loans'] === 4
                    && $by['Faculty']['total'] === 1
                    && $by['Staff']['total'] === 0
                    && $by['Outside User']['active'] === 0;
            });
    }

    public function test_an_unknown_panel_shows_the_main_settings(): void
    {
        $this->actingAs($this->admin())
            ->get($this->tab(['panel' => 'nope']))
            ->assertOk()
            ->assertSee('Report heading')
            ->assertViewHas('panel', null);
    }

    public function test_staff_cannot_open_the_panels(): void
    {
        foreach (['staff'] as $role) {
            $user = User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);

            foreach (['group', 'policy'] as $panel) {
                $this->actingAs($user)->get($this->tab(['panel' => $panel]))->assertForbidden();
            }
        }
    }
}
