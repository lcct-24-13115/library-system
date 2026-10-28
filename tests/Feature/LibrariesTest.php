<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Libraries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Libraries, Phase 1: exactly three libraries, one list used everywhere. */
class LibrariesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['user_type' => 'Non-Teaching Personnel', 'is_active' => true]);
    }

    public function test_there_are_exactly_three_libraries_in_the_legacy_order(): void
    {
        $this->assertSame(
            ['College Library', 'Grade School Library', 'High School Library'],
            Libraries::names()
        );
        $this->assertSame(['College', 'Grade School', 'High School'], Libraries::labels());
    }

    public function test_label_for_a_name(): void
    {
        $this->assertSame('Grade School', Libraries::labelFor('Grade School Library'));
        $this->assertNull(Libraries::labelFor('Made Up Library'));
        $this->assertNull(Libraries::labelFor(null));
    }

    public function test_from_any_accepts_name_label_and_slug_in_any_case(): void
    {
        foreach (['College Library', 'college library', 'COLLEGE', 'College', ' college '] as $value) {
            $this->assertSame('College Library', Libraries::fromAny($value), $value);
        }

        foreach (['Grade School Library', 'grade school', 'grade-school', 'GRADE_SCHOOL'] as $value) {
            $this->assertSame('Grade School Library', Libraries::fromAny($value), $value);
        }

        foreach (['High School Library', 'high school', 'high-school', 'HIGH SCHOOL'] as $value) {
            $this->assertSame('High School Library', Libraries::fromAny($value), $value);
        }
    }

    public function test_from_any_returns_null_for_blank_unknown_and_all(): void
    {
        foreach ([null, '', '   ', 'All libraries', 'Junior High School Library', 'Senior High School Library', 'Made Up'] as $value) {
            $this->assertNull(Libraries::fromAny($value), (string) $value);
        }
    }

    public function test_the_old_user_library_list_is_gone(): void
    {
        $this->assertFalse(defined(User::class . '::LIBRARIES'));
        $this->assertNull(config('rda.libraries'));
    }

    public function test_the_user_form_offers_the_three_libraries_only(): void
    {
        $this->actingAs($this->admin())
            ->get(route('utilities.index', ['tab' => 'administration']))
            ->assertOk()
            ->assertSee('College Library')
            ->assertSee('Grade School Library')
            ->assertSee('High School Library')
            ->assertDontSee('Junior High School Library')
            ->assertDontSee('Senior High School Library');
    }

    public function test_an_account_can_be_assigned_to_high_school_library(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Hi Sch', 'email' => 'hs@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'staff', 'user_type' => 'Senior High School Faculty',
            'library' => 'High School Library',
        ])->assertSessionHasNoErrors();

        $this->assertSame('High School Library', User::where('email', 'hs@example.com')->first()->library);
    }

    public function test_the_old_junior_and_senior_high_libraries_are_rejected_for_accounts(): void
    {
        foreach (['Junior High School Library', 'Senior High School Library'] as $old) {
            $this->actingAs($this->admin())->post(route('users.store'), [
                'name' => 'Old', 'email' => 'old' . md5($old) . '@example.com',
                'password' => 'password123', 'password_confirmation' => 'password123',
                'role' => 'staff', 'user_type' => 'Lower School Faculty', 'library' => $old,
            ])->assertSessionHasErrors('library');
        }
    }

    public function test_the_book_form_has_the_three_libraries_and_no_all_libraries_option(): void
    {
        $this->actingAs($this->admin())
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('College Library')
            ->assertSee('Grade School Library')
            ->assertSee('High School Library')
            ->assertDontSee('<option value="All libraries"', false);
    }

    public function test_a_book_cannot_be_saved_with_an_unknown_library(): void
    {
        $this->actingAs($this->admin())->post(route('books.store'), [
            'accession_number' => 'LIB-1', 'title' => 'T', 'author' => 'A',
            'total_copies' => 1, 'library' => 'Made Up Library',
        ])->assertSessionHasErrors('library');

        $this->assertDatabaseMissing('books', ['accession_number' => 'LIB-1']);
    }

    public function test_a_book_can_be_saved_in_a_real_library_or_without_one(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('books.store'), [
            'accession_number' => 'LIB-2', 'title' => 'T', 'author' => 'A',
            'total_copies' => 1, 'library' => 'Grade School Library',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('books.store'), [
            'accession_number' => 'LIB-3', 'title' => 'T', 'author' => 'A',
            'total_copies' => 1, 'library' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', ['accession_number' => 'LIB-2', 'library' => 'Grade School Library']);
        $this->assertDatabaseHas('books', ['accession_number' => 'LIB-3', 'library' => null]);
    }
}
