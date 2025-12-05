<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGroupController;

Route::middleware(['auth'])->group(function () {

 // Product routes - NEW for alternative units
    Route::get('/products/get-details', [ProductController::class, 'getProductDetails'])->name('products.get-details');
    Route::post('/products/convert-to-base-unit', [ProductController::class, 'convertToBaseUnit'])->name('products.convert-to-base-unit');
    Route::get('/products/with-alternative-stock', [ProductController::class, 'getProductsWithAlternativeStock'])->name('products.with-alternative-stock');
    Route::get('/products/stock-status', [ProductController::class, 'getStockStatus'])->name('products.stock-status');


    // Units Management
    Route::resource('units', UnitController::class);

    // Product Groups
    Route::resource('product-groups', ProductGroupController::class);
    Route::get('product-groups/get/dropdown', [ProductGroupController::class, 'getGroups'])
        ->name('product-groups.dropdown');

    // Products
    Route::resource('products', ProductController::class);
    Route::post('products/quick-add', [ProductController::class, 'quickAdd'])
        ->name('products.quickAdd');
     Route::get('products/{product}/movements-datatable', [ProductController::class, 'getMovementsDatatable'])
        ->name('products.movements.datatable');
});
