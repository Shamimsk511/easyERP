<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProductGroupController;

// Route::get('/', function () {
//     return view('dashboard');
// });

Route::middleware((['auth']))->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');
});


Route::middleware(['auth'])->group(function () {
    //Accounting Routes
    Route::resource('accounts', AccountController::class);
    Route::resource('transactions', TransactionController::class);
    Route::get('accounts/{account}/journal', [AccountController::class, 'journal'])->name('accounts.journal');
    Route::get('accounts/{account}/transactions', [AccountController::class, 'getTransactions'])
    ->name('accounts.transactions');
    // Additional transaction route
    Route::post('transactions/{transaction}/void', [TransactionController::class, 'void'])
        ->name('transactions.void');

        //units routes
    Route::resource('units', UnitController::class);

   // Product Groups
    Route::resource('product-groups', ProductGroupController::class);
    Route::get('product-groups/get/dropdown', [ProductGroupController::class, 'getGroups'])->name('product-groups.dropdown');
    
    // Products
    Route::resource('products', ProductController::class);

    
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
