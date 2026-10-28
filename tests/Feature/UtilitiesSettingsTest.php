<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Setting;
use App\Models\User;
use App\Support\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Utilities hub, Phase 3: Settings tab (excluded days, report heading, picture path, holidays).
 */
class UtilitiesSettingsTest extends TestCase
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

    private function fresh(): LibrarySettings
    {
        return new LibrarySettings();
    }

    public function test_the_settings_tab_shows_the_saved_values_and_holidays(): void
    {
        $settings = $this->fresh();
        $settings->set('report_heading', 'MY LIBRARY');
        $settings->set('picture_path', 'D:\\Pictures');
        Holiday::create(['date' => '2026-12-25', 'reason' => 'Christmas Day']);

        $this->actingAs($this->admin())
            ->get($this->tab())
            ->assertOk()
            ->assertSee('Report heading')
            ->assertSee('value="MY LIBRARY"', false)
            ->assertSee('Picture path')
            ->assertSee('D:\\Pictures', false)
            ->assertSee('Christmas Day')
            ->assertSee('Monday')
            ->assertSee('Sunday');
    }

    public function test_saving_from_the_tab_stores_everything_and_returns_to_the_tab(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.update'), [
                'from'            => 'utilities',
                'excluded_days'   => [6, 0],
                'report_heading'  => 'LA CONSOLACION COLLEGE LIBRARY',
                'picture_path'    => 'D:\\Pictures',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->tab());

        $s = $this->fresh();
        $this->assertEqualsCanonicalizing([6, 0], $s->excludedDays());
        $this->assertSame('LA CONSOLACION COLLEGE LIBRARY', $s->reportHeading());
        $this->assertSame('D:\\Pictures', $s->picturePath());
    }

    public function test_the_old_settings_page_still_works_and_does_not_wipe_the_picture_path(): void
    {
        $this->fresh()->set('picture_path', 'D:\\Pictures');

        $this->actingAs($this->admin())
            ->put(route('settings.update'), ['excluded_days' => [0], 'report_heading' => 'X'])
            ->assertRedirect(route('settings.index'));

        $this->assertSame('D:\\Pictures', $this->fresh()->picturePath());
        $this->assertSame('X', $this->fresh()->reportHeading());
    }

    public function test_a_blank_picture_path_clears_it(): void
    {
        $this->fresh()->set('picture_path', 'D:\\Pictures');

        $this->actingAs($this->admin())
            ->put(route('settings.update'), ['from' => 'utilities', 'picture_path' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->fresh()->picturePath());
    }

    public function test_the_picture_path_is_validated(): void
    {
        $admin = $this->admin();

        foreach (['D:\\Pictures\\..\\Secret', str_repeat('a', 256)] as $bad) {
            $this->actingAs($admin)
                ->from($this->tab())
                ->put(route('settings.update'), ['from' => 'utilities', 'picture_path' => $bad])
                ->assertSessionHasErrors('picture_path')
                ->assertRedirect($this->tab());
        }

        $this->assertNull($this->fresh()->picturePath());
    }

    public function test_adding_a_holiday_from_the_tab(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.holidays.store'), ['from' => 'utilities', 'date' => '2026-12-25', 'reason' => 'Christmas'])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->tab());

        $this->assertDatabaseHas('holidays', ['reason' => 'Christmas']);
    }

    public function test_a_duplicate_holiday_date_is_refused(): void
    {
        Holiday::create(['date' => '2026-12-25']);

        $this->actingAs($this->admin())
            ->from($this->tab())
            ->post(route('settings.holidays.store'), ['from' => 'utilities', 'date' => '2026-12-25'])
            ->assertSessionHasErrors('date');

        $this->assertSame(1, Holiday::count());
    }

    public function test_edit_mode_loads_the_chosen_holiday(): void
    {
        $h = Holiday::create(['date' => '2026-11-01', 'reason' => 'All Saints Day']);

        $this->actingAs($this->admin())
            ->get($this->tab(['edit_holiday' => $h->id]))
            ->assertOk()
            ->assertSee('value="2026-11-01"', false)
            ->assertSee('value="All Saints Day"', false)
            ->assertSee('Update');
    }

    public function test_an_unknown_holiday_id_shows_the_add_form(): void
    {
        $this->actingAs($this->admin())
            ->get($this->tab(['edit_holiday' => 999999]))
            ->assertOk()
            ->assertSee('Add');
    }

    public function test_editing_a_holiday_changes_the_date_and_what_counts_as_overdue(): void
    {
        $h = Holiday::create(['date' => '2026-11-02', 'reason' => 'Old']);

        $this->actingAs($this->admin())
            ->put(route('settings.holidays.update', $h), ['from' => 'utilities', 'date' => '2026-11-03', 'reason' => 'New'])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->tab());

        $h->refresh();
        $this->assertSame('New', $h->reason);
        $this->assertSame('2026-11-03', Carbon::parse($h->date)->toDateString());

        $s = $this->fresh();
        $this->assertTrue($s->isCounted(Carbon::parse('2026-11-02')));
        $this->assertFalse($s->isCounted(Carbon::parse('2026-11-03')));
    }

    public function test_a_holiday_can_be_saved_with_its_own_date_but_not_another_holidays_date(): void
    {
        $a = Holiday::create(['date' => '2026-11-02', 'reason' => 'A']);
        Holiday::create(['date' => '2026-11-03', 'reason' => 'B']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('settings.holidays.update', $a), ['from' => 'utilities', 'date' => '2026-11-02', 'reason' => 'A renamed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('A renamed', $a->fresh()->reason);

        $this->actingAs($admin)
            ->from($this->tab())
            ->put(route('settings.holidays.update', $a), ['from' => 'utilities', 'date' => '2026-11-03'])
            ->assertSessionHasErrors('date');
    }

    public function test_delete_and_empty_return_to_the_tab(): void
    {
        $h = Holiday::create(['date' => '2026-11-02']);
        Holiday::create(['date' => '2026-11-03']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('settings.holidays.destroy', $h), ['from' => 'utilities'])
            ->assertRedirect($this->tab());
        $this->assertSame(1, Holiday::count());

        $this->actingAs($admin)
            ->post(route('settings.holidays.empty'), ['from' => 'utilities'])
            ->assertRedirect($this->tab());
        $this->assertSame(0, Holiday::count());
    }

    public function test_non_admins_cannot_edit_settings_or_holidays(): void
    {
        $h = Holiday::create(['date' => '2026-11-02']);

        foreach (['librarian', 'staff'] as $role) {
            $user = User::factory()->create(['role' => $role, 'user_type' => 'Non-Teaching Personnel', 'is_active' => true]);

            $this->actingAs($user)->get($this->tab())->assertForbidden();
            $this->actingAs($user)->put(route('settings.update'), ['picture_path' => 'X'])->assertForbidden();
            $this->actingAs($user)->put(route('settings.holidays.update', $h), ['date' => '2026-12-01'])->assertForbidden();
        }

        $this->assertNull(Setting::where('key', 'picture_path')->first());
    }
}
