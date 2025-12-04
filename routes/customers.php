<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\TransactionController;

Route::middleware(['auth'])->group(function () {
    // Customer Management
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/data', [CustomerController::class, 'getData'])->name('customers.data');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Customer Ledger & Transactions
    Route::get('/customers/{customer}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
    Route::get('/customers/{customer}/ledger/data', [CustomerController::class, 'getLedgerData'])
        ->name('customers.ledger.data');
    Route::post('/customers/{customer}/extend-due-date', [CustomerController::class, 'extendDueDate'])
        ->name('customers.extend-due-date');
    Route::get('/customers/{customer}/create-transaction', [TransactionController::class, 'createForCustomer'])
        ->name('customers.create-transaction');
    Route::get('customers/{customer}/chart-data', [CustomerController::class, 'getChartData'])
        ->name('customers.chart.data');
    Route::post('/customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
        ->name('customers.deactivate');

    // Customer Groups
    Route::get('/customer-groups', [CustomerGroupController::class, 'index'])->name('customer-groups.index');
    Route::get('/customer-groups/data', [CustomerGroupController::class, 'getData'])->name('customer-groups.data');
    Route::get('/customer-groups/create', [CustomerGroupController::class, 'create'])->name('customer-groups.create');
    Route::post('/customer-groups', [CustomerGroupController::class, 'store'])->name('customer-groups.store');
    Route::get('/customer-groups/{customerGroup}/edit', [CustomerGroupController::class, 'edit'])
        ->name('customer-groups.edit');
    Route::put('/customer-groups/{customerGroup}', [CustomerGroupController::class, 'update'])
        ->name('customer-groups.update');
    Route::delete('/customer-groups/{customerGroup}', [CustomerGroupController::class, 'destroy'])
        ->name('customer-groups.destroy');
});
