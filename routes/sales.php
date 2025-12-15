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
        // AJAX endpoints
        Route::get('invoice/{invoice}/pending-items', [DeliveryController::class, 'getPendingItems'])
            ->name('pending-items');
        Route::post('invoice/{invoice}/quick-deliver', [DeliveryController::class, 'quickDeliver'])
            ->name('quick-deliver');

        // Resource routes
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
        // AJAX endpoints
        Route::get('invoice/{invoice}/payments', [PaymentController::class, 'getForInvoice'])
            ->name('for-invoice');

        // Resource routes
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('create', [PaymentController::class, 'create'])->name('create');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
        Route::delete('{payment}', [PaymentController::class, 'destroy'])->name('destroy');
        Route::get('{payment}/print', [PaymentController::class, 'print'])->name('print');
    });
});