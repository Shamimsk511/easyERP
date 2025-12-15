<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Add vendor_code for unique identification
            if (!Schema::hasColumn('vendors', 'vendor_code')) {
                $table->string('vendor_code', 50)->unique()->nullable()->after('id');
            }
            
            // Add company_name
            if (!Schema::hasColumn('vendors', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }
            
            // Add contact fields
            if (!Schema::hasColumn('vendors', 'email')) {
                $table->string('email')->nullable()->after('company_name');
            }
            
            if (!Schema::hasColumn('vendors', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('vendors', 'mobile')) {
                $table->string('mobile', 20)->nullable()->after('phone');
            }
            
            // Add address fields
            if (!Schema::hasColumn('vendors', 'address')) {
                $table->text('address')->nullable()->after('mobile');
            }
            
            if (!Schema::hasColumn('vendors', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('vendors', 'state')) {
                $table->string('state', 100)->nullable()->after('city');
            }
            
            if (!Schema::hasColumn('vendors', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('state');
            }
            
            if (!Schema::hasColumn('vendors', 'country')) {
                $table->string('country', 100)->default('Bangladesh')->after('postal_code');
            }
            
            // Add is_active flag
            if (!Schema::hasColumn('vendors', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('opening_balance_date');
            }
            
            // Add indexes for search
            $table->index(['name', 'phone', 'city'], 'vendors_search_index');
        });
        
        // Generate vendor_code for existing records
        $vendors = \App\Models\Vendor::whereNull('vendor_code')->get();
        foreach ($vendors as $index => $vendor) {
            $vendor->vendor_code = 'V' . str_pad($vendor->id, 5, '0', STR_PAD_LEFT);
            $vendor->saveQuietly();
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendors_search_index');
            
            $columns = [
                'vendor_code', 'company_name', 'email', 'phone', 'mobile',
                'address', 'city', 'state', 'postal_code', 'country', 'is_active'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};