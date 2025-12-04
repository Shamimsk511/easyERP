<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGroupController;

Route::middleware(['auth'])->group(function () {
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
