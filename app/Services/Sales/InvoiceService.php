<?php

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Customer;
use App\Models\CustomerPriceHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Create a new invoice with double-entry transactions
     */
    public function createInvoice(array $data)
    {
        DB::beginTransaction();
        try {
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Get customer
            $customer = Customer::findOrFail($data['customer_id']);

            // Get sales account (default to Sales account)
            $salesAccount = $data['sales_account_id'] 
                ? Account::findOrFail($data['sales_account_id'])
                : Account::where('type', 'income')->where('code', '4100')->first();

            if (!$salesAccount) {
                throw new \Exception('Sales account not found');
            }

            // Calculate totals
            $subtotal = $this->calculateSubtotal($data['items'] ?? []);
            $taxAmount = (float)($data['tax_amount'] ?? 0);
            $totalAmount = $subtotal + $taxAmount;

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'sales_account_id' => $salesAccount->id,
                'customer_ledger_account_id' => $customer->ledger_account_id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'total_paid' => 0,
                'delivery_status' => 'pending',
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items
            $totalDebit = 0;
            foreach (($data['items'] ?? []) as $itemData) {
                $itemData['item_type'] = 'product';
                $invoiceItem = $invoice->items()->create($itemData);
                $totalDebit += $invoiceItem->line_total;

                // Update customer price history
                if ($itemData['product_id'] ?? null) {
                    CustomerPriceHistory::createFromInvoice(
                        $customer,
                        $invoiceItem->product,
                        $itemData['quantity'],
                        $itemData['unit_price']
                    );
                }
            }

            // Handle passive items (labor, transportation, etc.)
            foreach (($data['passive_items'] ?? []) as $itemData) {
                $itemData['item_type'] = 'passive';
                $invoice->items()->create($itemData);
                $totalDebit += $itemData['amount'];
            }

            // Create accounting transaction
            $transaction = $this->createInvoiceTransaction($invoice, $salesAccount, $totalDebit, $taxAmount);

            // Update invoice with transaction reference
            $invoice->transaction_id = $transaction->id;
            $invoice->save();

            DB::commit();

            Log::info('Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'total_amount' => $totalAmount,
                'transaction_id' => $transaction->id,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create double-entry transaction for invoice
     * Debit: Accounts Receivable
     * Credit: Sales Account (Revenue)
     */
    private function createInvoiceTransaction(Invoice $invoice, Account $salesAccount, $amount, $taxAmount)
    {
        // Get AR account
        $arAccount = $invoice->customer->ledger_account_id
            ? Account::find($invoice->customer->ledger_account_id)
            : Account::where('type', 'asset')->where('code', '1210')->first();

        if (!$arAccount) {
            throw new \Exception('Accounts Receivable account not found');
        }

        $transaction = Transaction::create([
            'date' => $invoice->invoice_date,
            'reference' => $invoice->invoice_number,
            'description' => "Sales Invoice #{$invoice->invoice_number} - {$invoice->customer->name}",
            'status' => 'posted',
            'source_type' => Invoice::class,
            'source_id' => $invoice->id,
        ]);

        // Debit: AR
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $arAccount->id,
            'type' => 'debit',
            'amount' => $amount,
            'memo' => "Invoice {$invoice->invoice_number}",
        ]);

        // Credit: Sales Account
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $salesAccount->id,
            'type' => 'credit',
            'amount' => $amount,
            'memo' => "Invoice {$invoice->invoice_number}",
        ]);

        // Credit: Tax Payable (if tax)
        if ($taxAmount > 0) {
            $taxAccount = Account::where('type', 'liability')->where('code', '2200')->first();
            if ($taxAccount) {
                TransactionEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $taxAccount->id,
                    'type' => 'credit',
                    'amount' => $taxAmount,
                    'memo' => "Tax on Invoice {$invoice->invoice_number}",
                ]);

                // Adjust debit amount
                $transaction->entries()->where('type', 'debit')->first()->update([
                    'amount' => $amount + $taxAmount,
                ]);
            }
        }

        return $transaction;
    }

    /**
     * Delete invoice and reverse all transactions
     */
    public function deleteInvoice(Invoice $invoice, $deletedBy = null)
    {
        DB::beginTransaction();
        try {
            // Void original transaction
            if ($invoice->transaction_id) {
                $transaction = Transaction::find($invoice->transaction_id);
                if ($transaction) {
                    $transaction->update(['status' => 'voided']);
                    $transaction->entries()->delete();
                }

                // Delete from customer ledger
                \App\Observers\TransactionObserver::deleteCustomerLedger($transaction);
            }

            // Reverse any deliveries
            foreach ($invoice->deliveries as $delivery) {
                $this->reverseDelivery($delivery);
            }

            // Reverse any payments
            foreach ($invoice->payments as $payment) {
                // Will be handled by payment deletion
            }

            // Soft delete invoice
            $invoice->update(['deleted_by' => $deletedBy ?? auth()->id()]);
            $invoice->delete();

            DB::commit();

            Log::info('Invoice deleted successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'deleted_by' => $deletedBy,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice deletion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse a delivery and restore transaction
     */
    private function reverseDelivery($delivery)
    {
        if ($delivery->transaction_id) {
            $transaction = Transaction::find($delivery->transaction_id);
            if ($transaction) {
                $transaction->update(['status' => 'voided']);
                $transaction->entries()->delete();
            }
        }

        // Restore stock
        foreach ($delivery->items as $item) {
            if ($item->invoiceItem->product_id) {
                $product = $item->invoiceItem->product;
                $product->incrementStock($item->delivered_quantity, $item->invoiceItem->unit_id);
            }
        }
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber()
    {
        $lastInvoice = Invoice::withTrashed()->latest('id')->first();
        $lastNumber = $lastInvoice 
            ? (int)substr($lastInvoice->invoice_number, -5)
            : 0;

        $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        return 'INV-' . date('y') . $newNumber;
    }

    /**
     * Calculate subtotal from items
     */
    private function calculateSubtotal($items)
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $baseTotal = (float)$item['quantity'] * (float)$item['unit_price'];
            $discount = ($baseTotal * ((float)($item['discount_percent'] ?? 0))) / 100;
            $subtotal += $baseTotal - $discount;
        }
        return $subtotal;
    }
}
