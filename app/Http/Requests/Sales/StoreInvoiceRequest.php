<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'sales_account_id' => 'nullable|exists:accounts,id',
            
            // Product items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            
            // Passive income items
            'passive_items' => 'nullable|array',
            'passive_items.*.description' => 'required|string|max:255',
            'passive_items.*.quantity' => 'nullable|numeric|min:1',
            'passive_items.*.amount' => 'required|numeric|min:0',
            
            'tax_amount' => 'nullable|numeric|min:0',
            'internal_notes' => 'nullable|string|max:1000',
            'customer_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'customer_id.required' => 'Please select a customer',
            'customer_id.exists' => 'Selected customer does not exist',
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'items.*.unit_price.required' => 'Unit price is required',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater',
        ];
    }

    protected function prepareForValidation()
    {
        // Convert empty strings to null
        $this->merge([
            'due_date' => $this->due_date ?: null,
            'sales_account_id' => $this->sales_account_id ?: null,
            'tax_amount' => $this->tax_amount ?: 0,
        ]);
    }
}
