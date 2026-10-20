# Phase 2 - Scan-first Return

No migration. No database change. Existing data is untouched.

## What changed
- routes/web.php: new `POST /circulation/return/scan` (name `loans.return.scan`).
- app/Http/Controllers/LoanController.php:
  - `scanReturn()`: scan an accession number -> the active loan is returned right away.
    * unknown accession / nothing on loan -> clear error, nothing changes
    * several patrons hold copies of the same accession -> never guesses; redirects to the
      search list so staff pick the right loan
  - `closeLoan()` (private): the return logic now lives in ONE place, used by both the scan
    and the existing click-Return button (same locks, same copy counts as before).
  - `returnFlash()` (private): success message + information-only notices:
    "N days overdue. Collect any fine offline." and "Reserved for NAME ... hold aside."
  - `returnForm()` also loads today's returns (latest 10).
- app/Models/Loan.php: `daysLateAtReturn()` (display only).
- resources/views/loans/return.blade.php: scan box (cursor starts there), Remarks box,
  amber overdue banner, indigo reservation banner, "Returned today" list. The old search list stays.
- tests/Feature/ScanReturnTest.php: 13 new tests.

## Still true
- No fines, amounts or receipts anywhere. Overdue never blocks a return.
- Reservations are never changed by a return.
