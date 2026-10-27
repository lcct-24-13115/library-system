# Import books from Excel / CSV

No migration, no package. Works like the patron import.

## What it does
- Book Catalog now has an "Import from Excel" button (admin and librarian only) next to "+ Add New Book".
- Upload an .xlsx or .csv sheet: one book per row. Only accession_number and title are required.
- Other columns: author, isbn, category, total_copies, available_copies, subtitle, publisher, year_of_publication,
  place_of_publication, edition_statement, call_number, subjects, genre, language, summary, location, library,
  price, date_acquired, dealer_donor, notes. Common headings like "Accession No", "Copies", "Year" are understood.
- Blank author = "Unknown". Blank copies = 1. Blank available copies = all copies on the shelf.
- An accession number that already exists is skipped (never changed). Bad rows are skipped and listed with the reason.
- Up to 20,000 rows and 10 MB per file. The upload is read and discarded; nothing is saved or generated.
- Sample sheet: public/templates/books-import-template.csv

## Files
- app/Http/Controllers/BookImportController.php (new)
- resources/views/books/import.blade.php (new)
- resources/views/books/index.blade.php (Import button; also includes the earlier search count and no copies column)
- routes/web.php (two new routes; also keeps the splash route)
- public/templates/books-import-template.csv (new)
- tests/Feature/BookImportTest.php (new)
