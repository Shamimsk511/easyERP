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
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->date('payment_date');
            $table->string('payment_method')->default('cash'); // cash, bank, cheque, etc.
            $table->decimal('amount', 15, 2);
            $table->string('payee_type')->nullable(); // vendor, customer, employee, other
            $table->unsignedBigInteger('payee_id')->nullable(); // ID of vendor/customer/etc
            $table->foreignId('paid_from_account_id')->constrained('accounts')->onDelete('restrict');
            $table->foreignId('paid_to_account_id')->constrained('accounts')->onDelete('restrict');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('posted'); // draft, posted, cancelled
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('payment_date');
            $table->index('payee_type');
            $table->index('payee_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
