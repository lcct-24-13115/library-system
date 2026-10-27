<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Library chatbot: "where is <title>" questions, misspellings and unknown books. */
class ChatbotLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('chatbot.titles');

        Book::create([
            'accession_number' => 'B-1', 'title' => 'The Art of Accounting', 'author' => 'D. Santos',
            'call_number' => '650.91 S36', 'library' => 'College Library', 'location' => 'Shelf A2',
            'total_copies' => 1, 'available_copies' => 1,
        ]);
    }

    private function ask(string $message): array
    {
        return $this->postJson('/chatbot/message', ['message' => $message])->assertOk()->json();
    }

    public function test_where_questions_find_the_book_and_its_location(): void
    {
        foreach (['Saan mahahanap ang The Art of Accounting?', 'Art of Accounting location', 'Where is the art of accounting', 'Nasaan ang art of accounting'] as $q) {
            $r = $this->ask($q);

            $this->assertCount(1, $r['books'], $q);
            $this->assertStringContainsString('650.91 S36', $r['books'][0]['where']);
            $this->assertStringContainsString('Shelf A2', $r['books'][0]['where']);
        }
    }

    public function test_misspelled_title_gets_a_did_you_mean(): void
    {
        $r = $this->ask('Saan ang art of acounting');

        $this->assertSame([], $r['books']);
        $this->assertStringContainsString('The Art of Accounting', $r['reply']);
    }

    public function test_unknown_book_gets_a_not_found_reply(): void
    {
        $r = $this->ask('Where is Quantum Basket Weaving Zzzxx');

        $this->assertSame([], $r['books']);
        $this->assertStringContainsString('catalog', $r['reply']);
    }

    public function test_gibberish_gets_the_fallback_reply(): void
    {
        $r = $this->ask('asdf qwer zxcv uiop hjkl bnm tyu ghj');

        $this->assertStringContainsString("didn't catch that", $r['reply']);
    }
}
