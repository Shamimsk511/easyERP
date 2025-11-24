<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->decimal('conversion_factor', 15, 4); // e.g., 1 box = 25 pieces
            $table->boolean('is_default')->default(false);
            $table->boolean('is_purchase_unit')->default(false);
            $table->boolean('is_sales_unit')->default(false);
            $table->timestamps();
            
            $table->unique(['product_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
