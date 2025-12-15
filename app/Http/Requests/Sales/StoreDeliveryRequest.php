<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'delivery_date' => 'required|date',
            'delivery_method' => 'nullable|in:auto,motorcycle,truck,pickup,other',
            'driver_name' => 'nullable|string|max:255',
            'delivered_by_user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:500',
            
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'items.*.delivered_quantity' => 'required|numeric|min:0.001',
        ];
    }

    public function messages()
    {
        return [
            'invoice_id.required' => 'Invoice is required',
            'invoice_id.exists' => 'Selected invoice does not exist',
            'delivery_date.required' => 'Delivery date is required',
            'items.required' => 'At least one item is required for delivery',
            'items.min' => 'At least one item is required for delivery',
            'items.*.invoice_item_id.required' => 'Invoice item is required',
            'items.*.invoice_item_id.exists' => 'Selected invoice item does not exist',
            'items.*.delivered_quantity.required' => 'Delivered quantity is required',
            'items.*.delivered_quantity.min' => 'Delivered quantity must be greater than 0',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'delivery_method' => $this->delivery_method ?: 'auto',
            'delivered_by_user_id' => $this->delivered_by_user_id ?: auth()->id(),
        ]);
    }
}
