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
// Optional: create_trial_balance_cache_table.php
Schema::create('trial_balance_cache', function (Blueprint $table) {
    $table->id();
    $table->date('as_of_date');
    $table->json('balance_data');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_balance_cache');
    }
};
