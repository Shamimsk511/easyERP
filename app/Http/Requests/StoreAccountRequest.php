<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
            'description' => ['nullable', 'string'],
            'parent_account_id' => ['nullable', 'exists:accounts,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This account code is already in use.',
            'type.in' => 'Please select a valid account type.',
            'parent_account_id.exists' => 'The selected parent account does not exist.',
        ];
    }
}
