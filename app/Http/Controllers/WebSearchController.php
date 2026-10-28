<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Web Search (like "WebSearch" / "WEB NAVIGATION" in the old system).
 *
 * The user only picks a library `key` and types a search text. The address that
 * is returned is ALWAYS built from config/web_search.php, never from user input,
 * so this cannot be used to open an arbitrary site (no open redirect).
 * Nothing is saved or logged.
 */
class WebSearchController extends Controller
{
    /** Libraries for the dropdown: key + name only (the addresses stay on the server). */
    public function libraries(): JsonResponse
    {
        $libraries = collect(config('web_search.libraries', []))
            ->map(fn (array $library) => [
                'key'  => $library['key'],
                'name' => $library['name'],
            ])
            ->values();

        return response()->json([
            'default'   => config('web_search.default'),
            'libraries' => $libraries,
        ]);
    }

    /** Build the final address for the chosen library and search text. */
    public function go(Request $request): JsonResponse
    {
        $keys = collect(config('web_search.libraries', []))->pluck('key')->all();

        $data = $request->validate([
            'library' => ['required', 'string', 'in:'.implode(',', $keys)],
            'query'   => ['nullable', 'string', 'max:200'],
        ]);

        $library = collect(config('web_search.libraries'))->firstWhere('key', $data['library']);

        return response()->json([
            'library' => $library['name'],
            'url'     => $this->buildUrl($library['url_template'], $data['query'] ?? ''),
        ]);
    }

    private function buildUrl(string $template, string $query): string
    {
        // Remove control characters and extra spaces from the search text.
        $query = trim((string) preg_replace('/[\x00-\x1F\x7F\s]+/u', ' ', $query));

        // This library has no search text support: just open its page.
        if (! str_contains($template, '{query}')) {
            return $template;
        }

        // Empty search text: open the library's home page instead of an empty search.
        if ($query === '') {
            $parts = parse_url(str_replace('{query}', '', $template));

            return $parts['scheme'].'://'.$parts['host'].'/';
        }

        return str_replace('{query}', rawurlencode($query), $template);
    }
}
