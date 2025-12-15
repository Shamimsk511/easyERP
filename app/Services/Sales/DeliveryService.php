<?php

namespace App\Services\Sales;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    /**
     * Create delivery (challan) for invoice
     * Adjusts inventory and creates accounting entries (COGS)
     */
    public function createDelivery(Invoice $invoice, array $data)
    {
        DB::beginTransaction();
        try {
            $challanNumber = $this->generateChallanNumber();

            // Create delivery record
            $delivery = Delivery::create([
                'challan_number' => $challanNumber,
                'invoice_id' => $invoice->id,
                'delivery_date' => $data['delivery_date'] ?? now()->toDateString(),
                'delivery_method' => $data['delivery_method'] ?? 'auto',
                'driver_name' => $data['driver_name'] ?? null,
                'delivered_by_user_id' => $data['delivered_by_user_id'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Process delivery items
            $totalDeliveryAmount = 0;

            foreach (($data['items'] ?? []) as $itemData) {
                $invoiceItem = InvoiceItem::findOrFail($itemData['invoice_item_id']);
                $deliveryQty = (float)$itemData['delivered_quantity'];

                // Validate delivery quantity
                $remaining = $invoiceItem->quantity - $invoiceItem->delivered_quantity;
                if ($deliveryQty > $remaining) {
                    throw new \Exception("Cannot deliver more than {$remaining} units");
                }

                // Create delivery item record
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'delivered_quantity' => $deliveryQty,
                ]);

                // Update invoice item delivered quantity
                $invoiceItem->recordDelivery($deliveryQty);

                // Update product stock if product exists
                if ($invoiceItem->product_id) {
                    $product = Product::find($invoiceItem->product_id);
                    if ($product) {
                        $product->decrementStock($deliveryQty, $invoiceItem->unit_id);
                    }
                }

                // Calculate delivery amount
                $itemAmount = ($invoiceItem->unit_price * $deliveryQty) - 
                             (($invoiceItem->unit_price * $deliveryQty * $invoiceItem->discount_percent) / 100);
                $totalDeliveryAmount += $itemAmount;
            }

            // Update invoice delivery status
            $invoice->updateDeliveryStatus();

            // Create accounting transaction (COGS)
            $transaction = $this->createDeliveryTransaction($delivery, $invoice, $totalDeliveryAmount);

            // Update delivery with transaction
            $delivery->update(['transaction_id' => $transaction->id]);

            DB::commit();

            Log::info('Delivery created successfully', [
                'delivery_id' => $delivery->id,
                'challan_number' => $challanNumber,
                'invoice_id' => $invoice->id,
                'amount' => $totalDeliveryAmount,
                'transaction_id' => $transaction->id,
            ]);

            return $delivery;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delivery creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create double-entry accounting for delivery
     * Debit: COGS Account
     * Credit: Inventory Account
     */
    private function createDeliveryTransaction(Delivery $delivery, Invoice $invoice, $deliveryAmount)
    {
        // Get COGS and Inventory accounts
        $cogsAccount = Account::where('type', 'expense')->where('code', '5100')->first();
        $inventoryAccount = Account::where('type', 'asset')->where('code', '1140')->first();

        if (!$cogsAccount || !$inventoryAccount) {
            Log::warning('COGS or Inventory account not found', [
                'cogs_account' => $cogsAccount?->code,
                'inventory_account' => $inventoryAccount?->code,
            ]);
            // Create dummy transaction if accounts don't exist
            return $this->createDummyTransaction($delivery);
        }

        $transaction = Transaction::create([
            'date' => $delivery->delivery_date,
            'reference' => $delivery->challan_number,
            'description' => "Delivery #{$delivery->challan_number} - Invoice #{$invoice->invoice_number}",
            'status' => 'posted',
            'source_type' => Delivery::class,
            'source_id' => $delivery->id,
        ]);

        // Debit: COGS
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $cogsAccount->id,
            'type' => 'debit',
            'amount' => $deliveryAmount,
            'memo' => "Delivery {$delivery->challan_number}",
        ]);

        // Credit: Inventory
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $inventoryAccount->id,
            'type' => 'credit',
            'amount' => $deliveryAmount,
            'memo' => "Delivery {$delivery->challan_number}",
        ]);

        return $transaction;
    }

    /**
     * Create dummy transaction if accounts don't exist
     */
    private function createDummyTransaction(Delivery $delivery)
    {
        return Transaction::create([
            'date' => $delivery->delivery_date,
            'reference' => $delivery->challan_number,
            'description' => "Delivery #{$delivery->challan_number}",
            'status' => 'draft',
            'source_type' => Delivery::class,
            'source_id' => $delivery->id,
        ]);
    }

    /**
     * Delete delivery (revert stock and transaction)
     */
    public function deleteDelivery(Delivery $delivery)
    {
        DB::beginTransaction();
        try {
            // Revert stock
            foreach ($delivery->items as $deliveryItem) {
                $invoiceItem = $deliveryItem->invoiceItem;
                
                // Revert delivered quantity
                $invoiceItem->reverseDelivery($deliveryItem->delivered_quantity);

                // Restore product stock
                if ($invoiceItem->product_id) {
                    $product = Product::find($invoiceItem->product_id);
                    if ($product) {
                        $product->incrementStock($deliveryItem->delivered_quantity, $invoiceItem->unit_id);
                    }
                }
            }

            // Revert accounting transaction
            if ($delivery->transaction_id) {
                $transaction = Transaction::find($delivery->transaction_id);
                if ($transaction) {
                    $transaction->update(['status' => 'voided']);
                    $transaction->entries()->delete();
                }
            }

            // Update invoice delivery status
            $delivery->invoice->updateDeliveryStatus();

            // Soft delete delivery
            $delivery->delete();

            DB::commit();

            Log::info('Delivery deleted successfully', [
                'delivery_id' => $delivery->id,
                'challan_number' => $delivery->challan_number,
                'invoice_id' => $delivery->invoice_id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delivery deletion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique challan number
     */
    public function generateChallanNumber()
    {
        $lastDelivery = Delivery::withTrashed()->latest('id')->first();
        $lastNumber = $lastDelivery 
            ? (int)substr($lastDelivery->challan_number, -4)
            : 0;

        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        return 'CH' . date('y') . $newNumber;
    }
}
