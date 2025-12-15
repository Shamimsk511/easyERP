<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Add unit_id for alternative unit support
            if (!Schema::hasColumn('purchase_order_items', 'unit_id')) {
                $table->foreignId('unit_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('units')
                    ->onDelete('restrict');
            }
            
            // Add description for non-product items
            if (!Schema::hasColumn('purchase_order_items', 'description')) {
                $table->string('description')->nullable()->after('unit_id');
            }
            
            // Add received quantity for partial receiving
            if (!Schema::hasColumn('purchase_order_items', 'received_quantity')) {
                $table->decimal('received_quantity', 18, 2)->default(0)->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
            
            if (Schema::hasColumn('purchase_order_items', 'description')) {
                $table->dropColumn('description');
            }
            
            if (Schema::hasColumn('purchase_order_items', 'received_quantity')) {
                $table->dropColumn('received_quantity');
            }
        });
    }
};