<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::table('products', function (Blueprint $table) {
            // Add current_stock column if it doesn't exist
            if (!Schema::hasColumn('products', 'current_stock')) {
                $table->decimal('current_stock', 15, 4)->default(0)->after('base_unit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'current_stock',
            ]);
        });
    }
};
