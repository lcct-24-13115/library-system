# SALRC Library - Business Rules (final, from the client)

These rules are fixed. Do not change or work around them.

## Money
- There are NO fines, penalties, payments, cashier or receipt features. Never add them.
- Fines are collected manually and offline by the librarian.
- The only trace in the system is the free-text **Remarks** field on a loan. No computation, no amounts, no balances.

## Borrowing
- Borrowing is direct. Overdue items never block borrowing or renewing.
- Issuing a book requires: the patron exists, the patron is Active, a copy is available,
  the patron is under their type's borrow limit (`config/library.php` -> `max_loans`, set in `.env`),
  and the patron does not already have the same title on loan.
- Overdue items still NEVER block. Only the borrow limit and the duplicate-title rule do.
- Admin/librarian may override the limit on one issue (remark required). Staff cannot.
- Renewing requires only that the loan has not been returned (plus the optional `LIBRARY_MAX_RENEWALS` cap, which is off by default).

## Returning
- Returning is scan-first: scan the accession number and the active loan is returned.
- Overdue NEVER blocks a return. It only shows "N days overdue. Collect any fine offline." (no amount).
- If the same accession is out with several patrons, the system never guesses; staff pick the loan.
- If someone has reserved the book, a "hold aside" notice is shown. The reservation is not changed.

## Lost / Damaged
- Lost and Damaged are closed from the Return page and ALWAYS need a remark. There is no amount and no payment; replacement is settled offline.
- Lost: the loan is closed (outcome `lost`), `total_copies` goes down by 1, `available_copies` is NOT increased.
- Damaged: the loan is closed (outcome `damaged`). If the copy goes back on the shelf, counts are the same as a normal return. If it is no longer usable, counts are the same as Lost.
- Neither count can go below 0, and available can never be higher than total.
- A loan that is already closed cannot be marked Lost or Damaged. Older closed loans (no outcome) count as Returned.
- Lost/Damaged loans stay in the loans table and show as Lost/Damaged in the Loan History report and on the book page.

## Void (loan issued by mistake)
- Only admin and librarian can void. A reason is required. Staff cannot.
- Only a loan that is still out can be voided. The copy goes back on the shelf (`available_copies + 1`, never above `total_copies`).
- The loan row is NEVER deleted. We record who voided it, when and why (`voided_by`, `voided_at`, `void_reason`).
- A voided loan does not count toward the borrow limit and is not in the active, overdue or "returned" lists or counts. It stays in the Loan History report and on the book page with a "Voided" label.
- A voided loan cannot be returned, renewed, or marked Lost / Damaged. Voided loans are not counted as borrowing in the Summary report.

## Remarks history
- Remarks are never overwritten. Every new remark (issue, return, scan, renew, lost, damaged) is saved as a dated note with the user's name (`loan_notes`).
- The older `loans.remarks` text is kept and shown as the earlier remark. It is no longer written to.

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
`tests/Feature/VoidLoanTest.php` and `tests/Feature/LoanNotesTest.php` check the Void and Remarks rules above.
`tests/Feature/LostDamagedTest.php` checks the Lost/Damaged rules above.
`tests/Feature/OverdueDoesNotBlockTest.php` checks that a patron with an overdue loan can still borrow, and that an overdue loan can be renewed.

## Student reserve requests
- A signed-in student/faculty may request (reserve) a book from the catalog. The librarian still issues it at the desk.
- A request is a normal Pending reservation. It never holds a copy and never blocks borrowing, returning or renewing; overdue does not block it.
- No duplicate pending request for the same book, and no request for a book the patron already has on loan.
- A patron can cancel only their own pending request.
- Guarded by `tests/Feature/StudentReserveTest.php`.

## Patron import (Excel / CSV)
- Admin and librarian may add many patrons from an uploaded .xlsx / .csv. Existing ID numbers are skipped, never overwritten.
- The uploaded file is read in memory and not stored. The system generates no file; the sample sheet is a static download.
- Guarded by `tests/Feature/PatronImportTest.php`.
