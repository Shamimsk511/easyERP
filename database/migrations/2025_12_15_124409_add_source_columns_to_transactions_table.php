<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add polymorphic source columns for tracking origin (Invoice, Delivery, Payment, etc.)
            if (!Schema::hasColumn('transactions', 'source_type')) {
                $table->string('source_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('transactions', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            
            // Add composite index for polymorphic relationship
            $table->index(['source_type', 'source_id'], 'transactions_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_source_index');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};