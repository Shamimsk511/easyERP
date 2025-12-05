<?php

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\TransactionEntry;
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

            $customer = \App\Models\Customer::findOrFail($data['customer_id']);

            // Get default accounts if not provided
            $salesAccount = $data['sales_account_id']
                ? Account::find($data['sales_account_id'])
                : Account::where('code', '4100')->first(); // Default: Sales

            $arAccount = $customer->ledger_account_id
                ? Account::find($customer->ledger_account_id)
                : null;

            // Calculate totals
            $subtotal = 0;
            $totalDiscount = 0;

            // Store current customer outstanding for historical accuracy
            $currentOutstanding = abs($customer->getOutstandingBalance());

            // Create invoice record
            $invoice = Invoice::create([
                'invoice_number'            => $invoiceNumber,
                'invoice_date'              => $data['invoice_date'] ?? now()->toDateString(),
                'due_date'                  => $data['due_date'] ?? null,
                'customer_id'               => $customer->id,
                'sales_account_id'          => $salesAccount->id,
                'customer_ledger_account_id'=> $arAccount->id,
                'delivery_status'           => 'pending',
                'outstanding_at_creation'   => $currentOutstanding,
                'internal_notes'            => $data['internal_notes'] ?? null,
                'customer_notes'            => $data['customer_notes'] ?? null,
            ]);

            // Process product items
            foreach ($data['items'] as $itemData) {
                $item = $this->createInvoiceItem($invoice, $itemData);
                $subtotal      += $item['line_total'];
                $totalDiscount += $item['discount_amount'];
            }

            // Process passive income items if any (labour, transport, etc.)
            if (isset($data['passive_items'])) {
                foreach ($data['passive_items'] as $itemData) {
                    $item = $this->createPassiveIncomeItem($invoice, $itemData);
                    $subtotal += $item['line_total'];
                }
            }

            // Final totals
            $taxAmount   = $data['tax_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount;

            // Update invoice totals
            $invoice->update([
                'subtotal'        => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $totalAmount,
            ]);

            // Create double-entry accounting transaction
            $this->createInvoiceTransaction($invoice, $arAccount, $salesAccount, $totalAmount);

            DB::commit();

            Log::info('Invoice created', [
                'invoice_id'    => $invoice->id,
                'invoice_number'=> $invoiceNumber,
                'customer_id'   => $customer->id,
                'total_amount'  => $totalAmount,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create invoice line item (always store in base unit if unit_id not passed)
     */
    private function createInvoiceItem(Invoice $invoice, array $itemData)
    {
        $quantity        = $itemData['quantity'];
        $unitPrice       = $itemData['unit_price'];
        $discountPercent = $itemData['discount_percent'] ?? 0;

        $lineTotal      = $quantity * $unitPrice;
        $discountAmount = ($lineTotal * $discountPercent) / 100;
        $finalLineTotal = $lineTotal - $discountAmount;

        $product = isset($itemData['product_id'])
            ? \App\Models\Product::find($itemData['product_id'])
            : null;

        // Update customer price history
        if ($product) {
            CustomerPriceHistory::updateOrCreate(
                [
                    'customer_id' => $invoice->customer_id,
                    'product_id'  => $product->id,
                ],
                [
                    'rate'          => $unitPrice,
                    'last_sold_date'=> now(),
                ]
            );
        }

        $unitId = $itemData['unit_id'] ?? ($product ? $product->base_unit_id : null);

        $item = InvoiceItem::create([
            'invoice_id'      => $invoice->id,
            'product_id'      => $product ? $product->id : null,
            'item_type'       => 'product',
            'description'     => $itemData['description'] ?? ($product ? $product->name : 'Item'),
            'unit_id'         => $unitId,
            'quantity'        => $quantity,
            'unit_price'      => $unitPrice,
            'discount_percent'=> $discountPercent,
            'discount_amount' => $discountAmount,
            'line_total'      => $finalLineTotal,
            'rate_given_to_customer' => $unitPrice,
            'delivered_quantity'     => 0,
        ]);

        return [
            'item'            => $item,
            'line_total'      => $finalLineTotal,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Passive income item (no product, no unit)
     */
    private function createPassiveIncomeItem(Invoice $invoice, array $itemData)
    {
        $quantity = $itemData['quantity'] ?? 1;
        $amount   = $itemData['amount'];
        $lineTotal= $quantity * $amount;

        $item = InvoiceItem::create([
            'invoice_id'      => $invoice->id,
            'product_id'      => null,
            'item_type'       => 'passive_income',
            'description'     => $itemData['description'],
            'unit_id'         => null,
            'quantity'        => $quantity,
            'unit_price'      => $amount,
            'discount_percent'=> 0,
            'discount_amount' => 0,
            'line_total'      => $lineTotal,
            'delivered_quantity' => 0,
        ]);

        return [
            'item'            => $item,
            'line_total'      => $lineTotal,
            'discount_amount' => 0,
        ];
    }

    /**
     * Double-entry transaction for invoice
     * Debit: Customer Ledger (AR)
     * Credit: Sales Income
     */
    private function createInvoiceTransaction(Invoice $invoice, Account $arAccount, Account $salesAccount, $totalAmount)
    {
        $transaction = Transaction::create([
            'date'        => $invoice->invoice_date,
            'reference'   => $invoice->invoice_number,
            'description' => "Invoice #{$invoice->invoice_number} - {$invoice->customer->name}",
            'status'      => 'posted',
        ]);

        // Debit AR
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id'     => $arAccount->id,
            'type'           => 'debit',
            'amount'         => $totalAmount,
            'memo'           => "Invoice #{$invoice->invoice_number}",
        ]);

        // Credit Sales
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id'     => $salesAccount->id,
            'type'           => 'credit',
            'amount'         => $totalAmount,
            'memo'           => "Invoice #{$invoice->invoice_number}",
        ]);

        return $transaction;
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber()
    {
        $lastInvoice = Invoice::withTrashed()->latest('id')->first();
        $lastNumber  = $lastInvoice
            ? (int)substr($lastInvoice->invoice_number, -3)
            : 0;

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return date('y') . $newNumber; // e.g. 25001, 25002
    }

    /**
     * Delete invoice with reversal of related transactions/deliveries/payments
     */
    public function deleteInvoice(Invoice $invoice, $userId)
    {
        DB::beginTransaction();
        try {
            // Void related transactions
            $relatedTransactions = Transaction::where('reference', $invoice->invoice_number)->get();
            foreach ($relatedTransactions as $transaction) {
                $transaction->update(['status' => 'voided']);
                $transaction->entries()->delete();
            }

            // Soft delete deliveries (which will revert their transactions)
            foreach ($invoice->deliveries as $delivery) {
                if (!$delivery->deleted_at) {
                    $this->deleteDelivery($delivery);
                }
            }

            // Soft delete payments (which will revert their transactions via PaymentService)
            foreach ($invoice->payments as $payment) {
                if (!$payment->deleted_at) {
                    $payment->delete();
                }
            }

            // Mark invoice as deleted
            $invoice->deleted_by = $userId;
            $invoice->delete();

            DB::commit();

            Log::info('Invoice deleted', [
                'invoice_id'    => $invoice->id,
                'invoice_number'=> $invoice->invoice_number,
                'deleted_by'    => $userId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice deletion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function deleteDelivery(\App\Models\Delivery $delivery)
    {
        if ($delivery->transaction_id) {
            $transaction = Transaction::find($delivery->transaction_id);
            if ($transaction) {
                $transaction->update(['status' => 'voided']);
                $transaction->entries()->delete();
            }
        }

        $delivery->delete();
    }
}
