<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catalog search: typing "the" finds the books with "the", best matches first.
 */
class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    private function book(string $acc, string $title, string $author = 'Someone'): Book
    {
        return Book::create([
            'accession_number' => $acc,
            'title'            => $title,
            'author'           => $author,
            'total_copies'     => 1,
            'available_copies' => 1,
        ]);
    }

    private function seedBooks(): void
    {
        $this->book('B-1', 'Mathematics Made Easy');   // "the" only inside a longer word
        $this->book('B-2', 'Lord of the Rings');       // "the" as a whole word
        $this->book('B-3', 'The Little Prince');       // starts with "the"
        $this->book('B-4', 'Algebra Basics');          // no "the" anywhere
    }

    public function test_staff_search_for_the_lists_matches_best_first(): void
    {
        $this->seedBooks();

        $this->actingAs(User::factory()->librarian()->create())
            ->get(route('books.index', ['search' => 'the']))
            ->assertOk()
            ->assertSeeInOrder(['The Little Prince', 'Lord of the Rings', 'Mathematics Made Easy'])
            ->assertDontSee('Algebra Basics')
            ->assertSee('3</span> books found', false);
    }

    public function test_every_word_must_match_in_any_order(): void
    {
        $this->seedBooks();

        $this->actingAs(User::factory()->librarian()->create())
            ->get(route('books.index', ['search' => 'prince little']))
            ->assertOk()
            ->assertSee('The Little Prince')
            ->assertDontSee('Lord of the Rings');
    }

    public function test_search_still_finds_by_accession_number_and_author(): void
    {
        $this->book('ACC-777', 'Quiet Book', 'Ana Reyes');

        $librarian = User::factory()->librarian()->create();

        $this->actingAs($librarian)->get(route('books.index', ['search' => 'ACC-777']))->assertSee('Quiet Book');
        $this->actingAs($librarian)->get(route('books.index', ['search' => 'reyes']))->assertSee('Quiet Book');
    }

    public function test_public_catalog_ranks_the_same_way_and_hides_accession_search(): void
    {
        $this->seedBooks();

        $this->get(route('catalog.index', ['q' => 'the']))
            ->assertOk()
            ->assertSeeInOrder(['The Little Prince', 'Lord of the Rings', 'Mathematics Made Easy'])
            ->assertDontSee('Algebra Basics');

        // accession numbers are not searchable by the public
        $this->get(route('catalog.index', ['q' => 'B-3']))->assertDontSee('The Little Prince');
    }

    public function test_no_search_still_lists_the_newest_books(): void
    {
        $this->seedBooks();

        $this->actingAs(User::factory()->librarian()->create())
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Algebra Basics')
            ->assertSee('Click a book on the shelf to open it.');
    }
}
