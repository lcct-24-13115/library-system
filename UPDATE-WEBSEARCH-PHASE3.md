# Web Search - Phase 3 of 4: Interface

No database change. Staff layout only (admin, librarian, staff). Patrons and the public catalog do not get it.

## What the user sees
- Sidebar, group "Library": new **Web Search** button (globe icon).
- It opens a modal: search expression, library dropdown (default Philippine E-Lib), **WEB NAVIGATION** button.
- On click: POST `websearch.go`, then the returned address opens in a NEW tab (`noopener,noreferrer`). No iframe.
- Enter submits; Esc, the X button or a click outside closes it. Loading state on the button, clear error messages.
- If the browser blocks the new tab, an "Open it here" link appears.

## Files
- `resources/views/partials/web-search.blade.php` (new): modal, its own CSS and plain JS (text inserted with textContent).
- `resources/views/layouts/_sidebar.blade.php`: Web Search button in the Library group.
- `resources/views/layouts/library.blade.php`: `@include('partials.web-search')`.
- `resources/views/components/icon.blade.php`: new `globe` icon.
- `tests/Feature/WebSearchModalTest.php` (new, 3 tests).

## Next
Phase 4: remember the last library, "Search this title online" on the book page, final checks.
