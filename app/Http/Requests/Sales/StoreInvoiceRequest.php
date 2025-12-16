<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            
            // Tally-like account selection
            'sales_account_id' => 'nullable|exists:accounts,id',
            'labour_account_id' => 'nullable|exists:accounts,id',
            'transportation_account_id' => 'nullable|exists:accounts,id',
            
            // Product items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            
            // Passive income items (labour, transportation as line items)
            'passive_items' => 'nullable|array',
            'passive_items.*.account_id' => 'nullable|exists:accounts,id',
            'passive_items.*.description' => 'required_with:passive_items.*.amount|string|max:255',
            'passive_items.*.quantity' => 'nullable|numeric|min:1',
            'passive_items.*.amount' => 'nullable|numeric|min:0',
            
            // Additional charges (Tally-style separate fields)
            'labour_amount' => 'nullable|numeric|min:0',
            'transportation_amount' => 'nullable|numeric|min:0',
            
            // Totals
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'round_off_amount' => 'nullable|numeric',
            
            // Notes
            'internal_notes' => 'nullable|string|max:1000',
            'customer_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer',
            'customer_id.exists' => 'Selected customer does not exist',
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.description.required' => 'Item description is required',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'items.*.unit_price.required' => 'Unit price is required',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater',
            'sales_account_id.exists' => 'Selected sales account is invalid',
            'labour_account_id.exists' => 'Selected labour account is invalid',
            'transportation_account_id.exists' => 'Selected transportation account is invalid',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'due_date' => $this->due_date ?: null,
            'sales_account_id' => $this->sales_account_id ?: null,
            'labour_account_id' => $this->labour_account_id ?: null,
            'transportation_account_id' => $this->transportation_account_id ?: null,
            'discount_amount' => $this->discount_amount ?: 0,
            'tax_amount' => $this->tax_amount ?: 0,
            'labour_amount' => $this->labour_amount ?: 0,
            'transportation_amount' => $this->transportation_amount ?: 0,
            'round_off_amount' => $this->round_off_amount ?: 0,
        ]);
    }
}