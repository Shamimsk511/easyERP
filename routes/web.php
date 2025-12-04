<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Load module routes
require __DIR__.'/accounting.php';
require __DIR__.'/inventory.php';
require __DIR__.'/customers.php';
require __DIR__.'/vendors.php';
require __DIR__.'/vouchers.php';
require __DIR__.'/reports.php';
require __DIR__.'/settings.php';
require __DIR__.'/profile.php';
require __DIR__.'/auth.php';
