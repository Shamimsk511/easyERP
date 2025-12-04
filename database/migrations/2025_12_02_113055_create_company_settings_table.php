<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            
            // Company Information
            $table->string('company_name');
            $table->string('company_name_bangla')->nullable();
            $table->string('tagline')->nullable();
            $table->string('logo')->nullable(); // Logo file path
            $table->string('favicon')->nullable(); // Favicon file path
            
            // Contact Information
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->string('website')->nullable();
            
            // Address Information
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Bangladesh');
            
            // Business Information
            $table->string('bin')->nullable(); // Business Identification Number
            $table->string('tin')->nullable(); // Tax Identification Number
            $table->string('trade_license')->nullable();
            $table->string('vat_registration')->nullable();
            
            // Bank Information (Multiple banks support)
            $table->json('bank_accounts')->nullable();
            
            // Document Settings
            $table->text('terms_and_conditions')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->text('quotation_footer')->nullable();
            $table->text('receipt_footer')->nullable();
            
            // Fiscal Year
            $table->date('fiscal_year_start')->nullable();
            $table->date('fiscal_year_end')->nullable();
            
            // Currency Settings
            $table->string('currency_code')->default('BDT');
            $table->string('currency_symbol')->default('৳');
            $table->string('currency_position')->default('left'); // left or right
            
            // Print Settings
            $table->boolean('show_logo_in_print')->default(true);
            $table->boolean('show_company_info_in_print')->default(true);
            $table->string('print_paper_size')->default('A4'); // A4, Letter, etc.
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
