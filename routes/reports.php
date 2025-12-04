<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::middleware(['auth'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
    Route::get('trial-balance/pdf', [ReportController::class, 'trialBalancePdf'])->name('trial-balance.pdf');
    Route::get('trial-balance/excel', [ReportController::class, 'trialBalanceExcel'])->name('trial-balance.excel');
    //purchase register report
    Route::get('/purchase-register', [ReportController::class, 'purchaseRegister'])->name('purchase-register');
    Route::get('/purchase-register/items/{purchaseOrder}', [ReportController::class, 'getPurchaseOrderItems'])->name('purchase-register.items');
    Route::get('/purchase-register/summary', [ReportController::class, 'purchaseRegisterSummary'])->name('purchase-register.summary');
    Route::get('/purchase-register/export', [ReportController::class, 'exportPurchaseRegister'])->name('purchase-register.export');


    Route::get('/vendor-wise-purchase', [ReportController::class, 'vendorWisePurchase'])->name('vendor-wise-purchase');
    Route::get('/vendor-wise-purchase/orders/{vendor}', [ReportController::class, 'getVendorPurchaseOrders'])->name('vendor-wise-purchase.orders');
    Route::get('/vendor-wise-purchase/summary', [ReportController::class, 'vendorWisePurchaseSummary'])->name('vendor-wise-purchase.summary');

    // Payables Report Routes
    Route::get('payables', [ReportController::class, 'payables'])->name('payables');
    Route::get('payables/data', [ReportController::class, 'getPayablesData'])->name('payables.data');
    Route::get('payables/{account}/transactions', [ReportController::class, 'getPayableTransactions'])->name('payables.transactions');
    Route::get('payables/export/csv', [ReportController::class, 'payablesCsv'])->name('payables.csv');
    Route::get('payables/print', [ReportController::class, 'payablesPrint'])->name('payables.print');
});
