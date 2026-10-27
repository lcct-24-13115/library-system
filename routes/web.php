<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookImportController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\PatronAccountController;
use App\Http\Controllers\PatronController;
use App\Http\Controllers\PatronImportController;
use App\Http\Controllers\PatronProfileController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Splash screen (the book opens, then the sign-in page). Signed-in users go straight to their home.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->homeRoute());
    }

    return view('splash');
})->name('splash');

// Public catalog (OPAC): search books without an account. Read only, no patron data.
Route::get('/catalog', [CatalogController::class, 'index'])->middleware('throttle:60,1')->name('catalog.index');

// Student / Faculty portal: their own loans, due dates and reservations (read only).
Route::middleware(['auth', 'role:patron'])->prefix('my-library')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'index'])->name('index');
    Route::get('/reserve/{book}', [PortalController::class, 'reserveConfirm'])->whereNumber('book')->name('reserve.confirm');
    Route::post('/reserve/{book}', [PortalController::class, 'reserve'])->whereNumber('book')->middleware('throttle:20,1')->name('reserve.store');
    Route::post('/reservations/{reservation}/cancel', [PortalController::class, 'cancelReservation'])->whereNumber('reservation')->name('reserve.cancel');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified', 'role:admin,librarian,staff'])->name('dashboard');

// Student check-in (PUBLIC, opened from the login page; no account needed).
// Only these four routes are public. Statistics, Visitor history and removing entries stay staff-only.
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->middleware('throttle:60,1')->name('index');
    Route::post('/', [AttendanceController::class, 'store'])->middleware('throttle:40,1')->name('store');
    Route::get('/lookup', [AttendanceController::class, 'lookup'])->middleware('throttle:120,1')->name('lookup');
    Route::get('/summary', [AttendanceController::class, 'summaryJson'])->middleware('throttle:120,1')->name('summary');
});

// Roles: admin = everything + Users; librarian = books, patrons, loans, reservations,
// attendance, reports; staff = view books/patrons, circulation, reservations, scan, reports.
Route::middleware(['auth', 'role:admin,librarian,staff'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin only: user management (never hard-deletes; accounts are deactivated)
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
        Route::post('/{user}/activate', [UserController::class, 'activate'])->name('activate');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    });

    // Admin only: Settings (excluded days, report heading, holidays / suspensions, loan policy)
    Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
        Route::post('/holidays', [SettingsController::class, 'holidayStore'])->name('holidays.store');
        Route::post('/holidays/empty', [SettingsController::class, 'holidayEmpty'])->name('holidays.empty');
        Route::delete('/holidays/{holiday}', [SettingsController::class, 'holidayDestroy'])->name('holidays.destroy');
        Route::get('/policy', [SettingsController::class, 'policy'])->name('policy');
        Route::put('/policy', [SettingsController::class, 'policyUpdate'])->name('policy.update');
    });

    // Admin + librarian only: create / edit / delete books and patrons, remove visit
    // entries, cancel reservations. Declared before the read routes so /books/create wins.
    Route::middleware('role:admin,librarian')->group(function () {
        // Bulk add books from an Excel / CSV sheet (declared before the books resource so /books/import wins).
        Route::get('/books/import', [BookImportController::class, 'create'])->name('books.import');
        Route::post('/books/import', [BookImportController::class, 'store'])->middleware('throttle:10,1')->name('books.import.store');
        Route::resource('books', BookController::class)->except(['index', 'show']);
        Route::get('/patrons/import', [PatronImportController::class, 'create'])->name('patrons.import');
        Route::post('/patrons/import', [PatronImportController::class, 'store'])->middleware('throttle:10,1')->name('patrons.import.store');
        Route::resource('patrons', PatronController::class)->except(['index', 'show']);
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::post('/loans/{loan}/void', [LoanController::class, 'void'])->name('loans.void');
        // Student / Faculty portal login for a patron
        Route::post('/patrons/{patron}/account', [PatronAccountController::class, 'store'])->whereNumber('patron')->name('patrons.account.store');
        Route::post('/patrons/{patron}/account/toggle', [PatronAccountController::class, 'toggle'])->whereNumber('patron')->name('patrons.account.toggle');
    });

    // Everyone: view books and patrons
    Route::resource('books', BookController::class)->only(['index', 'show']);
    Route::resource('patrons', PatronController::class)->only(['index']);

    // Circulation: Issue Book & Return Book
    Route::get('/circulation/return', [LoanController::class, 'returnForm'])->name('loans.return.form');
    Route::post('/circulation/return/scan', [LoanController::class, 'scanReturn'])->name('loans.return.scan');
    Route::post('/loans/{loan}/return', [LoanController::class, 'markReturned'])->name('loans.return');
    Route::post('/loans/{loan}/lost', [LoanController::class, 'markLost'])->name('loans.lost');
    Route::post('/loans/{loan}/damaged', [LoanController::class, 'markDamaged'])->name('loans.damaged');
    Route::post('/loans/{loan}/renew', [LoanController::class, 'renew'])->name('loans.renew');
    // Phase 5: end of a multi-book issue session, and the overdue follow-up list (display only)
    Route::post('/circulation/issue/done', [LoanController::class, 'issueDone'])->name('loans.issue.done');
    Route::get('/circulation/overdue', [LoanController::class, 'overdue'])->name('loans.overdue');
    Route::resource('loans', LoanController::class)->only(['index', 'create', 'store']);

    // v3: Patron profile (defined after the patrons resource so /patrons/create still wins)
    Route::get('/patrons/{patron}', [PatronProfileController::class, 'show'])->whereNumber('patron')->name('patrons.show');

    // v3: Live preview lookups (JSON) used by the Issue Book / Reserve forms
    Route::get('/lookup/patron', [LookupController::class, 'patron'])->name('lookup.patron');
    Route::get('/lookup/book', [LookupController::class, 'book'])->name('lookup.book');

    // v3: Attendance reports (staff only; the student check-in screen is declared above)
    Route::get('/attendance/stats', [AttendanceController::class, 'stats'])->name('attendance.stats');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');

    // v3: Reservations
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{reservation}/fulfill', [ReservationController::class, 'fulfill'])->name('reservations.fulfill');

    // Reports (on-screen, printable)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/loan-history', [ReportController::class, 'loanHistory'])->name('reports.loan-history');
    Route::get('/reports/overdue', [ReportController::class, 'overdue'])->name('reports.overdue');
    Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
    Route::get('/reports/accession', [ReportController::class, 'accession'])->name('reports.accession');
});

require __DIR__.'/auth.php';

// Library Assistant chatbot
Route::post('/chatbot/message', [\App\Http\Controllers\ChatbotController::class, 'message'])->middleware('throttle:30,1')->name('chatbot.message');
