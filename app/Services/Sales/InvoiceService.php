<?php

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Customer;
use App\Models\Product;
use App\Models\CustomerPriceHistory;
use App\Models\PassiveIncomeItem;
use App\Services\UnitConversionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        protected UnitConversionService $unitConversionService
    ) {}

    /**
     * Create a new invoice with double-entry transactions
     */
    public function createInvoice(array $data): Invoice
    {
        DB::beginTransaction();
        try {
            $invoiceNumber = $this->generateInvoiceNumber();
            $customer = Customer::findOrFail($data['customer_id']);

            // Get sales account (default to Sales account 4100)
            $salesAccount = !empty($data['sales_account_id'])
                ? Account::findOrFail($data['sales_account_id'])
                : Account::where('code', '4100')->first();

            if (!$salesAccount) {
                throw new \Exception('Sales account not found');
            }

            // Get labour account if amount provided
            $labourAccount = null;
            $labourAmount = (float)($data['labour_amount'] ?? 0);
            if ($labourAmount > 0) {
                $labourAccount = !empty($data['labour_account_id'])
                    ? Account::find($data['labour_account_id'])
                    : PassiveIncomeItem::getLabourChargesAccount();
            }

            // Get transportation account if amount provided
            $transportationAccount = null;
            $transportationAmount = (float)($data['transportation_amount'] ?? 0);
            if ($transportationAmount > 0) {
                $transportationAccount = !empty($data['transportation_account_id'])
                    ? Account::find($data['transportation_account_id'])
                    : PassiveIncomeItem::getTransportationAccount();
            }

            // Calculate subtotal from items
            $subtotal = $this->calculateSubtotal($data['items'] ?? []);
            $discountAmount = (float)($data['discount_amount'] ?? 0);
            $taxAmount = (float)($data['tax_amount'] ?? 0);
            $roundOffAmount = (float)($data['round_off_amount'] ?? 0);

            // Calculate grand total
            $totalAmount = $subtotal - $discountAmount + $taxAmount + $labourAmount + $transportationAmount + $roundOffAmount;

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'sales_account_id' => $salesAccount->id,
                'customer_ledger_account_id' => $customer->ledger_account_id,
                'labour_account_id' => $labourAccount?->id,
                'transportation_account_id' => $transportationAccount?->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'labour_amount' => $labourAmount,
                'transportation_amount' => $transportationAmount,
                'round_off_amount' => $roundOffAmount,
                'total_amount' => $totalAmount,
                'total_paid' => 0,
                'delivery_status' => 'pending',
                'outstanding_at_creation' => $customer->current_balance ?? 0,
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create invoice items (products)
            $this->createInvoiceItems($invoice, $data['items'] ?? [], $customer);

            // Create passive income items if any
            $this->createPassiveIncomeItems($invoice, $data['passive_items'] ?? []);

            // Create accounting transaction
            $transaction = $this->createInvoiceTransaction(
                $invoice,
                $salesAccount,
                $labourAccount,
                $transportationAccount
            );

            // Update invoice with transaction reference
            $invoice->update(['transaction_id' => $transaction->id]);

            DB::commit();

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'total_amount' => $totalAmount,
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
     * Create invoice items with alternative quantity calculation
     */
    protected function createInvoiceItems(Invoice $invoice, array $items, Customer $customer): void
    {
        foreach ($items as $itemData) {
            $product = null;
            $altQtyDisplay = null;
            $altQtyBoxes = 0;
            $altQtyPieces = 0;
            $baseQuantity = (float)($itemData['quantity'] ?? 0);

            // If product selected, calculate alt qty breakdown
            if (!empty($itemData['product_id'])) {
                $product = Product::with(['baseUnit', 'alternativeUnits'])->find($itemData['product_id']);
                
                if ($product) {
                    // Convert to base unit if different unit selected
                    $selectedUnitId = $itemData['unit_id'] ?? $product->base_unit_id;
                    $baseQuantity = $this->unitConversionService->convertToBaseUnit(
                        (float)$itemData['quantity'],
                        (int)$selectedUnitId,
                        $product
                    );

                    // Calculate alternative unit breakdown (box + pcs)
                    $altQty = $this->unitConversionService->convertToAlternativeUnits($baseQuantity, $product);
                    $altQtyDisplay = $altQty['display'];
                    $altQtyBoxes = $altQty['boxes'];
                    $altQtyPieces = $altQty['pieces'];
                }
            }

            // Calculate line total
            $quantity = (float)($itemData['quantity'] ?? 0);
            $unitPrice = (float)($itemData['unit_price'] ?? 0);
            $discountPercent = (float)($itemData['discount_percent'] ?? 0);
            $lineSubtotal = $quantity * $unitPrice;
            $discountAmount = $discountPercent > 0 ? ($lineSubtotal * $discountPercent / 100) : 0;
            $lineTotal = $lineSubtotal - $discountAmount;

            $invoiceItem = $invoice->items()->create([
                'product_id' => $itemData['product_id'] ?? null,
                'item_type' => 'product',
                'description' => $itemData['description'] ?? $product?->name ?? 'Product',
                'unit_id' => $itemData['unit_id'] ?? $product?->base_unit_id,
                'quantity' => $quantity,
                'base_quantity' => $baseQuantity,
                'alt_qty_display' => $altQtyDisplay,
                'alt_qty_boxes' => $altQtyBoxes,
                'alt_qty_pieces' => $altQtyPieces,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'line_total' => $lineTotal,
                'rate_given_to_customer' => $unitPrice,
            ]);

            // Update customer price history
            if ($product) {
                CustomerPriceHistory::createFromInvoice($customer, $product, $quantity, $unitPrice);
            }
        }
    }

    /**
     * Create passive income items (labour, transportation as line items)
     */
    protected function createPassiveIncomeItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $itemData) {
            if (empty($itemData['amount']) || (float)$itemData['amount'] <= 0) {
                continue;
            }

            $invoice->items()->create([
                'item_type' => 'passive_income',
                'passive_account_id' => $itemData['account_id'] ?? null,
                'description' => $itemData['description'] ?? 'Service Charge',
                'quantity' => (float)($itemData['quantity'] ?? 1),
                'unit_price' => (float)$itemData['amount'],
                'line_total' => (float)$itemData['amount'] * (float)($itemData['quantity'] ?? 1),
            ]);
        }
    }

    /**
     * Create double-entry transaction for invoice
     * 
     * Debit: Customer Ledger (AR)
     * Credit: Sales Account (Revenue)
     * Credit: Labour Income Account (if applicable)
     * Credit: Transportation Income Account (if applicable)
     */
    protected function createInvoiceTransaction(
        Invoice $invoice,
        Account $salesAccount,
        ?Account $labourAccount,
        ?Account $transportationAccount
    ): Transaction {
        // Get AR account
        $arAccount = $invoice->customer->ledger_account_id
            ? Account::find($invoice->customer->ledger_account_id)
            : Account::where('code', '1210')->first();

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

        // DEBIT: Customer Ledger (full invoice amount)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $arAccount->id,
            'type' => 'debit',
            'amount' => $invoice->total_amount,
            'memo' => "Invoice {$invoice->invoice_number}",
        ]);

        // CREDIT: Sales Account (subtotal - discount + tax)
        $salesCredit = $invoice->subtotal - $invoice->discount_amount + $invoice->tax_amount + $invoice->round_off_amount;
        if ($salesCredit > 0) {
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $salesAccount->id,
                'type' => 'credit',
                'amount' => $salesCredit,
                'memo' => "Sales - Invoice {$invoice->invoice_number}",
            ]);
        }

        // CREDIT: Labour Income Account
        if ($labourAccount && $invoice->labour_amount > 0) {
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $labourAccount->id,
                'type' => 'credit',
                'amount' => $invoice->labour_amount,
                'memo' => "Labour Charges - Invoice {$invoice->invoice_number}",
            ]);
        }

        // CREDIT: Transportation Income Account
        if ($transportationAccount && $invoice->transportation_amount > 0) {
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $transportationAccount->id,
                'type' => 'credit',
                'amount' => $invoice->transportation_amount,
                'memo' => "Transportation Charges - Invoice {$invoice->invoice_number}",
            ]);
        }

        return $transaction;
    }

    /**
     * Calculate subtotal from items
     */
    protected function calculateSubtotal(array $items): float
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            $discountPercent = (float)($item['discount_percent'] ?? 0);
            
            $lineTotal = $qty * $price;
            if ($discountPercent > 0) {
                $lineTotal -= ($lineTotal * $discountPercent / 100);
            }
            $subtotal += $lineTotal;
        }
        return $subtotal;
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = now()->format('Y');
        
        $lastInvoice = Invoice::withTrashed()
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastInvoice
            ? (int)substr($lastInvoice->invoice_number, -5) + 1
            : 1;

        return $prefix . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Delete invoice and reverse transactions
     */
    public function deleteInvoice(Invoice $invoice, int $deletedBy): bool
    {
        // Delete related transaction entries
        if ($invoice->transaction_id) {
            TransactionEntry::where('transaction_id', $invoice->transaction_id)->delete();
            Transaction::find($invoice->transaction_id)?->delete();
        }

        $invoice->update(['deleted_by' => $deletedBy]);
        $invoice->delete();

        return true;
    }
}