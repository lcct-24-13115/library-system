# Phase 4 - Void loan and remarks history

TWO new additive migrations. No existing row is changed or deleted.

## Database
- `2026_10_22_000001_add_void_columns_to_loans_table.php`: nullable `loans.voided_at`, `voided_by` (user id), `void_reason` (500). All NULL for existing rows.
- `2026_10_22_000002_create_loan_notes_table.php`: new `loan_notes` (loan_id, user_id, note, timestamps).
  The old `loans.remarks` column is NOT changed or migrated.

## Void
- `POST /loans/{loan}/void` (`loans.void`), inside the admin + librarian route group (staff get 403; the controller also checks).
- `LoanController::void()`: reason required; only a loan still out; locks the loan and the book;
  records voided_at / voided_by / void_reason; `available_copies + 1` (never above total). Never deletes the row.
- `Loan` model: `isVoided()`, `scopeVoided()`, `voidedBy()`, status `voided`. `active()` and `overdue()` now also require `voided_at IS NULL`,
  which frees the borrow-limit slot and removes the loan from the active / overdue lists, dashboard, lookups and patron profile.
- `PatronController`: the raw `whereNull('returned_at')` count now uses `active()`.
- A voided loan cannot be returned, renewed, lost or damaged (guards in `closeLoan()` and `renew()`).
- Loan History (report and Loans page) and the book page show a "Voided" badge; the reason, user and date show next to it.
  New "Voided" option in the status filters.
- Summary report no longer counts voided loans as borrowing.

## Remarks history
- Every new remark is saved as a `loan_notes` row (date + user name). Nothing is overwritten.
  Sources: Issue (incl. limit override remark), Return, Scan return, Renew, Lost ("LOST: ..."), Damaged ("DAMAGED: ...").
- A blank remark adds nothing. The old `loans.remarks` text still shows as the earlier remark but is no longer written.
- `resources/views/loans/_notes.blade.php` shows old remark + notes + void info (Loans page, Loan History, Overdue report, patron profile).
- The Return page no longer pre-fills the old remark (it would be saved again as a new note).
- Removed the Phase 3 `appendRemark()` helper (replaced by notes).

## Tests
- New: `VoidLoanTest` (16), `LoanNotesTest` (8).
- Updated for the new remark behaviour: `ScanReturnTest::test_remarks_are_saved_and_kept_when_blank`,
  `LostDamagedTest::test_the_earlier_remark_is_kept_and_the_new_one_is_added_as_a_note`.

## Not changed
- Borrow limit rules, overdue rules (never blocks), reservations, Lost/Damaged copy counts, BookController.
- No fines, amounts, payments or receipts. No generated files.
