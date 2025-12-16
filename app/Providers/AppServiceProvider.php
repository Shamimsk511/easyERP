<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Illuminate\Support\ServiceProvider;
use App\Services\UnitConversionService;
use App\Services\Sales\InvoiceService;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
public function register(): void
{
    // Register UnitConversionService as singleton
    $this->app->singleton(UnitConversionService::class, function ($app) {
        return new UnitConversionService();
    });

    // Register InvoiceService with dependency injection
    $this->app->singleton(InvoiceService::class, function ($app) {
        return new InvoiceService(
            $app->make(UnitConversionService::class)
        );
    });
}

    /**
     * Bootstrap any application services.
     */
public function boot(): void
    {
        // ✅ Register Transaction Observer
        // Transaction::observe(TransactionObserver::class);
    }
}
