<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Sales Module Routes
|--------------------------------------------------------------------------
| Include this file in your main routes/web.php:
| require __DIR__.'/sales.php';
*/

Route::middleware(['auth'])->group(function () {

    // ============================================
    // Sales / Invoice Routes
    // ============================================
    Route::prefix('sales')->name('sales.')->group(function () {
        
        // AJAX endpoints (before resource routes)
        Route::get('customer/{customer}/balance', [SalesController::class, 'getCustomerBalance'])
            ->name('customer.balance');
        Route::get('product/{product}/details', [SalesController::class, 'getProductDetails'])
            ->name('product.details');
        
        // NEW: Calculate alternative quantity display
        Route::post('calculate-alt-qty', [SalesController::class, 'calculateAltQty'])
            ->name('calculate-alt-qty');
        
        // NEW: Get passive income accounts for Select2
        Route::get('passive-income-accounts', [SalesController::class, 'getPassiveIncomeAccounts'])
            ->name('passive-income-accounts');

        // Resource routes
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::get('create', [SalesController::class, 'create'])->name('create');
        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::get('{invoice}', [SalesController::class, 'show'])->name('show');
        Route::get('{invoice}/edit', [SalesController::class, 'edit'])->name('edit');
        Route::put('{invoice}', [SalesController::class, 'update'])->name('update');
        Route::delete('{invoice}', [SalesController::class, 'destroy'])->name('destroy');
        Route::get('{invoice}/print', [SalesController::class, 'print'])->name('print');
    });

    // ============================================
    // Delivery / Challan Routes
    // ============================================
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('invoice/{invoice}/pending-items', [DeliveryController::class, 'getPendingItems'])
            ->name('pending-items');
        Route::post('invoice/{invoice}/quick-deliver', [DeliveryController::class, 'quickDeliver'])
            ->name('quick-deliver');

        Route::get('/', [DeliveryController::class, 'index'])->name('index');
        Route::get('create', [DeliveryController::class, 'create'])->name('create');
        Route::post('/', [DeliveryController::class, 'store'])->name('store');
        Route::get('{delivery}', [DeliveryController::class, 'show'])->name('show');
        Route::delete('{delivery}', [DeliveryController::class, 'destroy'])->name('destroy');
        Route::get('{delivery}/print', [DeliveryController::class, 'print'])->name('print');
    });

    // ============================================
    // Payment Routes
    // ============================================
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('invoice/{invoice}', [PaymentController::class, 'create'])->name('create');
        Route::post('invoice/{invoice}', [PaymentController::class, 'store'])->name('store');
        Route::delete('{payment}', [PaymentController::class, 'destroy'])->name('destroy');
    });
});