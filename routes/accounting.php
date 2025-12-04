<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;

Route::middleware(['auth'])->group(function () {
    // Account Management
    Route::resource('accounts', AccountController::class);
    Route::get('accounts/{account}/journal', [AccountController::class, 'journal'])->name('accounts.journal');
    Route::get('accounts/{account}/transactions', [AccountController::class, 'getTransactions'])
        ->name('accounts.transactions');

    // Transaction Management
    Route::resource('transactions', TransactionController::class);
    Route::post('transactions/{transaction}/void', [TransactionController::class, 'void'])
        ->name('transactions.void');
});
