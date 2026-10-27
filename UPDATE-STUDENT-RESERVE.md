# Student Reserve (request a book from the portal)

NO migration. Uses the existing `reservations` table. Nothing existing is changed or deleted.

## How it works
1. Student / faculty signs in and opens the Catalog (3D shelf or List).
2. Click a book, press **Reserve**, then **Send request** on the confirm page.
3. It appears as **Pending** in the librarian's Reservations list (remark: "Requested online by the patron.").
4. The student comes to the library with their ID. The librarian issues the book at **Issue Book** and marks the reservation Fulfilled (manual, as before).

## Files
- routes/web.php: `portal.reserve.confirm` (GET), `portal.reserve.store` (POST), `portal.reserve.cancel` (POST), all inside the patron-only `my-library` group.
- app/Http/Controllers/PortalController.php: `reserveConfirm`, `reserve`, `cancelReservation`.
- resources/views/portal/reserve.blade.php (new), portal/index.blade.php (Cancel button, hint, error box),
  books/_shelf.blade.php and catalog/index.blade.php (Reserve button for signed-in patrons only).
- tests/Feature/StudentReserveTest.php (new).

## Rules kept
- A request never holds a copy, never changes available_copies, never blocks issuing / returning / renewing.
- Overdue never blocks a request. No fines, amounts or payments. No generated files.
- Duplicate pending request or a book already on loan to the same student is refused.
- A student can cancel only their OWN pending request. Staff and guests cannot use these routes.

## ID number as password
- Patron profile > Portal Login: new green button "Create login (password = ID number)" / "Reset password to ID number".
- Username and password are both the patron's ID number. The old "set your own password" form is still there.
- Files: PatronAccountController.php, patrons/show.blade.php, StudentReserveTest.php (new test).

## Automatic login (no setup per student)
- First sign-in with ID number as username AND password creates the portal login on the spot (LoginRequest::provisionPortalAccountIfNeeded -> Patron::provisionAccount).
- Only when: no account yet, card active, not an Outside User, password equals the ID number. A wrong password never creates anything.
- An existing account (e.g. custom password set by the librarian) is never touched.
- The Patron profile now says "Automatic"; the manual buttons moved into "Advanced" for resets / custom passwords.
- No migration. Files: Patron.php, LoginRequest.php, PatronAccountController.php, patrons/show.blade.php, StudentReserveTest.php.
