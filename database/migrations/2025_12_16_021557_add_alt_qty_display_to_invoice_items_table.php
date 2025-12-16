<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // Alternative quantity display fields
            $table->string('alt_qty_display')->nullable()->after('quantity');
            $table->decimal('alt_qty_boxes', 15, 3)->default(0)->after('alt_qty_display');
            $table->decimal('alt_qty_pieces', 15, 3)->default(0)->after('alt_qty_boxes');
            
            // Base quantity in sft (or product's base unit)
            $table->decimal('base_quantity', 15, 4)->nullable()->after('alt_qty_pieces');
            
            // Account for passive income items (Labour, Transportation, etc.)
            $table->foreignId('passive_account_id')
                ->nullable()
                ->after('product_id')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['passive_account_id']);
            $table->dropColumn([
                'alt_qty_display',
                'alt_qty_boxes',
                'alt_qty_pieces',
                'base_quantity',
                'passive_account_id',
            ]);
        });
    }
};