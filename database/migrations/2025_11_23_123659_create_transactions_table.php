<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->string('description');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'voided'])->default('posted');
            $table->timestamps();
            
            $table->index(['date', 'status']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
