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
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('description');
            $table->enum('opening_balance_type', ['debit', 'credit'])->default('credit')->after('opening_balance');
            $table->date('opening_balance_date')->nullable()->after('opening_balance_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'opening_balance_type', 'opening_balance_date']);
        });
    }
};
