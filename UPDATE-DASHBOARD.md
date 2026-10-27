# Dashboard: visitors today + test data

No migration, no package.

## What changed
- Dashboard now has a "Library visitors today" panel: time, name, ID number and type of everyone who checked in
  (latest 10), with check-ins today, different visitors, this week, and links to Statistics and the Visitor log.
- DemoDataSeeder: 17,000 sample book titles (TEST-00001 to TEST-17000; the first 15 are real titles, the rest are made-up
  titles and authors across 13 categories) and 12 sample check-ins today (plus a few for the past 6 days).
  Change the total with TOTAL_BOOKS at the top of the seeder.

- Live date and time at the top right of the dashboard (ticks every second, uses the app timezone Asia/Manila,
  starts from the server clock so a wrong computer clock does not matter).

- Better catalog search (staff Book Catalog and the public catalog): searching "the" lists the books with "the"
  best match first (titles starting with "The", then titles with the word, then longer words like "Mathematics").
  Several words work in any order ("prince little"). The catalog shows how many books were found.
- More "The ..." titles in the test data (The Story of ..., The Art of ...). Books already in the database keep their
  old titles; to get the new ones, remove the test books and seed again:
      php artisan tinker
      >>> App\Models\Book::where('accession_number', 'like', 'TEST-%')->doesntHave('loans')->delete();
      >>> exit
      php artisan db:seed --class=DemoDataSeeder

## Run the test data
    php artisan db:seed --class=DemoDataSeeder

Safe to run again (no duplicates). If the patrons table is empty it runs PatronSeeder first.

## Remove the test data later
    php artisan tinker
    >>> App\Models\Attendance::where('remarks', 'Test data')->delete();
    >>> App\Models\Book::where('accession_number', 'like', 'TEST-%')->doesntHave('loans')->delete();

## Files
- app/Http/Controllers/DashboardController.php
- resources/views/dashboard.blade.php
- database/seeders/DemoDataSeeder.php (new)
- tests/Feature/DashboardVisitorsTest.php (new)
- app/Models/Book.php, app/Http/Controllers/BookController.php, app/Http/Controllers/CatalogController.php,
  resources/views/books/index.blade.php (search)
- tests/Feature/BookSearchTest.php (new)
