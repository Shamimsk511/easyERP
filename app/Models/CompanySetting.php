<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_name_bangla',
        'tagline',
        'logo',
        'favicon',
        'email',
        'phone',
        'mobile',
        'fax',
        'website',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'bin',
        'tin',
        'trade_license',
        'vat_registration',
        'bank_accounts',
        'terms_and_conditions',
        'invoice_footer',
        'quotation_footer',
        'receipt_footer',
        'fiscal_year_start',
        'fiscal_year_end',
        'currency_code',
        'currency_symbol',
        'currency_position',
        'show_logo_in_print',
        'show_company_info_in_print',
        'print_paper_size',
    ];

    protected $casts = [
        'bank_accounts' => 'array',
        'fiscal_year_start' => 'date',
        'fiscal_year_end' => 'date',
        'show_logo_in_print' => 'boolean',
        'show_company_info_in_print' => 'boolean',
    ];

    /**
     * Get the singleton instance of company settings
     */
    public static function getInstance()
    {
        $settings = static::first();
        
        if (!$settings) {
            $settings = static::create([
                'company_name' => 'Your Company Name',
                'address' => 'Your Company Address',
                'country' => 'Bangladesh',
                'currency_code' => 'BDT',
                'currency_symbol' => '৳',
            ]);
        }
        
        return $settings;
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return Storage::url($this->logo);
        }
        
        return null;
    }

    /**
     * Get favicon URL
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if ($this->favicon && Storage::disk('public')->exists($this->favicon)) {
            return Storage::url($this->favicon);
        }
        
        return null;
    }

    /**
     * Get formatted address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Format currency
     */
    public function formatCurrency(float $amount): string
    {
        $formatted = number_format($amount, 2);
        
        if ($this->currency_position === 'left') {
            return $this->currency_symbol . ' ' . $formatted;
        }
        
        return $formatted . ' ' . $this->currency_symbol;
    }

    /**
     * Get bank accounts as collection
     */
    public function getBankAccountsListAttribute()
    {
        return collect($this->bank_accounts ?? []);
    }
}
