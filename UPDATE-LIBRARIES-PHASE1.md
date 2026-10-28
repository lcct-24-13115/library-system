# Libraries, Phase 1: three libraries, one list

The school has three libraries, like the legacy system (La Consolacion College - Tanauan):
**College Library**, **Grade School Library**, **High School Library**.

## What changed
- `config/library.php` has the one list (`libraries`): name (stored in the database), label (College / Grade School / High School), slug (for URLs).
- `app/Support/Libraries.php` (new): `names()`, `labels()`, `labelFor($name)`, `fromAny($value)`.
  `fromAny` accepts a name, label or slug in any letter case ("grade-school", "HIGH SCHOOL") and returns the exact name, or null for blank, unknown values and "All libraries".
- `User::LIBRARIES` is removed. `config('rda.libraries')` is removed. Everything reads `Libraries::names()`.
- Migration `2026_10_28_000001_unify_libraries.php`:
  - accounts with "Junior High School Library" or "Senior High School Library" become "High School Library";
  - index on `books.library`. No book data is changed.
- Book form: the Library select has the three libraries. The "All libraries" option is gone (it is a search filter, not a place a book belongs to).
- User forms (Utilities > Administration, Users > Add / Edit): three libraries. Blank still means "All libraries" for the account.
- Validation: `UserController` and `BookController` accept only the three libraries (blank is allowed).
- Reports > Accession list: the Library filter uses the same list.

## Files
- config/library.php, config/rda.php
- app/Support/Libraries.php (new)
- app/Models/User.php, app/Http/Controllers/UserController.php, app/Http/Controllers/BookController.php
- resources/views/books/_form.blade.php, resources/views/users/_form.blade.php, resources/views/utilities/_administration.blade.php, resources/views/reports/accession.blade.php
- database/migrations/2026_10_28_000001_unify_libraries.php (new)
- tests/Feature/LibrariesTest.php (new), tests/Feature/AdminSettingsTest.php (uses High School Library)

## Notes
- Existing books keep their `library` value (all empty for the demo data). Phase 2 loads the real catalog.
- Run `php artisan migrate` (never migrate:fresh).
