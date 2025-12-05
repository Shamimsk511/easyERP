<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            
            // Customer Information
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            
            // Accounting Fields
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('customer_ledger_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('sales_return_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            // Amount Fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            
            // Outstanding Balance
            $table->decimal('outstanding_at_creation', 15, 2)->default(0);
            
            // Status
            $table->enum('delivery_status', ['pending', 'partial', 'delivered'])->default('pending');
            
            // Notes
            $table->text('internal_notes')->nullable();
            $table->text('customer_notes')->nullable();
            
            // Soft Delete & Audit
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes(); // Creates deleted_at automatically
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'invoice_date']);
            $table->index(['invoice_date', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
