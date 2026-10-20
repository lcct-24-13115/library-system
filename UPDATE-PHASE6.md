# Phase 6 - Users: Library, Settings (excluded days, holidays, report heading), Group / Policy

THREE new additive migrations. No existing row is changed or deleted.

## Database
- `2026_10_23_000001_add_library_to_users_table.php`: nullable `users.library`. NULL = "All libraries" (every existing account).
- `2026_10_23_000002_create_settings_table.php`: key/value `settings`.
- `2026_10_23_000003_create_holidays_table.php`: `holidays` (unique `date`, optional `reason`).

## Users (admin only)
- Add / Edit User has a **Library** select (All libraries, Grade School, Junior High School, Senior High School, College). Stored and shown only; it does not restrict access yet.
- Users list shows **Access Right** (the existing role), **Library**, and **Current User** (Online = session active in the last 5 minutes; needs `SESSION_DRIVER=database`).
- Accounts are still deactivated, never deleted.

## Settings (admin only, sidebar > Admin > Settings)
- Weekdays to exclude from the "days overdue" count, and Holiday / Suspension dates (add, delete, empty list).
  Display only: `Loan::daysOverdue()` / `daysLateAtReturn()` skip those days. With nothing set the count is unchanged. Still no fines or amounts.
- Report heading: shown in the print line of every report (`reports/_header`).
- Group / Policy page: default loan days and borrow limit per patron type. Falls back to `config/library.php` / `.env` when nothing is saved.
- New: `App\Support\LibrarySettings` (singleton), `SettingsController`, `Setting`, `Holiday`.

## Not done
- "Picture path" from the old system: not added (the system stores no files, see BUSINESS_RULES.md).
- Library does not yet limit which books / loans a user can see.

## Tests
- New: `AdminSettingsTest`.
