<?php

namespace App\Services\Sales;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    /**
     * Create delivery (challan) for invoice
     * Adjusts inventory and creates accounting entries
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
            $deliveryAmount = 0;

            foreach ($data['items'] as $itemData) {
                $invoiceItem = InvoiceItem::find($itemData['invoice_item_id']);
                $deliveryQty = $itemData['delivered_quantity'];

                // Create delivery item record
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'delivered_quantity' => $deliveryQty,
                ]);

                // Update invoice item's delivered quantity
                $invoiceItem->delivered_quantity += $deliveryQty;
                $invoiceItem->save();

                // Update product stock if product exists
                if ($invoiceItem->product_id) {
                    $product = Product::find($invoiceItem->product_id);
                    if ($product) {
                        $product->decrementStock($deliveryQty, $invoiceItem->unit_id);
                    }
                }

                // Calculate delivery amount
                $deliveryAmount += ($invoiceItem->unit_price * $deliveryQty) - 
                                 (($invoiceItem->unit_price * $deliveryQty * $invoiceItem->discount_percent) / 100);
            }

            // Update invoice delivery status
            $invoice->updateDeliveryStatus();

            // Create accounting transaction for delivery
            // This records the Cost of Goods Sold and reduces Inventory
            $this->createDeliveryTransaction($delivery, $invoice, $deliveryAmount);

            DB::commit();

            Log::info('Delivery created', [
                'delivery_id' => $delivery->id,
                'challan_number' => $challanNumber,
                'invoice_id' => $invoice->id,
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
        $cogAccount = \App\Models\Account::where('code', '5100')->first(); // COGS - Tiles
        $inventoryAccount = \App\Models\Account::where('code', '1140')->first(); // Inventory - Tiles

        if (!$cogAccount || !$inventoryAccount) {
            Log::warning('COGS or Inventory account not found');
            return;
        }

        $transaction = Transaction::create([
            'date' => $delivery->delivery_date,
            'reference' => $delivery->challan_number,
            'description' => "Delivery #{$delivery->challan_number} - Invoice #{$invoice->invoice_number}",
            'status' => 'posted',
        ]);

        // Debit: COGS
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $cogAccount->id,
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

        $delivery->transaction_id = $transaction->id;
        $delivery->save();

        return $transaction;
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
                $invoiceItem->delivered_quantity -= $deliveryItem->delivered_quantity;
                $invoiceItem->save();

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

            Log::info('Delivery deleted', [
                'delivery_id' => $delivery->id,
                'challan_number' => $delivery->challan_number,
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
        return 'CH' . date('y') . $newNumber; // Format: CH250001, CH250002, etc.
    }
}
