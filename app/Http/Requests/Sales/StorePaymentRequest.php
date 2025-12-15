<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,cheque,bank_transfer,mobile_banking,online',
            'account_id' => 'required|exists:accounts,id',
            
            // Cheque details
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:100',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'bank_name' => 'required_if:payment_method,cheque|nullable|string|max:255',
            
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'invoice_id.required' => 'Invoice is required',
            'invoice_id.exists' => 'Selected invoice does not exist',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than 0',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
            'account_id.required' => 'Payment account is required',
            'account_id.exists' => 'Selected account does not exist',
            'cheque_number.required_if' => 'Cheque number is required for cheque payment',
            'cheque_date.required_if' => 'Cheque date is required for cheque payment',
            'bank_name.required_if' => 'Bank name is required for cheque payment',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'cheque_number' => $this->cheque_number ?: null,
            'cheque_date' => $this->cheque_date ?: null,
            'bank_name' => $this->bank_name ?: null,
        ]);
    }
}
