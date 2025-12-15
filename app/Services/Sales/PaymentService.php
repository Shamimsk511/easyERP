<?php

namespace App\Services\Sales;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Record payment against an invoice
     * Creates double-entry transaction:
     * - Debit: Cash/Bank Account (Asset increases)
     * - Credit: Customer AR Account (Asset decreases - they owe less)
     */
    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        DB::beginTransaction();
        try {
            $amount = (float) $data['amount'];
            $paymentNumber = $this->generatePaymentNumber();

            // Get cash/bank account
            $cashAccount = Account::findOrFail($data['account_id']);

            // Get AR account (customer's ledger account)
            $arAccount = $invoice->customer->ledger_account_id
                ? Account::find($invoice->customer->ledger_account_id)
                : Account::where('type', 'asset')->where('code', '1210')->first();

            if (!$arAccount) {
                throw new \Exception('Customer receivable account not found');
            }

            // Create payment record
            $payment = InvoicePayment::create([
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'account_id' => $cashAccount->id,
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            // Create accounting transaction
            $transaction = Transaction::create([
                'date' => $payment->payment_date,
                'type' => 'receipt',
                'reference' => $paymentNumber,
                'description' => "Payment received for Invoice #{$invoice->invoice_number} - {$invoice->customer->name}",
                'status' => 'posted',
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
            ]);

            // Debit: Cash/Bank Account (Asset increases)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $cashAccount->id,
                'type' => 'debit',
                'amount' => $amount,
                'memo' => "Payment {$paymentNumber} from {$invoice->customer->name}",
            ]);

            // Credit: AR Account (Receivable decreases)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $arAccount->id,
                'type' => 'credit',
                'amount' => $amount,
                'memo' => "Payment {$paymentNumber} against Invoice {$invoice->invoice_number}",
            ]);

            // Update payment with transaction ID
            $payment->update(['transaction_id' => $transaction->id]);

            // Update invoice paid amount
            $invoice->recordPayment($amount);

            DB::commit();

            Log::info('Payment recorded successfully', [
                'payment_id' => $payment->id,
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment recording failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete payment and reverse transaction
     */
    public function deletePayment(InvoicePayment $payment): bool
    {
        DB::beginTransaction();
        try {
            $invoice = $payment->invoice;
            $amount = (float) $payment->amount;

            // Void the accounting transaction
            if ($payment->transaction_id) {
                $transaction = Transaction::find($payment->transaction_id);
                if ($transaction) {
                    $transaction->update(['status' => 'voided']);
                    $transaction->entries()->delete();
                }
            }

            // Reverse the payment on invoice
            $invoice->total_paid = max(0, (float) $invoice->total_paid - $amount);
            $invoice->save();

            // Soft delete the payment
            $payment->delete();

            DB::commit();

            Log::info('Payment deleted successfully', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'invoice_id' => $invoice->id,
                'amount_reversed' => $amount,
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
    public function generatePaymentNumber(): string
    {
        $prefix = 'PAY-' . date('Ym') . '-';
        $lastPayment = InvoicePayment::withTrashed()
            ->where('payment_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Get payment statistics for a customer
     */
    public function getCustomerPaymentStats(int $customerId): array
    {
        $payments = InvoicePayment::whereHas('invoice', fn($q) => $q->where('customer_id', $customerId))
            ->selectRaw('
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MAX(payment_date) as last_payment_date
            ')
            ->first();

        $byMethod = InvoicePayment::whereHas('invoice', fn($q) => $q->where('customer_id', $customerId))
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        return [
            'total_payments' => (int) $payments->total_payments,
            'total_amount' => (float) $payments->total_amount,
            'average_amount' => (float) $payments->average_amount,
            'last_payment_date' => $payments->last_payment_date,
            'by_method' => $byMethod->toArray(),
        ];
    }
}