# Web Search - Phase 2 of 4: Backend

No database change, no view yet. Existing features untouched (only 2 lines added to routes/web.php).

## Routes (inside the auth + role:admin,librarian,staff group)
- `GET  /web-search/libraries` (`websearch.libraries`): JSON `{ default, libraries: [{key, name}] }`. Addresses are not exposed.
- `POST /web-search/go` (`websearch.go`, throttle 30/min): body `library` (required, must be in the whitelist) and `query` (optional, max 200). Returns JSON `{ library, url }`.

## Rules
- The address comes only from `config/web_search.php`. A raw URL as `library` is rejected with 422.
- Search text is trimmed, control characters removed, then `rawurlencode`d (`& # ? /` cannot change the address).
- Empty search text: the library's home page (`scheme://host/`).
- A library whose template has no `{query}` just opens that page.
- Search text is not saved or logged. Patrons get 403, guests go to login.

## Files
- `app/Http/Controllers/WebSearchController.php` (new)
- `routes/web.php` (import + 2 routes)
- `tests/Feature/WebSearchTest.php` (new, 11 tests)

## Next
Phase 3: the Web Search button and modal in the staff navigation.
