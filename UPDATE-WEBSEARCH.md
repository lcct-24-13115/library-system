# Web Search (4 phases) - summary

Search an external library website from the library system, like "WebSearch / WEB NAVIGATION" in the old system.
No database change. Staff only (admin, librarian, staff).

## How it works
1. Staff clicks **Web Search** in the sidebar (Library group), or **Search this title online** on a book page.
2. In the modal: type the search text, pick a library, click **WEB NAVIGATION**.
3. The server builds the address from `config/web_search.php` and the library opens in a new tab.

## Phases
| Phase | What | Main files |
|---|---|---|
| 1 | Library whitelist | `config/web_search.php`, `tests/Feature/WebSearchConfigTest.php` |
| 2 | Backend | `WebSearchController`, routes `websearch.libraries` and `websearch.go`, `tests/Feature/WebSearchTest.php` |
| 3 | Button and modal | `partials/web-search.blade.php`, `_sidebar`, `library` layout, `globe` icon, `WebSearchModalTest` |
| 4 | Remember last library, "Search this title online", docs | `books/show.blade.php`, modal script, `WebSearchPhase4Test` |

## Libraries (14, same as the old system's dropdown)
Ateneo De Manila, Ateneo De Naga, Australian National University, IRRI, La Salle Taft, Library of Congress, LibraryLink Catalog, Miriam College, National Library of the Philippines, OASIS Library Catalog, OhioLink, Philippine E-Lib (default), UP Diliman, UST.
LibraryLink and OASIS use a temporary Google search until their real catalog address is known.

## Safety
- The user never sends a URL, only a library key. Unknown keys are rejected (422).
- Search text is encoded, limited to 200 characters, never saved or logged.
- Library addresses are not sent to the browser (only key and name).
- Routes are inside the staff group (patrons 403, guests go to login), throttled 30/min.
- The new tab is opened and its opener link is cut (`win.opener = null`). No iframe (library sites block it).

## Add or fix a library
Edit `config/web_search.php`:
- `url_template` with `{query}` = search text goes there (URL-encoded). Without it, the page just opens.
- `verified => false` means the address is a best guess: open it in a browser and correct it.
Then run `php artisan config:clear`.

## Known limitations
- Philippine E-Lib opens its Basic Search page without the search text (its search address pattern is not confirmed).
- Libraries marked `verified => false` open the site home page until their address is checked.
- The remembered library is per browser (localStorage), not per user account.
- Pop-up blockers may block the new tab; the modal then shows an "Open it here" link.
