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
        Schema::create('customer_ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('cascade');
            $table->string('voucher_type'); // Sales, Receipt, Journal, etc.
            $table->string('voucher_number');
            $table->date('transaction_date');
            $table->decimal('debit', 15, 2)->default(0); // Sales/Outstanding
            $table->decimal('credit', 15, 2)->default(0); // Receipts/Payments
            $table->decimal('balance', 15, 2)->default(0); // Running balance
            $table->text('narration')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_settled')->default(false);
            $table->timestamps();
            
            $table->index(['customer_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_transactions');
    }
};
