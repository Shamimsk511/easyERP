<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow editing pending orders
        $purchaseOrder = $this->route('purchaseOrder') ?? $this->route('purchase_order');
        return $purchaseOrder && $purchaseOrder->status === 'pending';
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
            'items.required' => 'At least one item is required',
            'items.*.product_id.required' => 'Product is required for each item',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.rate.required' => 'Rate is required',
        ];
    }

    /**
     * Handle failed authorization
     */
    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Cannot edit a purchase order that has been received.'
        );
    }

    /**
     * Get validated data with calculated amounts
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Add calculated amount to each item
        if (isset($validated['items'])) {
            $total = 0;
            foreach ($validated['items'] as &$item) {
                $item['amount'] = ((float) $item['quantity']) * ((float) $item['rate']);
                $total += $item['amount'];
            }
            $validated['total_amount'] = $total;
        }

        return $validated;
    }
}