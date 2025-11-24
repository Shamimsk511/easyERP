<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->string('code', 50)->unique()->nullable();
            $table->foreignId('product_group_id')->nullable()->constrained('product_groups')->onDelete('restrict');
            $table->foreignId('base_unit_id')->constrained('units')->onDelete('restrict');
            $table->text('description')->nullable();
            
            // Opening Stock fields
            $table->decimal('opening_quantity', 15, 3)->default(0);
            $table->decimal('opening_rate', 15, 2)->nullable();
            $table->date('opening_date')->nullable();
            
            // Inventory Accounts
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            
            // Product details
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('maximum_stock', 15, 3)->default(0);
            $table->decimal('reorder_level', 15, 3)->default(0);
            
            // Pricing
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->decimal('mrp', 15, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
