<?php

namespace App\Helpers;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;

class CompanyHelper
{
    /**
     * Get company settings (cached)
     */
    public static function getSettings()
    {
        return Cache::remember('company_settings', 3600, function () {
            return CompanySetting::getInstance();
        });
    }

    /**
     * Clear company settings cache
     */
    public static function clearCache()
    {
        Cache::forget('company_settings');
    }

    /**
     * Get company name
     */
    public static function getName(): string
    {
        return self::getSettings()->company_name ?? 'Your Company';
    }

    /**
     * Get company logo URL
     */
    public static function getLogoUrl(): ?string
    {
        return self::getSettings()->logo_url;
    }

    /**
     * Get company address
     */
    public static function getAddress(): string
    {
        return self::getSettings()->full_address ?? '';
    }

    /**
     * Format currency
     */
    public static function formatCurrency(float $amount): string
    {
        return self::getSettings()->formatCurrency($amount);
    }

    /**
     * Get all bank accounts
     */
    public static function getBankAccounts(): array
    {
        return self::getSettings()->bank_accounts ?? [];
    }

    /**
     * Get terms and conditions
     */
    public static function getTerms(): ?string
    {
        return self::getSettings()->terms_and_conditions;
    }

    /**
     * Get invoice footer
     */
    public static function getInvoiceFooter(): ?string
    {
        return self::getSettings()->invoice_footer;
    }

    /**
     * Get receipt footer
     */
    public static function getReceiptFooter(): ?string
    {
        return self::getSettings()->receipt_footer;
    }

    /**
     * Get quotation footer
     */
    public static function getQuotationFooter(): ?string
    {
        return self::getSettings()->quotation_footer;
    }

    /**
     * Get company info for print header
     */
    public static function getPrintHeader(): array
    {
        $settings = self::getSettings();
        
        return [
            'company_name' => $settings->company_name,
            'company_name_bangla' => $settings->company_name_bangla,
            'tagline' => $settings->tagline,
            'logo_url' => $settings->logo_url,
            'address' => $settings->full_address,
            'phone' => $settings->phone,
            'mobile' => $settings->mobile,
            'email' => $settings->email,
            'website' => $settings->website,
            'bin' => $settings->bin,
            'tin' => $settings->tin,
            'show_logo' => $settings->show_logo_in_print,
            'show_company_info' => $settings->show_company_info_in_print,
        ];
    }
}
