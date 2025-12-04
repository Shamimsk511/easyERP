<?php

use App\Helpers\CompanyHelper;

if (!function_exists('company')) {
    /**
     * Get company settings instance
     */
    function company()
    {
        return CompanyHelper::getSettings();
    }
}

if (!function_exists('company_name')) {
    /**
     * Get company name
     */
    function company_name(): string
    {
        return CompanyHelper::getName();
    }
}

if (!function_exists('company_logo')) {
    /**
     * Get company logo URL
     */
    function company_logo(): ?string
    {
        return CompanyHelper::getLogoUrl();
    }
}

if (!function_exists('company_address')) {
    /**
     * Get company address
     */
    function company_address(): string
    {
        return CompanyHelper::getAddress();
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format amount as currency
     */
    function format_currency(float $amount): string
    {
        return CompanyHelper::formatCurrency($amount);
    }
}

if (!function_exists('company_banks')) {
    /**
     * Get company bank accounts
     */
    function company_banks(): array
    {
        return CompanyHelper::getBankAccounts();
    }
}
