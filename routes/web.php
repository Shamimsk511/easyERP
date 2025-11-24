<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;

// Route::get('/', function () {
//     return view('dashboard');
// });

Route::middleware((['auth']))->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('accounts', AccountController::class);
    Route::resource('transactions', TransactionController::class);
    Route::get('accounts/{account}/journal', [AccountController::class, 'journal'])->name('accounts.journal');

    Route::get('accounts/{account}/transactions', [AccountController::class, 'getTransactions'])
    ->name('accounts.transactions');
    // Additional transaction route
    Route::post('transactions/{transaction}/void', [TransactionController::class, 'void'])
        ->name('transactions.void');
});
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
