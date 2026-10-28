# Web Search - Phase 1 of 4: Library list

Config only. No database change, no route, no view. Nothing existing was touched.

## Added
- `config/web_search.php`: whitelist of external libraries (`key`, `name`, `url_template`, `verified`, `note`) and the `default` library (Philippine E-Lib).
- `tests/Feature/WebSearchConfigTest.php`: checks keys, names, https URLs, unique keys, default exists, and that the 9 libraries of the old system are present.

## How a library works
- `{query}` in `url_template` is replaced by the URL-encoded search text (done in Phase 2).
- No `{query}` = the library page just opens.
- `verified => false` means the address is a best guess. Open it in a browser and fix it before the defense.

## To add a library
Copy a block in `config/web_search.php`, change the values, run `php artisan config:clear`.

## Next
Phase 2: `WebSearchController` + routes (`websearch.libraries`, `websearch.go`).
