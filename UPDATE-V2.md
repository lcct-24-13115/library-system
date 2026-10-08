# Update v2 - RDA fields, Dashboard, Renewals, Reports

Extract over your project root (replace files when asked), then:

    php artisan migrate
    php artisan route:clear   (optional)

Two NEW migrations (your existing patrons/loans/books tables are only ALTERED, no data lost):
- 2026_10_08_000003_add_rda_fields_to_books_table.php
- 2026_10_08_000004_add_renewal_columns_to_loans_table.php

## 1. RDA fields (Book Catalog)
Title proper, subtitle, statement of responsibility, edition, place/publisher/date of publication,
series, content/media/carrier type, extent, dimensions, language, call number, subjects, genre,
summary, notes, library, location, date acquired, dealer/donor, price.
Dropdown lists come from config/rda.php (taken from the legacy media.txt, carrier.txt, content.txt,
Language.txt, location.txt, library.txt, genre.txt, Notes.txt). All new columns are nullable.
New page: books/{id} shows the full RDA record + borrowing history.

## 2. Dashboard
/dashboard now shows live stats, a 7-day issued/returned chart, longest overdue, recent
transactions and most borrowed titles.

## 3. Renewals
Renew button on Loans and Return Book screens. New due date = later of (current due date, today) + days.
Unlimited by default; set LIBRARY_MAX_RENEWALS=3 in .env for a cap. Overdue/fines never block renewal.

## 4. Reports (print via the browser, nothing saved as files)
Loan History, Overdue & On-loan list, Circulation Summary, Accession/Shelf List.
Use the Print button (Ctrl+P) -> Save as PDF if a copy is needed.

## Changed existing files
routes/web.php, app/Models/{Book,Loan}.php, app/Http/Controllers/{BookController,LoanController}.php,
config/library.php, resources/views/{dashboard,layouts/library}.blade.php,
resources/views/books/{index,create,edit}.blade.php, resources/views/loans/{index,return}.blade.php
