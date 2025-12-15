<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
            
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            
            'passive_items' => 'nullable|array',
            'passive_items.*.description' => 'required|string|max:255',
            'passive_items.*.quantity' => 'nullable|numeric|min:1',
            'passive_items.*.amount' => 'required|numeric|min:0',
            
            'tax_amount' => 'nullable|numeric|min:0',
            'internal_notes' => 'nullable|string|max:1000',
            'customer_notes' => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'due_date' => $this->due_date ?: null,
            'sales_account_id' => $this->sales_account_id ?: null,
            'tax_amount' => $this->tax_amount ?: 0,
        ]);
    }
}
