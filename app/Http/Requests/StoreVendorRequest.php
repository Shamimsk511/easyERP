<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:vendors,name'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:vendors,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_type' => ['required_with:opening_balance', 'in:debit,credit'],
            'opening_balance_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vendor name is required',
            'name.unique' => 'A vendor with this name already exists',
            'email.unique' => 'A vendor with this email already exists',
            'opening_balance_type.required_with' => 'Balance type is required when opening balance is provided',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'opening_balance' => $this->opening_balance ?: 0,
            'opening_balance_type' => $this->opening_balance_type ?: 'credit',
            'is_active' => $this->is_active ?? true,
            'country' => $this->country ?: 'Bangladesh',
        ]);
    }
}