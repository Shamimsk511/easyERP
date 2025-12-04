<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('product_movements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
        $table->enum('type', ['purchase', 'sale', 'adjustment', 'opening_stock', 'return']);
        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();
        $table->decimal('quantity', 15, 4);
        $table->decimal('rate', 15, 2)->nullable();
        $table->decimal('stock_before', 15, 4)->default(0); // ← ADD DEFAULT
        $table->decimal('stock_after', 15, 4)->default(0);  // ← ADD DEFAULT
        $table->date('movement_date');
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
        
        // Indexes
        $table->index(['product_id', 'movement_date']);
        $table->index(['reference_type', 'reference_id']);
        $table->index('type');
    });
}


    public function down(): void
    {
        Schema::dropIfExists('product_movements');
    }
};
