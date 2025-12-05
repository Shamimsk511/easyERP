<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;

Route::middleware(['auth'])->group(function () {
    
    // ============================================
    // AJAX Endpoints (must be BEFORE model binding)
    // ============================================
    // Vendor balance endpoint
    Route::get('vendors/{vendor}/balance', [VendorController::class, 'getBalance'])
        ->name('vendors.balance');

    // ============================================
    // Vendor Management
    // ============================================
    Route::resource('vendors', VendorController::class);

    // ============================================
    // Purchase Order Routes (Specific before Generic)
    // ============================================
    Route::prefix('purchase-orders')->group(function () {
        // List and Create
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        
        // Specific action routes BEFORE model binding
        Route::post('{purchaseOrder}/mark-received', [PurchaseOrderController::class, 'markAsReceived'])
            ->name('purchase-orders.mark-received');
        
        // Model binding routes (these use {purchaseOrder} wildcard)
        Route::get('{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::get('{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::delete('{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
    });

    // ============================================
    // Product Management (Alternative Units Support)
    // ============================================
    // AJAX Endpoints FIRST (specific, non-model-binding)
    Route::post('products/convert-to-base-unit', [ProductController::class, 'convertToBaseUnit'])
        ->name('products.convert-to-base-unit');
    Route::get('products/with-alternative-stock', [ProductController::class, 'getProductsWithAlternativeStock'])
        ->name('products.with-alternative-stock');
    
    // Model binding routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        
        // Specific action routes BEFORE model binding
        Route::get('{product}/stock-status', [ProductController::class, 'getStockStatus'])
            ->name('products.stock-status');
        Route::get('{product}/details', [ProductController::class, 'getProductDetails'])
            ->name('products.get-details');
        
        // Model binding routes
        Route::get('{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
});
