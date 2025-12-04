<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PurchaseOrderController;

Route::middleware(['auth'])->group(function () {
    // Vendor Management
    Route::resource('vendors', VendorController::class);
    Route::get('vendors/{vendor}/balance', [VendorController::class, 'getBalance'])
        ->name('vendors.balance');

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{order}/receive', [PurchaseOrderController::class, 'markAsReceived'])
        ->name('purchase-orders.receive');
    Route::post('purchase-orders/{order}/mark-received', [PurchaseOrderController::class, 'markAsReceived'])
        ->name('purchase-orders.mark-received');
});
