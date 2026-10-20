# Phase 1 - Borrow limit + duplicate-title check

No new migration. No database change. Existing data is untouched.

## What changed
- config/library.php: new `max_loans` (Student 3, Faculty 5, Staff 3, Outside User 3, default 3), editable in `.env`.
- app/Models/Patron.php: `maxLoans()` helper.
- app/Http/Controllers/LoanController.php (`store`): inside the existing transaction, after the book lock:
  1. blocks if the patron already has the same title on loan (no override),
  2. blocks if active loans >= limit, unless an admin/librarian ticks "Override limit" AND writes a remark.
  Overdue loans count as active loans, but overdue by itself never blocks.
- app/Http/Controllers/LookupController.php: patron preview also returns `max_loans`.
- resources/views/partials/lookup-preview.blade.php: shows "On loan: X of Y allowed" and an at-limit notice.
- resources/views/loans/create.blade.php: "Override borrow limit" checkbox (admin/librarian only).
- .env.example, BUSINESS_RULES.md: documented.
- tests/Feature/BorrowLimitTest.php: 11 new tests. OverdueDoesNotBlockTest is unchanged.

## Change the limits (no code needed)
Edit `.env`:
    LIBRARY_MAX_LOANS_STUDENT=3
    LIBRARY_MAX_LOANS_FACULTY=5
then run `php artisan config:clear`.
