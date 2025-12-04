<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contra_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->date('contra_date');
            $table->decimal('amount', 15, 2);
            
            // From Account (Source)
            $table->foreignId('from_account_id')
                ->constrained('accounts')
                ->onDelete('restrict');
            
            // To Account (Destination)
            $table->foreignId('to_account_id')
                ->constrained('accounts')
                ->onDelete('restrict');
            
            // Transaction reference
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->onDelete('set null');
            
            // Transfer method details
            $table->enum('transfer_method', ['cash', 'bank_transfer', 'cheque', 'online'])
                ->default('cash');
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('reference_number')->nullable(); // For online/bank transfers
            
            $table->string('description', 500);
            $table->text('notes')->nullable();
            
            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft');
            
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('voucher_number');
            $table->index('contra_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contra_vouchers');
    }
};
