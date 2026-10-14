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

];
