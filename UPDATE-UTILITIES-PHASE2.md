# Utilities - Phase 2: Administration and Users List tabs

Additive only. No migration. The existing /users pages keep working.

## Administration tab (`/utilities?tab=administration`)
- New user form: User Name, Email, Password + confirm, Access Right (Administrator / Librarian / Staff), User Type, Library (blank = All libraries).
- Posts to the existing `users.store` / `users.update` (same validation). A hidden `from=utilities` makes `UserController` redirect back to the Users List tab; any other value keeps the old redirect to `users.index`.
- Edit mode: `?tab=administration&edit={id}` (from the Edit button in Users List). Unknown ids and portal (patron) accounts are ignored and show the new-user form.
- Your own role is locked (shown, not changeable), same rule as before.
- Buttons: New (clear form), Save / Update, Close (dashboard).

## Users List tab (`/utilities?tab=users`)
- Columns: User name (+ email), Current User (active in the last 5 minutes), Access right, Library, Status, Actions.
- Filter by User Type, Refresh, Close, pagination.
- "Delete" of the old system = Deactivate / Reactivate (accounts are never deleted). You cannot deactivate yourself. Reset password is also here.

## Files
- Changed: `app/Http/Controllers/UtilitiesController.php`, `UserController.php` (two redirect lines), `utilities/_administration.blade.php`, `utilities/_users.blade.php`.
- New: `tests/Feature/UtilitiesAdminUsersTest.php`.

## Not changed
- Roles, validation rules, Settings, Policy, holidays, login by email.
