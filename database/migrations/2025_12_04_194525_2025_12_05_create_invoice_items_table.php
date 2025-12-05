<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            
            // Item Type: product or passive_income
            $table->enum('item_type', ['product', 'passive_income'])->default('product');
            
            // Product Details
            $table->string('description'); // Product name or service description
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            
            // Quantities & Rates
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            
            // Discount
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            
            // Line Total
            $table->decimal('line_total', 15, 2); // quantity * unit_price - discount
            
            // Customer History - Retain rate for this specific customer
            $table->decimal('rate_given_to_customer', 15, 2)->nullable();
            
            // Delivered Quantity (for partial delivery tracking)
            $table->decimal('delivered_quantity', 15, 3)->default(0);
            
            $table->timestamps();
            $table->index(['invoice_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
