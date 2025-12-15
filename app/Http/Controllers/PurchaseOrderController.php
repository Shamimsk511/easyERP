<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Product;
use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\ProductMovement;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    /**
     * Display listing with DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseOrder::with(['vendor', 'items', 'purchaseAccount'])
                ->select('purchase_orders.*');

            return DataTables::eloquent($query)
                ->addColumn('vendor_name', fn($po) => $po->vendor->name ?? 'N/A')
                ->addColumn('items_count', fn($po) => $po->items->count() . ' items')
                ->addColumn('status_badge', function ($po) {
                    $badges = [
                        'pending' => '<span class="badge badge-warning">Pending</span>',
                        'received' => '<span class="badge badge-success">Received</span>',
                    ];
                    return $badges[$po->status] ?? '<span class="badge badge-secondary">Unknown</span>';
                })
                ->addColumn('action', function ($po) {
                    return view('purchase-orders.partials.actions', ['order' => $po])->render();
                })
                ->editColumn('order_date', fn($po) => $po->order_date->format('d M Y'))
                ->editColumn('total_amount', fn($po) => '৳ ' . number_format($po->total_amount, 2))
                ->filterColumn('vendor_name', function ($query, $keyword) {
                    $query->whereHas('vendor', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('purchase-orders.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        $vendors = Vendor::active()->orderBy('name')->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->orderBy('name')->get();
        $purchaseAccounts = Account::where('type', 'expense')
            ->where(fn($q) => $q->where('code', 'like', '5%')->orWhere('name', 'like', '%purchase%'))
            ->active()
            ->get();

        return view('purchase-orders.create', compact('vendors', 'products', 'purchaseAccounts'));
    }

    /**
     * Store new purchase order
     * Fixed: Using StorePurchaseOrderRequest
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Generate order number
            $orderNumber = PurchaseOrder::generateOrderNumber();

            // Calculate total
            $totalAmount = collect($validated['items'])->sum('amount');

            // Create purchase order
            $order = PurchaseOrder::create([
                'vendor_id' => $validated['vendor_id'],
                'purchase_account_id' => $validated['purchase_account_id'] ?? null,
                'order_number' => $orderNumber,
                'order_date' => $validated['order_date'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totalAmount,
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'rate' => $item['rate'],
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            Log::info('Purchase order created', [
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'vendor_id' => $order->vendor_id,
                'total' => $totalAmount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully!',
                'data' => $order->load(['vendor', 'items.product']),
                'redirect_url' => route('purchase-orders.show', $order->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase order creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show purchase order details
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'vendor',
            'items.product.baseUnit',
            'items.unit',
            'purchaseAccount',
            'transaction.entries.account',
        ]);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    /**
     * Show edit form
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Cannot edit a received purchase order.');
        }

        $purchaseOrder->load(['items.product', 'items.unit']);
        $vendors = Vendor::active()->orderBy('name')->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->orderBy('name')->get();
        $purchaseAccounts = Account::where('type', 'expense')->active()->get();

        return view('purchase-orders.edit', [
            'order' => $purchaseOrder,
            'vendors' => $vendors,
            'products' => $products,
            'purchaseAccounts' => $purchaseAccounts,
        ]);
    }

    /**
     * Update purchase order
     * Fixed: Using UpdatePurchaseOrderRequest
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Update order
            $purchaseOrder->update([
                'vendor_id' => $validated['vendor_id'],
                'purchase_account_id' => $validated['purchase_account_id'] ?? null,
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $validated['total_amount'],
            ]);

            // Delete old items
            $purchaseOrder->items()->delete();

            // Create new items
            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'rate' => $item['rate'],
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully!',
                'data' => $purchaseOrder->fresh(['vendor', 'items.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase order update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete purchase order
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a received purchase order.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark purchase order as received
     * - Updates stock for all items
     * - Creates product movements
     * - Creates double-entry accounting transaction
     */
    public function markAsReceived(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'This purchase order has already been received.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update PO status
            $purchaseOrder->update([
                'status' => 'received',
                'received_date' => now(),
            ]);

            // Process each item
            foreach ($purchaseOrder->items as $item) {
                $product = $item->product;
                $stockBefore = (float) ($product->current_stock ?? 0);
                $quantityReceived = (float) $item->quantity;
                $stockAfter = $stockBefore + $quantityReceived;

                // Update product stock
                $product->update(['current_stock' => $stockAfter]);

                // Update item received quantity
                $item->update(['received_quantity' => $quantityReceived]);

                // Create product movement
                ProductMovement::create([
                    'product_id' => $product->id,
                    'type' => 'purchase',
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $purchaseOrder->id,
                    'quantity' => $quantityReceived,
                    'rate' => $item->rate,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'movement_date' => $purchaseOrder->received_date ?? now(),
                    'notes' => "Purchase from {$purchaseOrder->vendor->name} - Order #{$purchaseOrder->order_number}",
                    'created_by' => auth()->id(),
                ]);

                Log::info("Stock updated for product", [
                    'product' => $product->name,
                    'before' => $stockBefore,
                    'added' => $quantityReceived,
                    'after' => $stockAfter,
                ]);
            }

            // Create accounting transaction
            $transaction = $this->createPurchaseTransaction($purchaseOrder);

            // Link transaction to PO
            $purchaseOrder->update(['transaction_id' => $transaction->id]);

            DB::commit();

            Log::info('Purchase order received', [
                'order_id' => $purchaseOrder->id,
                'order_number' => $purchaseOrder->order_number,
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order marked as received! Stock updated and accounting entry created.',
                'data' => $purchaseOrder->fresh(['vendor', 'items.product', 'transaction']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark as received failed', [
                'order_id' => $purchaseOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error receiving purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create double-entry accounting transaction for purchase
     * Debit: Purchase Account (Expense) or Inventory Account (Asset)
     * Credit: Vendor Account (Liability - we owe them)
     */
    private function createPurchaseTransaction(PurchaseOrder $order): Transaction
    {
        $vendor = $order->vendor;

        if (!$vendor->ledger_account_id) {
            throw new \Exception('Vendor does not have a ledger account.');
        }

        // Get purchase account
        $purchaseAccount = $order->purchaseAccount
            ?? Account::where('type', 'expense')->where('code', 'like', '5100%')->first()
            ?? Account::where('type', 'expense')->first();

        if (!$purchaseAccount) {
            throw new \Exception('Purchase expense account not found.');
        }

        $transaction = Transaction::create([
            'date' => $order->received_date ?? now(),
            'type' => 'purchase',
            'reference' => $order->order_number,
            'description' => "Purchase from {$vendor->name} - {$order->order_number}",
            'notes' => $order->notes,
            'status' => 'posted',
            'source_type' => PurchaseOrder::class,
            'source_id' => $order->id,
        ]);

        // Debit: Purchase Account (Expense increases)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $purchaseAccount->id,
            'type' => 'debit',
            'amount' => $order->total_amount,
            'memo' => "Purchase - {$order->order_number}",
        ]);

        // Credit: Vendor Account (Liability increases - we owe vendor)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $vendor->ledger_account_id,
            'type' => 'credit',
            'amount' => $order->total_amount,
            'memo' => "Payable to {$vendor->name}",
        ]);

        return $transaction;
    }

    /**
     * Print purchase order
     */
    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.product.baseUnit', 'items.unit']);

        return view('purchase-orders.print', ['order' => $purchaseOrder]);
    }

    /**
     * Get vendor purchase history (AJAX)
     */
    public function getVendorHistory(Vendor $vendor): JsonResponse
    {
        $orders = $vendor->purchaseOrders()
            ->with('items')
            ->orderBy('order_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date->format('d M Y'),
                'status' => $order->status,
                'total' => number_format($order->total_amount, 2),
                'items_count' => $order->items->count(),
            ]);

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'total_orders' => $vendor->purchaseOrders()->count(),
            'total_amount' => number_format($vendor->total_purchases, 2),
        ]);
    }
}