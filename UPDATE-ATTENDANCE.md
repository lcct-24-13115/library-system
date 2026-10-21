# Attendance "Record your visit" update

Changed / new files
- database/migrations/2026_10_25_000001_add_photo_to_patrons_table.php  (NEW: patrons.photo)
- app/Models/Patron.php                       (photo fillable, photoUrl(), initials())
- app/Http/Controllers/PatronController.php   (photo upload / remove)
- app/Http/Controllers/AttendanceController.php (index = kiosk, lookup, summaryJson, stats)
- routes/web.php                              (attendance.lookup, attendance.summary, attendance.stats)
- resources/views/attendance/index.blade.php  (NEW kiosk UI)
- resources/views/attendance/stats.blade.php  (old dashboard, moved here)
- resources/views/attendance/history.blade.php (back link text)
- resources/views/patrons/_form|create|edit.blade.php (photo input, multipart)
- tests/Feature/V3ModulesTest.php             (updated + 2 new tests)

Apply
  php artisan migrate
  php artisan storage:link
  php artisan test --filter=V3ModulesTest
  php artisan serve   -> http://127.0.0.1:8000/attendance
