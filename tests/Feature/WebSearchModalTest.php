<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web Search, Phase 3: the sidebar button and modal (staff layout only).
 */
class WebSearchModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_pages_show_the_web_search_button_and_modal(): void
    {
        foreach (['staff', 'librarian', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('books.index'))
                ->assertOk()
                ->assertSee('data-websearch-open', false)
                ->assertSee('id="salrc-websearch"', false)
                ->assertSee('Web Navigation')
                ->assertSee(route('websearch.libraries', [], false), false)
                ->assertSee(route('websearch.go', [], false), false);
        }
    }

    public function test_the_modal_never_contains_library_addresses(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('books.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('elib.gov.ph', $html);
        $this->assertStringNotContainsString('loc.gov', $html);
    }

    public function test_the_public_catalog_does_not_get_the_modal(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertDontSee('salrc-websearch', false);
    }
}
