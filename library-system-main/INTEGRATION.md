# Circulation Module - Integration Guide

Copy the folders in this package over your project root (same paths as your repo), then:

    php artisan migrate
    php artisan db:seed --class=PatronSeeder     # optional sample borrowers
    php artisan route:list --name=loans          # sanity check

## NEW files
- database/migrations/2026_10_08_000001_create_patrons_table.php
- database/migrations/2026_10_08_000002_create_loans_table.php
- app/Models/Patron.php, app/Models/Loan.php
- app/Http/Controllers/LoanController.php, PatronController.php
- config/library.php            (default loan days, env LIBRARY_LOAN_DAYS)
- database/seeders/PatronSeeder.php (optional)
- resources/views/layouts/library.blade.php
- resources/views/loans/*  (create, index, return, _status)
- resources/views/patrons/* (index, create, edit, _form)

## MODIFIED existing files (small, additive edits only)
- routes/web.php                      -> added patron + circulation routes (books route untouched)
- app/Models/Book.php                 -> added loans() relationship only
- app/Http/Controllers/BookController.php -> destroy() refuses to delete a book that has loan records
- resources/views/books/{index,create,edit}.blade.php -> added nav links (index also shows an 'error' flash)

If you prefer not to overwrite those 5 files, apply the edits by hand (each is a few lines).

## Rules implemented
- No fine/penalty/payment columns, calculations, receipts or generated files.
- Overdue is display-only (badge + "days overdue"); it never blocks borrowing.
- Only hard limits: patron must exist and be Active (manual flag), and the book needs an available copy.
- Issue/return run in DB transactions with row locks and keep books.available_copies in sync.
- `remarks` is a free-text field for the librarian's manual offline fine/payment notes.

## Recommended
Set 'timezone' => 'Asia/Manila' in config/app.php so due dates roll over at local midnight (it is UTC now).
