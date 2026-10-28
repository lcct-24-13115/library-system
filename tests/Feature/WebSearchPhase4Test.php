<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web Search, Phase 4: remembered library and "Search this title online".
 */
class WebSearchPhase4Test extends TestCase
{
    use RefreshDatabase;

    private function book(string $title = 'Noli Me Tangere'): Book
    {
        return Book::create([
            'accession_number' => 'ACC-WS1',
            'title'            => $title,
            'author'           => 'Jose Rizal',
            'total_copies'     => 1,
            'available_copies' => 1,
        ]);
    }

    public function test_book_page_has_a_search_this_title_online_button_with_the_title(): void
    {
        $book = $this->book();

        $this->actingAs(User::factory()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Search this title online')
            ->assertSee('data-websearch-query="Noli Me Tangere"', false)
            ->assertSee('id="salrc-websearch"', false);
    }

    public function test_the_book_title_is_escaped_in_the_button(): void
    {
        $book = $this->book('"><script>alert(1)</script>');

        $html = $this->actingAs(User::factory()->create())
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-websearch-query=""><script>', $html);
    }

    public function test_the_modal_remembers_the_library_in_a_guarded_local_storage(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('salrc.websearch.library', $html);
        $this->assertStringContainsString('localStorage.setItem', $html);
        $this->assertMatchesRegularExpression('/try \{[^}]*localStorage/', $html);
    }
}
