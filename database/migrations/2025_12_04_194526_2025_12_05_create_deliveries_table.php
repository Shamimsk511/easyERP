<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('challan_number')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->date('delivery_date');
            
            // Delivery Details
            $table->string('delivery_method')->default('auto'); // auto, manual, courier, etc.
            $table->string('driver_name')->nullable();
            $table->foreignId('delivered_by_user_id')->constrained('users')->onDelete('restrict');
            
            // Delivery Note
            $table->text('notes')->nullable();
            
            // Accounting
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // No status - creating delivery means it's delivered
            // Reverting through soft delete reverts all transactions
            
            $table->timestamps();
            $table->softDeletes();
            $table->index(['invoice_id', 'delivery_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
