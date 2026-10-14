# Update v3 - Attendance, Reservations, Patron Profile, Live Preview

Extract this zip **over your project root** (same paths as the repo; overwrite when asked), then:

    php artisan migrate
    php artisan route:clear        # optional
    php artisan view:clear         # optional
    php artisan test --filter=V3ModulesTest   # optional sanity check

Two NEW migrations only CREATE new tables (`attendances`, `reservations`). Your existing
books / patrons / loans data is not touched. Rollback: `php artisan migrate:rollback --step=2`.

> **Recommended before using Attendance:** set `'timezone' => 'Asia/Manila'` in `config/app.php`
> (it is `UTC` now). Hourly charts group visits by the app timezone, so UTC would shift every
> bar by 8 hours.

## 1. Attendance  (`/attendance`, `/attendance/history`)
- Scan station: cursor is always in the ID box; a scanner's trailing Enter logs the visit (AJAX, no page reload).
- Result banner after each scan (welcome / duplicate / unknown ID / inactive notice).
- Hourly "visitors per hour" chart for any date, unique visitors, peak hour, by patron type, 14-day totals.
- Visitor history log: search by name/ID, date range, pagination, Remove for mistaken entries, browser Print.
- Double reads of the same card within 5 minutes are ignored. Change with `LIBRARY_ATTENDANCE_DUPLICATE_MINUTES`
  in `.env` (0 = log every scan).
- Inactive patrons are still logged (with an on-screen notice); unknown IDs are rejected.

## 2. Reservations  (`/reservations`, `/reservations/create`)
- Reserve a book that is on loan **or** on the shelf, for an active patron (patron ID + accession no., scanner friendly).
- Statuses: Pending -> Fulfilled / Cancelled, changed manually with buttons. "Issue" button jumps to
  Issue Book with both fields pre-filled.
- Queue position (#1, #2 ...) per book, ordered by reservation time.
- Blocks only duplicates: same patron + same book already pending, or patron already has that book on loan.
- A reservation does **not** hold a physical copy and never changes `available_copies`.
- "Reserve" button added to the book page; pending count shown there too.
- Reservations are created by library staff on behalf of patrons (patrons have no login in this system).

## 3. Enhanced Patron Profile  (`/patrons/{id}`)
Contact details, summary cards, active loans, borrowing history (latest returned), attendance history
(latest visits), active + recently closed reservations, quick actions (Issue / Reserve / Edit / Print).
Reached from the patron name or the new "Profile" button on the Patrons list.

## 4. Live preview on Issue Book  (`/loans/create`)
As the librarian types or scans, Patron and Book cards appear (debounced AJAX):
patron name/type/status/contact/loans/overdue count; book title/author/call no./location/availability,
reservation queue and whether this patron is in it. Enter in the Patron field jumps to the Accession field.
The same preview is reused on the Reserve form. The preview is informational: the server still validates on submit.

## Rules respected
- No fine / penalty / payment columns, calculations or blocks. Overdue stays display-only (the preview says so).
- No generated files: everything is on-screen; use the browser Print button if paper is needed.
- Additive: 2 new tables, new files, and only small edits to existing files (below).

## NEW files
- database/migrations/2026_10_09_000001_create_attendances_table.php
- database/migrations/2026_10_09_000002_create_reservations_table.php
- app/Models/Attendance.php, app/Models/Reservation.php
- app/Http/Controllers/AttendanceController.php, ReservationController.php, PatronProfileController.php, LookupController.php
- resources/views/attendance/{index,history}.blade.php
- resources/views/reservations/{index,create,_status}.blade.php
- resources/views/patrons/show.blade.php
- resources/views/partials/lookup-preview.blade.php
- tests/Feature/V3ModulesTest.php

## MODIFIED existing files (small, additive edits - full files included)
- routes/web.php                              -> new use-lines + v3 routes (existing routes untouched)
- config/library.php                          -> + attendance_duplicate_minutes
- app/Models/Patron.php                       -> + attendances(), reservations()
- app/Models/Book.php                         -> + reservations()
- app/Http/Controllers/PatronController.php   -> destroy() also refuses patrons with attendance/reservation records
- app/Http/Controllers/BookController.php     -> destroy() also refuses books with reservation records
- resources/views/layouts/library.blade.php   -> + "Reservations" and "Attendance" nav links
- resources/views/loans/create.blade.php      -> live preview include + prefill from ?patron_id_number= / ?accession_number=
- resources/views/patrons/index.blade.php     -> name links to profile + "Profile" button
- resources/views/books/show.blade.php        -> "Reserve" button + pending-reservation count

If you have changed any of these 10 files since the v2 zip, don't overwrite them; apply the few
lines by hand (each edit is 1-6 lines, search for "v3" or the names above).
