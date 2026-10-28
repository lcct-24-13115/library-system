<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Loan;
use App\Models\Book;
use App\Models\Patron;
use App\Models\User;
use App\Support\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6: user Library field, Users list columns, Settings page
 * (excluded days, report heading, holidays / suspensions) and Group / Policy.
 */
class AdminSettingsTest extends TestCase
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

    private function overdueLoan(\Illuminate\Support\Carbon $due): Loan
    {
        $patron = Patron::create(['id_number' => 'S-1', 'name' => 'P', 'patron_type' => 'Student', 'status' => 'active']);
        $book = Book::create(['accession_number' => 'S-ACC', 'title' => 'T', 'author' => 'A', 'total_copies' => 1, 'available_copies' => 0]);

        return Loan::create([
            'book_id' => $book->id, 'patron_id' => $patron->id,
            'issued_at' => $due->copy()->subDays(7), 'due_date' => $due,
        ]);
    }

    public function test_admin_can_save_a_library_on_a_user_and_blank_means_all_libraries(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Ems', 'email' => 'ems@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'librarian', 'user_type' => 'Senior High School Faculty',
            'library' => 'High School Library',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('High School Library', User::where('email', 'ems@example.com')->first()->library);
        $this->assertSame('All libraries', $admin->fresh()->libraryLabel());
    }

    public function test_an_unknown_library_is_rejected(): void
    {
        $this->actingAs($this->user('admin'))->post(route('users.store'), [
            'name' => 'X', 'email' => 'x@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'staff', 'user_type' => 'Lower School Faculty', 'library' => 'Made Up Library',
        ])->assertSessionHasErrors('library');
    }

    public function test_users_list_shows_library_column(): void
    {
        $admin = $this->user('admin');
        User::factory()->create(['role' => 'staff', 'user_type' => 'College Faculty', 'library' => 'College Library']);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()->assertSee('College Library')->assertSee('All libraries');
    }

    public function test_admin_and_librarian_can_open_settings_but_staff_cannot(): void
    {
        $this->actingAs($this->user('librarian'))->get(route('settings.index'))->assertOk();
        $this->actingAs($this->user('staff'))->get(route('settings.policy'))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('settings.index'))->assertOk();
        $this->actingAs($this->user('admin'))->get(route('settings.policy'))->assertOk();
    }

    public function test_excluded_days_and_report_heading_are_saved(): void
    {
        $this->actingAs($this->user('admin'))->put(route('settings.update'), [
            'excluded_days'  => [6, 0],
            'report_heading' => 'LA CONSOLACION COLLEGE LIBRARY',
        ])->assertRedirect(route('settings.index'));

        $s = app(LibrarySettings::class);
        $this->assertEqualsCanonicalizing([6, 0], $s->excludedDays());
        $this->assertSame('LA CONSOLACION COLLEGE LIBRARY', $s->reportHeading());
    }

    public function test_days_overdue_is_a_plain_day_count_when_nothing_is_excluded(): void
    {
        $loan = $this->overdueLoan(today()->subDays(10));

        $this->assertSame(10, $loan->daysOverdue());
    }

    public function test_excluded_weekdays_and_holidays_are_not_counted_as_days_overdue(): void
    {
        $admin = $this->user('admin');
        $due = today()->subDays(14);
        $loan = $this->overdueLoan($due);

        $this->actingAs($admin)->put(route('settings.update'), ['excluded_days' => [6, 0]]);
        // 14 days always contain exactly two Saturdays and two Sundays.
        $this->assertSame(10, $loan->fresh()->daysOverdue());

        // A holiday on a counted weekday takes one more day off.
        $day = $due->copy()->addDay();
        while (in_array($day->dayOfWeek, [0, 6], true)) {
            $day->addDay();
        }
        $this->actingAs($admin)->post(route('settings.holidays.store'), ['date' => $day->toDateString(), 'reason' => 'Suspension']);
        $this->assertSame(9, $loan->fresh()->daysOverdue());
    }

    public function test_holidays_can_be_added_removed_and_emptied(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('settings.holidays.store'), ['date' => '2026-12-25', 'reason' => 'Christmas'])
            ->assertRedirect(route('settings.index'));
        $this->actingAs($admin)->post(route('settings.holidays.store'), ['date' => '2026-12-25'])
            ->assertSessionHasErrors('date');
        $this->assertSame(1, Holiday::count());

        $this->actingAs($admin)->delete(route('settings.holidays.destroy', Holiday::first()));
        $this->assertSame(0, Holiday::count());

        Holiday::create(['date' => '2026-01-01']);
        Holiday::create(['date' => '2026-01-02']);
        $this->actingAs($admin)->post(route('settings.holidays.empty'));
        $this->assertSame(0, Holiday::count());
    }

    public function test_policy_changes_the_borrow_limit_and_default_loan_days(): void
    {
        $this->actingAs($this->user('admin'))->put(route('settings.policy.update'), [
            'loan_days' => 3,
            'max_loans' => ['Student' => 1, 'Faculty' => 8, 'Staff' => 2, 'Outside User' => 1],
        ])->assertRedirect(route('settings.policy'));

        $student = Patron::create(['id_number' => 'S-2', 'name' => 'S', 'patron_type' => 'Student', 'status' => 'active']);

        $this->assertSame(1, $student->maxLoans());
        $this->assertSame(3, app(LibrarySettings::class)->loanDays());
    }

    public function test_without_saved_settings_the_old_config_values_still_apply(): void
    {
        $student = Patron::create(['id_number' => 'S-3', 'name' => 'S', 'patron_type' => 'Student', 'status' => 'active']);

        $this->assertSame((int) config('library.max_loans.Student'), $student->maxLoans());
        $this->assertSame((int) config('library.loan_days'), app(LibrarySettings::class)->loanDays());
    }
}
