<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web Search, Phase 2: libraries list + address builder. Backend only.
 */
class WebSearchTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create();
    }

    public function test_libraries_endpoint_returns_key_and_name_only(): void
    {
        $response = $this->actingAs($this->staff())->getJson(route('websearch.libraries'))->assertOk();

        $response->assertJsonPath('default', config('web_search.default'));
        $this->assertCount(count(config('web_search.libraries')), $response->json('libraries'));

        foreach ($response->json('libraries') as $library) {
            $this->assertSame(['key', 'name'], array_keys($library));
        }
    }

    public function test_valid_library_with_search_text_returns_the_encoded_address(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['library' => 'library-of-congress', 'query' => 'Noli Me Tangere'])
            ->assertOk()
            ->assertJsonPath('url', 'https://www.loc.gov/search/?q=Noli%20Me%20Tangere')
            ->assertJsonPath('library', 'Library of Congress');
    }

    public function test_special_characters_are_encoded_and_cannot_break_out_of_the_address(): void
    {
        $url = $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['library' => 'library-of-congress', 'query' => 'a&b=c#d?e/../x'])
            ->assertOk()
            ->json('url');

        $this->assertSame('https://www.loc.gov/search/?q=a%26b%3Dc%23d%3Fe%2F..%2Fx', $url);
    }

    public function test_empty_search_text_opens_the_library_home_page(): void
    {
        foreach ([null, '', '   '] as $query) {
            $this->actingAs($this->staff())
                ->postJson(route('websearch.go'), ['library' => 'library-of-congress', 'query' => $query])
                ->assertOk()
                ->assertJsonPath('url', 'https://www.loc.gov/');
        }
    }

    public function test_library_without_search_support_just_opens_its_page(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['library' => 'philippine-elib', 'query' => 'history'])
            ->assertOk()
            ->assertJsonPath('url', 'https://www.elib.gov.ph/bsearch.php');
    }

    public function test_unknown_library_is_rejected(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['library' => 'evil-site', 'query' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('library');
    }

    public function test_a_raw_url_is_never_accepted_as_the_library(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['library' => 'https://evil.example.com', 'query' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('library');
    }

    public function test_library_is_required_and_search_text_has_a_length_limit(): void
    {
        $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['query' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('library');

        $this->actingAs($this->staff())
            ->postJson(route('websearch.go'), ['library' => 'library-of-congress', 'query' => str_repeat('a', 201)])
            ->assertStatus(422)->assertJsonValidationErrors('query');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('websearch.libraries'))->assertRedirect(route('login'));
        $this->post(route('websearch.go'), ['library' => 'library-of-congress'])->assertRedirect(route('login'));
    }

    public function test_patrons_are_forbidden(): void
    {
        $patron = User::factory()->create(['role' => 'patron']);

        $this->actingAs($patron)->getJson(route('websearch.libraries'))->assertForbidden();
        $this->actingAs($patron)->postJson(route('websearch.go'), ['library' => 'library-of-congress'])->assertForbidden();
    }

    public function test_all_staff_roles_can_use_it(): void
    {
        foreach (['staff', 'librarian', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson(route('websearch.libraries'))->assertOk();
        }
    }
}
