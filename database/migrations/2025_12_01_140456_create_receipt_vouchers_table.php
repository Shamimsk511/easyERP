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
        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->date('receipt_date');
            $table->string('payment_method')->default('cash'); // cash, bank, cheque, mobile_banking
            $table->decimal('amount', 15, 2);
            
            // Customer information
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            
            // Accounting accounts
            $table->foreignId('received_in_account_id')->constrained('accounts')->onDelete('restrict'); // Cash/Bank Account (Debit)
            $table->foreignId('customer_account_id')->constrained('accounts')->onDelete('restrict'); // Customer Ledger (Credit)
            
            // Transaction linkage
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            
            // Cheque details
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('posted'); // draft, posted, cancelled
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('receipt_date');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_vouchers');
    }
};
