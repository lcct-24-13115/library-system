# Phase 7 - Login tabs, Student / Faculty portal, public catalog (OPAC)

ONE new additive migration: `2026_10_24_000001_add_patron_id_to_users_table.php` (nullable, unique `users.patron_id`). Staff accounts are untouched.

## Login page
- Tabs: Student, Faculty, Librarian, Administrator, Staff. Optional: if a tab is chosen the account must match it, otherwise the login is refused. No tab = works as before.
- The field accepts an email, or a patron ID number for Student / Faculty accounts.
- Link to the public catalog.

## Student / Faculty portal (`/my-library`, role `patron`)
- Read only: own books on loan, due dates, overdue (days only), pending reservations, recently returned. No fines or amounts.
- Created by admin / librarian on the patron's page ("Portal Login"): create, reset password, deactivate / reactivate. Not for "Outside User" or inactive patrons.
- A patron account cannot open any staff page (403). Portal accounts are hidden from the Users page.
- Deleting a patron also deletes their portal login (patrons with records still cannot be deleted).

## Public catalog (`/catalog`, no login)
- Search title, subtitle, author, ISBN, call number, subjects, category, publisher; filter by library; shows Available x of y.
- Shows no patron, loan or accession data.

## Tests
- New: `PortalAndCatalogTest`.
