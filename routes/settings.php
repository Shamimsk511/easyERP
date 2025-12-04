<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanySettingController;

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    
    // Company Settings
    Route::prefix('company')->name('company.')->group(function () {
        Route::get('/', [CompanySettingController::class, 'index'])->name('index');
        Route::post('/update', [CompanySettingController::class, 'update'])->name('update');
        Route::delete('/logo', [CompanySettingController::class, 'deleteLogo'])->name('delete-logo');
        Route::delete('/favicon', [CompanySettingController::class, 'deleteFavicon'])->name('delete-favicon');
    });
});
