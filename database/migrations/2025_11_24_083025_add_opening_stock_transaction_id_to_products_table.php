<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('opening_stock_transaction_id')
                  ->nullable()
                  ->after('inventory_account_id')
                  ->constrained('transactions')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['opening_stock_transaction_id']);
            $table->dropColumn('opening_stock_transaction_id');
        });
    }
};
