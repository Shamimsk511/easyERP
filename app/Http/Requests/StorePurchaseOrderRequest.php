<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'purchase_account_id' => ['nullable', 'exists:accounts,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Items validation
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.required' => 'Please select a vendor',
            'vendor_id.exists' => 'Selected vendor does not exist',
            'order_date.required' => 'Order date is required',
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.product_id.required' => 'Product is required for each item',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'items.*.rate.required' => 'Rate is required',
            'items.*.rate.min' => 'Rate must be greater than 0',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Calculate total if not provided
        if ($this->has('items')) {
            $total = collect($this->items)->sum(function ($item) {
                return ((float) ($item['quantity'] ?? 0)) * ((float) ($item['rate'] ?? 0));
            });

            $this->merge(['total_amount' => $total]);
        }
    }

    /**
     * Get validated data with calculated amounts
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Add calculated amount to each item
        if (isset($validated['items'])) {
            foreach ($validated['items'] as &$item) {
                $item['amount'] = ((float) $item['quantity']) * ((float) $item['rate']);
            }
        }

        return $validated;
    }
}