<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_account_id')
                ->nullable()
                ->after('vendor_id')
                ->constrained('accounts')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_account_id']);
            $table->dropColumn('purchase_account_id');
        });
    }
};
