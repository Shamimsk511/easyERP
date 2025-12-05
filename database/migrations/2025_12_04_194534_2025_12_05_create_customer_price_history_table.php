<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('rate', 15, 2);
            $table->date('last_sold_date');
            $table->timestamps();
            
            $table->unique(['customer_id', 'product_id']);
            $table->index(['customer_id', 'last_sold_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_price_history');
    }
};
