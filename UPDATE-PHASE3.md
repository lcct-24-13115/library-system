# Phase 3 - Lost / Damaged

One NEW additive migration. No existing row is changed.

## Database
- `2026_10_21_000001_add_outcome_to_loans_table.php`: nullable `loans.outcome` (string, 20).
  Values: `returned` | `lost` | `damaged`. Older rows stay NULL and are read as "returned".
  No data is deleted or rewritten. `down()` only drops the new column.

## What changed
- routes/web.php: `POST /loans/{loan}/lost` (`loans.lost`) and `POST /loans/{loan}/damaged` (`loans.damaged`).
- app/Http/Controllers/LoanController.php:
  - `markLost()` / `markDamaged()`: remark REQUIRED; damaged also needs `restock` (1 = back on shelf, 0 = no longer usable).
  - `closeLoan()` (the same private method used by Return and Scan) now takes an outcome and a restock flag.
    Same row lock and the same "already returned" guard as before.
    * restock: available_copies + 1 (never above total), like a normal return
    * no restock (lost, or damaged and unusable): total_copies - 1, available_copies NOT increased,
      neither below 0, available never above the new total
  - Earlier remarks are kept: the new one is added after it as `old | LOST: new` (kept within 500 characters).
  - `returnFlash()`: messages for lost/damaged. No "hold aside" notice when no copy went back to the shelf.
    The overdue notice ("N days overdue. Collect any fine offline.") still shows. Reservations are never changed.
- app/Models/Loan.php: `outcome` in fillable, outcome constants, `outcomeKey()`, `outcome_label`.
- resources/views/loans/_status.blade.php: Lost / Damaged badges. This one partial feeds the Loan History report,
  the book page history, the patron profile and the Return list.
- resources/views/loans/return.blade.php: "Lost / Damaged..." section in each row (Mark as Lost, Mark as Damaged
  with "Put back on shelf" / "No longer usable"); the "Returned today" list shows the badge.
- tests/Feature/LostDamagedTest.php: 15 tests.

## Not changed
- BookController (its edit form still requires total_copies >= 1, so a title whose last copy was lost shows
  total 0 and must be given a total again if you edit that book).
- Overdue rules, borrow limit, duplicate-title block, reservations, scan return.
- No fines, amounts, payments or receipts. No generated files.
