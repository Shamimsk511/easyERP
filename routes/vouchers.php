<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContraVoucherController;
use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\PaymentVoucherController;
use App\Http\Controllers\ReceiptVoucherController;

Route::middleware(['auth'])->prefix('vouchers')->name('vouchers.')->group(function () {
    // Payment Vouchers
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/', [PaymentVoucherController::class, 'index'])->name('index');
        Route::get('/create', [PaymentVoucherController::class, 'create'])->name('create');
        Route::post('/', [PaymentVoucherController::class, 'store'])->name('store');
        Route::get('/{paymentVoucher}', [PaymentVoucherController::class, 'show'])->name('show');
        Route::get('/{paymentVoucher}/print', [PaymentVoucherController::class, 'print'])->name('print'); 
        Route::get('/{paymentVoucher}/edit', [PaymentVoucherController::class, 'edit'])->name('edit');
        Route::put('/{paymentVoucher}', [PaymentVoucherController::class, 'update'])->name('update');
        Route::delete('/{paymentVoucher}', [PaymentVoucherController::class, 'destroy'])->name('destroy');
        Route::post('/{paymentVoucher}/cancel', [PaymentVoucherController::class, 'cancel'])->name('cancel');

        // AJAX helper routes
        Route::get('/ajax/vendor-account', [PaymentVoucherController::class, 'getVendorAccount'])
            ->name('vendor-account');
    });

Route::prefix('receipt')->name('receipt.')->group(function () {
        Route::get('/', [ReceiptVoucherController::class, 'index'])->name('index');
        Route::get('/create', [ReceiptVoucherController::class, 'create'])->name('create');
        Route::post('/', [ReceiptVoucherController::class, 'store'])->name('store');
        Route::get('/{receiptVoucher}', [ReceiptVoucherController::class, 'show'])->name('show');
        Route::get('/{receiptVoucher}/edit', [ReceiptVoucherController::class, 'edit'])->name('edit');
        Route::put('/{receiptVoucher}', [ReceiptVoucherController::class, 'update'])->name('update');
        Route::delete('/{receiptVoucher}', [ReceiptVoucherController::class, 'destroy'])->name('destroy');
        Route::post('/{receiptVoucher}/cancel', [ReceiptVoucherController::class, 'cancel'])->name('cancel');
        
        // AJAX routes - Must be before customer details route
        Route::get('/ajax/search-customers', [ReceiptVoucherController::class, 'searchCustomers'])->name('ajax.search-customers');
        Route::get('/ajax/customer/{customer}', [ReceiptVoucherController::class, 'getCustomerDetails'])->name('ajax.customer-details');
    });

    // Contra Vouchers
    Route::prefix('contra')->name('contra.')->group(function () {
        Route::get('/', [ContraVoucherController::class, 'index'])->name('index');
        Route::get('/create', [ContraVoucherController::class, 'create'])->name('create');
        Route::post('/', [ContraVoucherController::class, 'store'])->name('store');
        Route::get('/{contraVoucher}', [ContraVoucherController::class, 'show'])->name('show');
        Route::get('/{contraVoucher}/edit', [ContraVoucherController::class, 'edit'])->name('edit');
        Route::put('/{contraVoucher}', [ContraVoucherController::class, 'update'])->name('update');
        Route::delete('/{contraVoucher}', [ContraVoucherController::class, 'destroy'])->name('destroy');
        Route::post('/{contraVoucher}/cancel', [ContraVoucherController::class, 'cancel'])->name('cancel');

        // AJAX helper routes
        Route::post('/ajax/quick-create-account', [ContraVoucherController::class, 'quickCreateAccount'])
            ->name('ajax.quick-create-account');
        Route::get('/ajax/search-accounts', [ContraVoucherController::class, 'searchAccounts'])
            ->name('ajax.search-accounts');
    });

    // Journal Vouchers
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/', [JournalVoucherController::class, 'index'])->name('index');
        Route::get('/create', [JournalVoucherController::class, 'create'])->name('create');
        Route::post('/', [JournalVoucherController::class, 'store'])->name('store');
        Route::get('/{journalVoucher}', [JournalVoucherController::class, 'show'])->name('show');
        Route::get('/{journalVoucher}/edit', [JournalVoucherController::class, 'edit'])->name('edit');
        Route::put('/{journalVoucher}', [JournalVoucherController::class, 'update'])->name('update');
        Route::delete('/{journalVoucher}', [JournalVoucherController::class, 'destroy'])->name('destroy');
        Route::post('/{journalVoucher}/cancel', [JournalVoucherController::class, 'cancel'])->name('cancel');
        Route::get('/ajax/search-accounts', [JournalVoucherController::class, 'searchAccounts'])->name('ajax.search-accounts');
    });
});
