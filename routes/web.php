<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PatronController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::resource('loans', LoanController::class)->only(['index', 'create', 'store']);
});

require __DIR__.'/auth.php';
