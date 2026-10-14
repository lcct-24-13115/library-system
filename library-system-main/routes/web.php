<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PatronController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Books Resource Routes
    Route::resource('books', BookController::class);

    // Patrons (borrowers)
    Route::resource('patrons', PatronController::class)->except(['show']);

    // Circulation: Issue Book & Return Book
    Route::get('/circulation/return', [LoanController::class, 'returnForm'])->name('loans.return.form');
    Route::post('/loans/{loan}/return', [LoanController::class, 'markReturned'])->name('loans.return');
    Route::post('/loans/{loan}/renew', [LoanController::class, 'renew'])->name('loans.renew');
    Route::resource('loans', LoanController::class)->only(['index', 'create', 'store']);

    // Reports (on-screen, printable)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/loan-history', [ReportController::class, 'loanHistory'])->name('reports.loan-history');
    Route::get('/reports/overdue', [ReportController::class, 'overdue'])->name('reports.overdue');
    Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
    Route::get('/reports/accession', [ReportController::class, 'accession'])->name('reports.accession');
});

require __DIR__.'/auth.php';
