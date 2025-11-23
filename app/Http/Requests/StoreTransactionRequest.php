<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['in:draft,posted,voided'],
            
            // Transaction entries
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'exists:accounts,id'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.type' => ['required', 'in:debit,credit'],
            'entries.*.memo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.min' => 'A transaction must have at least 2 entries (debit and credit).',
            'entries.*.account_id.required' => 'Please select an account for each entry.',
            'entries.*.account_id.exists' => 'The selected account does not exist.',
            'entries.*.amount.required' => 'Amount is required for each entry.',
            'entries.*.amount.min' => 'Amount must be greater than zero.',
            'entries.*.type.in' => 'Entry type must be either debit or credit.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $entries = $this->input('entries', []);
            
            $totalDebits = 0;
            $totalCredits = 0;
            
            foreach ($entries as $entry) {
                if (isset($entry['type']) && isset($entry['amount'])) {
                    if ($entry['type'] === 'debit') {
                        $totalDebits += floatval($entry['amount']);
                    } elseif ($entry['type'] === 'credit') {
                        $totalCredits += floatval($entry['amount']);
                    }
                }
            }
            
            if (abs($totalDebits - $totalCredits) > 0.01) {
                $validator->errors()->add('entries', 'Total debits must equal total credits.');
            }
        });
    }
}
