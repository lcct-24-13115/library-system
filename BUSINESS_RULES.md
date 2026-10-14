# SALRC Library - Business Rules (final, from the client)

These rules are fixed. Do not change or work around them.

## Money
- There are NO fines, penalties, payments, cashier or receipt features. Never add them.
- Fines are collected manually and offline by the librarian.
- The only trace in the system is the free-text **Remarks** field on a loan. No computation, no amounts, no balances.

## Borrowing
- Borrowing is direct. Overdue items never block borrowing or renewing.
- Issuing a book requires only: the patron exists, the patron is Active, and a copy is available.
- Renewing requires only that the loan has not been returned (plus the optional `LIBRARY_MAX_RENEWALS` cap, which is off by default).

## History
- KEEP borrowing history: the `loans` table, the Loan History report, and the borrowing history on the book page.

## Files and printing
- The system generates NO files and writes nothing to storage.
- Printing is browser-only (Ctrl+P / Save as PDF in the browser).

## Working rules for changes
- Small, additive changes only. No refactoring of unrelated code.
- Follow the existing code style, `layouts/library.blade.php` and Tailwind classes.
- Schema changes go in NEW migrations only. Existing data must not be lost.
- After each task run `php artisan migrate` and `php artisan test`, fix what breaks, then summarize the files changed.
- Do only the current task. Do not start the next one unprompted.

## Guarded by tests
`tests/Feature/OverdueDoesNotBlockTest.php` checks that a patron with an overdue loan can still borrow, and that an overdue loan can be renewed.
