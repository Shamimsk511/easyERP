<?php

namespace App\Services\Sales;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Record payment on invoice
     * Creates double-entry: Debit Cash/Bank, Credit Customer Ledger
     */
    public function recordPayment(Invoice $invoice, array $data)
    {
        DB::beginTransaction();
        try {
            $paymentNumber = $this->generatePaymentNumber();
            
            $amount = $data['amount'];
            $account = Account::find($data['account_id']);

            // Validate amount doesn't exceed outstanding
            $outstanding = $invoice->outstanding_balance;
            if ($amount > $outstanding) {
                throw new \Exception("Payment amount exceeds outstanding balance of {$outstanding}");
            }

            // Create payment record
            $payment = InvoicePayment::create([
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'account_id' => $account->id,
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create accounting transaction
            $this->createPaymentTransaction($payment, $invoice, $account, $amount);

            DB::commit();

            Log::info('Payment recorded', [
                'payment_id' => $payment->id,
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment recording failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create double-entry accounting for payment
     * Debit: Cash/Bank Account
     * Credit: Customer Ledger (AR)
     */
    private function createPaymentTransaction(InvoicePayment $payment, Invoice $invoice, Account $account, $amount)
    {
        $transaction = Transaction::create([
            'date' => $payment->payment_date,
            'reference' => $payment->payment_number,
            'description' => "Payment for Invoice #{$invoice->invoice_number} - {$invoice->customer->name}",
            'status' => 'posted',
        ]);

        // Debit: Cash/Bank Account
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => $amount,
            'memo' => "Payment {$payment->payment_number}",
        ]);

        // Credit: Customer Ledger
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $invoice->customer_ledger_account_id,
            'type' => 'credit',
            'amount' => $amount,
            'memo' => "Payment {$payment->payment_number}",
        ]);

        $payment->transaction_id = $transaction->id;
        $payment->save();

        return $transaction;
    }

    /**
     * Delete payment (revert transaction)
     */
    public function deletePayment(InvoicePayment $payment)
    {
        DB::beginTransaction();
        try {
            // Revert transaction
            if ($payment->transaction_id) {
                $transaction = Transaction::find($payment->transaction_id);
                if ($transaction) {
                    $transaction->update(['status' => 'voided']);
                    $transaction->entries()->delete();
                }
            }

            $payment->delete();

            DB::commit();

            Log::info('Payment deleted', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment deletion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique payment number
     */
    public function generatePaymentNumber()
    {
        $lastPayment = InvoicePayment::withTrashed()->latest('id')->first();
        $lastNumber = $lastPayment 
            ? (int)substr($lastPayment->payment_number, -3) 
            : 0;
        
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return 'RP' . date('y') . $newNumber; // Format: RP25001, RP25002, etc.
    }
}
