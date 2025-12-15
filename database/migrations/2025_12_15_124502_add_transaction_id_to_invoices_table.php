<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add transaction_id for linking to accounting transaction
            if (!Schema::hasColumn('invoices', 'transaction_id')) {
                $table->foreignId('transaction_id')
                    ->nullable()
                    ->after('customer_ledger_account_id')
                    ->constrained('transactions')
                    ->onDelete('set null');
            }
            
            // Add status column if missing
            if (!Schema::hasColumn('invoices', 'status')) {
                $table->enum('status', ['draft', 'posted', 'cancelled'])
                    ->default('posted')
                    ->after('delivery_status');
            }
            
            // Add audit columns if missing
            if (!Schema::hasColumn('invoices', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('customer_notes')
                    ->constrained('users')
                    ->onDelete('set null');
            }
            
            if (!Schema::hasColumn('invoices', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('invoices', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
                $table->dropColumn('transaction_id');
            }
            
            if (Schema::hasColumn('invoices', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            
            if (Schema::hasColumn('invoices', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }
            
            if (Schema::hasColumn('invoices', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};