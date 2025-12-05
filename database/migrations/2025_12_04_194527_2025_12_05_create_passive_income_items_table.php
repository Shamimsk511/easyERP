<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passive_income_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Labour Bill, Transportation, Service Charge, etc.
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict'); // Income account
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passive_income_items');
    }
};
