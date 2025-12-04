<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    /**
     * Display company settings
     */
    public function index()
    {
        $settings = CompanySetting::getInstance();
        
        return view('settings.company.index', compact('settings'));
    }

    /**
     * Update company settings
     */
public function update(Request $request)
{
    $validated = $request->validate([
        // Company Information
        'company_name' => 'required|string|max:255',
        'company_name_bangla' => 'nullable|string|max:255',
        'tagline' => 'nullable|string|max:255',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        'favicon' => 'nullable|image|mimes:png,ico|max:512',
        
        // Contact Information
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'mobile' => 'nullable|string|max:50',
        'fax' => 'nullable|string|max:50',
        'website' => 'nullable|url|max:255',
        
        // Address Information
        'address' => 'required|string',
        'city' => 'nullable|string|max:100',
        'state' => 'nullable|string|max:100',
        'postal_code' => 'nullable|string|max:20',
        'country' => 'required|string|max:100',
        
        // Business Information
        'bin' => 'nullable|string|max:50',
        'tin' => 'nullable|string|max:50',
        'trade_license' => 'nullable|string|max:50',
        'vat_registration' => 'nullable|string|max:50',
        
        // Bank Accounts (handled separately)
        'bank_accounts' => 'nullable|json',
        
        // Document Settings
        'terms_and_conditions' => 'nullable|string',
        'invoice_footer' => 'nullable|string|max:500',
        'quotation_footer' => 'nullable|string|max:500',
        'receipt_footer' => 'nullable|string|max:500',
        
        // Fiscal Year
        'fiscal_year_start' => 'nullable|date',
        'fiscal_year_end' => 'nullable|date|after:fiscal_year_start',
        
        // Currency Settings
        'currency_code' => 'required|string|max:10',
        'currency_symbol' => 'required|string|max:10',
        'currency_position' => 'required|in:left,right',
        
        // Print Settings - Remove validation for boolean fields
        'print_paper_size' => 'required|in:A4,Letter,Legal',
    ]);

    DB::beginTransaction();
    try {
        $settings = CompanySetting::getInstance();
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            
            $logoPath = $request->file('logo')->store('company', 'public');
            $validated['logo'] = $logoPath;
        }
        
        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            // Delete old favicon
            if ($settings->favicon && Storage::disk('public')->exists($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
            }
            
            $faviconPath = $request->file('favicon')->store('company', 'public');
            $validated['favicon'] = $faviconPath;
        }
        
        // Handle bank accounts
        if ($request->filled('bank_accounts')) {
            $validated['bank_accounts'] = json_decode($request->bank_accounts, true);
        }
        
        // Convert checkboxes to proper boolean using Laravel's boolean() method
        $validated['show_logo_in_print'] = $request->boolean('show_logo_in_print');
        $validated['show_company_info_in_print'] = $request->boolean('show_company_info_in_print');
        
        $settings->update($validated);
        
        // Clear cache
        \App\Helpers\CompanyHelper::clearCache();
        
        DB::commit();
        
        Log::info('Company settings updated', [
            'user_id' => auth()->id(),
            'company_name' => $validated['company_name']
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Company settings updated successfully!',
                'data' => $settings,
            ]);
        }
        
        return redirect()
            ->route('settings.company.index')
            ->with('success', 'Company settings updated successfully!');
            
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Company settings update error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings: ' . $e->getMessage(),
            ], 500);
        }
        
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Error updating settings: ' . $e->getMessage());
    }
}


    /**
     * Delete logo
     */
    public function deleteLogo()
    {
        try {
            $settings = CompanySetting::getInstance();
            
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
                $settings->update(['logo' => null]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Logo deleted successfully!',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Logo deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting logo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete favicon
     */
    public function deleteFavicon()
    {
        try {
            $settings = CompanySetting::getInstance();
            
            if ($settings->favicon && Storage::disk('public')->exists($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
                $settings->update(['favicon' => null]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Favicon deleted successfully!',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Favicon deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting favicon: ' . $e->getMessage(),
            ], 500);
        }
    }
}
