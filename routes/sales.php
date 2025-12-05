<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvoicePaymentController;

Route::middleware(['auth'])->group(function () {
    
    // ============================================
    // AJAX Endpoints (must be BEFORE model binding)
    // ============================================
    Route::get('sales/search-customers', [SalesController::class, 'searchCustomers'])
        ->name('sales.search-customers');
    Route::get('sales/search-products', [SalesController::class, 'searchProducts'])
        ->name('sales.search-products');
    Route::post('sales/quick-add-customer', [SalesController::class, 'quickAddCustomer'])
        ->name('sales.quick-add-customer');
    Route::get('sales/customer/{customerId}', [SalesController::class, 'getCustomerDetails'])->name('sales.get-customer');

     // ========= Sales / Invoices =========
    Route::prefix('sales')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('sales.index');
        Route::get('create', [SalesController::class, 'create'])->name('sales.create');
        Route::post('/', [SalesController::class, 'store'])->name('sales.store');
        Route::get('{invoice}', [SalesController::class, 'show'])->name('sales.show');
        Route::get('{invoice}/edit', [SalesController::class, 'edit'])->name('sales.edit');
        Route::put('{invoice}', [SalesController::class, 'update'])->name('sales.update');
        Route::delete('{invoice}', [SalesController::class, 'destroy'])->name('sales.destroy');
    });
    // ============================================
    // Payment Routes
    // ============================================
    // ========= Invoice Payments =========
    Route::get('payments/create', [PaymentController::class, 'create'])
        ->name('payments.create');    // AJAX: get form data
    Route::post('payments', [PaymentController::class, 'store'])
        ->name('payments.store');     // AJAX: store payment
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])
        ->name('payments.destroy');   // AJAX: delete payment
});