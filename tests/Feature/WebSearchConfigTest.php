<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Web Search, Phase 1: the library whitelist in config/web_search.php.
 * No routes or views yet.
 */
class WebSearchConfigTest extends TestCase
{
    private function libraries(): array
    {
        return config('web_search.libraries');
    }

    public function test_the_whitelist_is_not_empty(): void
    {
        $this->assertNotEmpty($this->libraries());
    }

    public function test_every_entry_has_a_valid_key_name_and_https_url(): void
    {
        foreach ($this->libraries() as $library) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $library['key'] ?? '', 'Bad key');
            $this->assertNotEmpty(trim($library['name'] ?? ''), 'Missing name for '.($library['key'] ?? '?'));

            $url = $library['url_template'] ?? '';
            $this->assertStringStartsWith('https://', $url, 'URL must be https for '.$library['key']);

            // With the placeholder swapped for text, it must still be a valid URL.
            $this->assertNotFalse(
                filter_var(str_replace('{query}', 'test', $url), FILTER_VALIDATE_URL),
                'Invalid URL for '.$library['key']
            );
        }
    }

    public function test_keys_are_unique(): void
    {
        $keys = array_column($this->libraries(), 'key');

        $this->assertSame(count($keys), count(array_unique($keys)));
    }

    public function test_the_default_library_exists_in_the_list(): void
    {
        $this->assertContains(config('web_search.default'), array_column($this->libraries(), 'key'));
    }

    public function test_the_libraries_from_the_old_system_are_present(): void
    {
        $names = array_column($this->libraries(), 'name');

        foreach ([
            'Philippine E-Lib',
            'Ateneo De Manila University Library',
            'Ateneo De Naga University Library',
            'Australian National University Library',
            'IRRI Library',
            'La Salle Taft Library',
            'Library of Congress',
            'LibraryLink Catalog',
            'Miriam College Library',
            'National Library of the Philippines',
            'OASIS Library Catalog',
            'OhioLink',
            'UP Diliman Library',
            'UST Library',
        ] as $expected) {
            $this->assertContains($expected, $names);
        }
    }
}
