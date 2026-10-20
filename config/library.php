<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Loan Period (days)
    |--------------------------------------------------------------------------
    |
    | Pre-filled in the "Issue Book" form (the legacy system defaulted to 7
    | "days out"). The librarian can change it per transaction.
    | Override in .env with LIBRARY_LOAN_DAYS=14
    |
    */
    'loan_days' => (int) env('LIBRARY_LOAN_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Maximum Renewals per Loan
    |--------------------------------------------------------------------------
    |
    | null = unlimited (default, so renewals are never blocked automatically).
    | Set LIBRARY_MAX_RENEWALS=3 in .env if the library wants a cap.
    |
    */
    'max_renewals' => env('LIBRARY_MAX_RENEWALS') !== null ? (int) env('LIBRARY_MAX_RENEWALS') : null,

    /*
    |--------------------------------------------------------------------------
    | Maximum Active Loans per Patron
    |--------------------------------------------------------------------------
    |
    | How many books a patron may have on loan (not yet returned) at one time.
    | Overdue items still never block borrowing; this limit only protects the
    | shelves. Keyed by patron type; 'default' is used for any other type.
    | Override in .env, e.g. LIBRARY_MAX_LOANS_STUDENT=3
    | An admin/librarian can override the limit on a single issue (remark required).
    |
    */
    'max_loans' => [
        'Student'      => (int) env('LIBRARY_MAX_LOANS_STUDENT', 3),
        'Faculty'      => (int) env('LIBRARY_MAX_LOANS_FACULTY', 5),
        'Staff'        => (int) env('LIBRARY_MAX_LOANS_STAFF', 3),
        'Outside User' => (int) env('LIBRARY_MAX_LOANS_OUTSIDE', 3),
        'default'      => (int) env('LIBRARY_MAX_LOANS_DEFAULT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance: duplicate-scan window (minutes)
    |--------------------------------------------------------------------------
    |
    | Barcode scanners sometimes read a card twice. A second scan of the same
    | patron within this many minutes is ignored (reported as "already logged").
    | Set LIBRARY_ATTENDANCE_DUPLICATE_MINUTES=0 to log every single scan.
    |
    */
    'attendance_duplicate_minutes' => (int) env('LIBRARY_ATTENDANCE_DUPLICATE_MINUTES', 5),

];
