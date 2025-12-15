<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_price_history', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_price_history', 'quantity_sold')) {
                $table->decimal('quantity_sold', 15, 3)->default(0)->after('last_sold_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_price_history', function (Blueprint $table) {
            if (Schema::hasColumn('customer_price_history', 'quantity_sold')) {
                $table->dropColumn('quantity_sold');
            }
        });
    }
};