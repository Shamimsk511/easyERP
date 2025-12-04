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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name')->unique();
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Bangladesh');
            
            // Accounting Fields
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->foreignId('ledger_account_id')->constrained('accounts')->onDelete('cascade'); // Link to accounts table
            $table->decimal('opening_balance', 15, 2)->default(0); // Positive = Outstanding, Negative = Advance
            $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit'); // debit = outstanding, credit = advance
            $table->date('opening_balance_date')->nullable();
            
            // Credit Management
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('credit_period_days')->default(0); // Default credit period
            $table->date('current_due_date')->nullable();
            $table->integer('total_extended_days')->default(0); // Track total extensions
            $table->integer('extension_count')->default(0); // Number of times extended
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for search
            $table->index(['name', 'phone', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
