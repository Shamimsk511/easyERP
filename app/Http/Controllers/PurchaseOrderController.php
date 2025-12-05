<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\ProductMovement;
use App\Models\TransactionEntry;
use Yajra\DataTables\DataTables;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderController extends Controller
{
public function index(Request $request)
{
    if ($request->ajax()) {
        $query = PurchaseOrder::with('vendor')->select('purchase_orders.*');
        
        // ADD THIS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        return DataTables::of($query)  // Change $data to $query
            ->addIndexColumn()
            ->addColumn('vendor_name', function ($row) {
                return $row->vendor ? $row->vendor->name : '-';
            })
            ->addColumn('order_date_formatted', function ($row) {
                // Fix: Check if it's already a string or convert from Carbon
                if (is_string($row->order_date)) {
                    return \Carbon\Carbon::parse($row->order_date)->format('d M Y');
                }
                return $row->order_date ? $row->order_date->format('d M Y') : '-';
            })
            ->addColumn('status_badge', function ($row) {
                if ($row->status === 'received') {
                    return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Received</span>';
                }
                return '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>';
            })
            ->addColumn('amount_formatted', function ($row) {
                return '৳ ' . number_format($row->total_amount, 2);
            })
            ->addColumn('action', function ($row) {
                $viewBtn = '<a href="' . route('purchase-orders.show', $row->id) . '" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>';
                $editBtn = '';
                $receiveBtn = '';
                $deleteBtn = '';
                
                // Only show edit and delete for pending orders
                if ($row->status === 'pending') {
                    $editBtn = '<a href="' . route('purchase-orders.edit', $row->id) . '" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>';
                    $deleteBtn = '<button type="button" class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                    
                    // Safe vendor name retrieval
                    $vendorName = $row->vendor ? e($row->vendor->name) : 'N/A';
                    
                    // Add "Mark as Received" button
                    $receiveBtn = '<button type="button" class="btn btn-success btn-sm receive-btn" data-id="' . $row->id . '" data-vendor="' . $vendorName . '" data-amount="' . number_format($row->total_amount, 2) . '" title="Mark as Received"><i class="fas fa-check-circle"></i> Receive</button>';
                }
                
                return '<div class="btn-group">' . $viewBtn . ' ' . $receiveBtn . ' ' . $editBtn . ' ' . $deleteBtn . '</div>';
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    return view('purchase_orders.index');
}



 public function create(Request $request)
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        // Get expense accounts for purchase account selection
        $purchaseAccounts = Account::where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        
        // Get default purchase account (code 5100 or first expense account)
        $defaultPurchaseAccount = Account::where('code', '5100')->first() 
            ?? $purchaseAccounts->first();
        
        return view('purchase_orders.create', compact('vendors', 'products', 'purchaseAccounts', 'defaultPurchaseAccount'));
    }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'vendor_id' => 'required|exists:vendors,id',
    //         'purchase_account_id' => 'required|exists:accounts,id',
    //         'order_date' => 'required|date',
    //         'items.*.product_id' => 'required|exists:products,id',
    //         'items.*.quantity' => 'required|numeric|min:0.001',
    //         'items.*.rate' => 'required|numeric|min:0.01',
    //         'notes' => 'nullable|string',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // Create purchase order
    //         $order = PurchaseOrder::create([
    //             'vendor_id' => $validated['vendor_id'],
    //             'purchase_account_id' => $validated['purchase_account_id'],
    //             'order_number' => 'PO-' . now()->format('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT),
    //             'order_date' => $validated['order_date'],
    //             'status' => 'pending',
    //             'notes' => $validated['notes'] ?? null,
    //             'total_amount' => collect($validated['items'])->sum(function($item) {
    //                 return $item['quantity'] * $item['rate'];
    //             }),
    //         ]);

    //         // Create order items
    //         foreach ($validated['items'] as $item) {
    //             PurchaseOrderItem::create([
    //                 'purchase_order_id' => $order->id,
    //                 'product_id' => $item['product_id'],
    //                 'quantity' => $item['quantity'],
    //                 'rate' => $item['rate'],
    //                 'amount' => $item['quantity'] * $item['rate'],
    //             ]);
    //         }

    //         DB::commit();
    //         return response()->json(['success' => true, 'data' => $order]);
            
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase order creation failed: ' . $e->getMessage());
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }


    /**
     * Mark purchase order as received and create accounting entries
     */
public function markAsReceived(PurchaseOrder $order)
    {
        if ($order->status == 'received') {
            return response()->json([
                'success' => false,
                'error' => 'Already marked as received',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update order status
            $order->status = 'received';
            $order->received_date = now();
            $order->save();

            // 1. Update stock for all items AND log movements
            foreach ($order->items as $item) {
                $product = $item->product;

                // Get current stock before update (handle null)
                $stockBefore = $product->currentstock ?? 0;

                // Item quantity is stored in base unit already
                // If it was recorded with alternative unit, conversion happened at entry time
                $quantityInBase = $item->quantity;

                // Calculate new stock
                $newStock = $stockBefore + $quantityInBase;

                // Update product stock
                $product->update(['currentstock' => $newStock]);

                // Log the movement with conversion info
                $conversionNote = "Purchase from {$order->vendor->name} - Order #{$order->order_number}";

                ProductMovement::create([
                    'product_id' => $product->id,
                    'type' => 'purchase',
                    'reference_type' => 'PurchaseOrder',
                    'reference_id' => $order->id,
                    'quantity' => $quantityInBase,
                    'rate' => $item->rate,
                    'stock_before' => $stockBefore,
                    'stock_after' => $newStock,
                    'movement_date' => $order->received_date ?? now(),
                    'notes' => $conversionNote,
                    'created_by' => auth()->id(),
                ]);

                Log::info("Stock updated for product {$product->name}", [
                    'before' => $stockBefore,
                    'added' => $quantityInBase,
                    'after' => $newStock,
                ]);
            }

            // 2. Create double-entry accounting transaction
            $vendor = $order->vendor;
            if (!$vendor->ledger_account_id) {
                throw new \Exception('Vendor does not have a ledger account. Please update vendor settings.');
            }

            // Use the selected purchase account from the order
            $purchaseAccount = $order->purchaseAccount;
            if (!$purchaseAccount) {
                throw new \Exception('Purchase account not found for this order.');
            }

            // Create transaction
            $transaction = Transaction::create([
                'date' => $order->received_date ?? now(),
                'type' => 'purchase',
                'reference' => $order->order_number,
                'description' => "Purchase from {$vendor->name} - {$order->order_number}",
                'notes' => $order->notes,
                'status' => 'posted',
            ]);

            // Dr. Purchase Account (Expense increases)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $purchaseAccount->id,
                'amount' => $order->total_amount,
                'type' => 'debit',
                'memo' => "Purchase - {$order->order_number}",
            ]);

            // Cr. Vendor Account (Liability increases - we owe vendor)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $vendor->ledger_account_id,
                'amount' => $order->total_amount,
                'type' => 'credit',
                'memo' => "Payable to {$vendor->name}",
            ]);

            // Link transaction to purchase order
            $order->transaction_id = $transaction->id;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order marked as received successfully! Stock updated and accounting entry created.',
                'data' => $order->fresh('vendor', 'items.product', 'transaction'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark as received failed', [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store purchase order with alternative unit conversion
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_account_id' => 'required|exists:accounts,id',
            'order_date' => 'required|date',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Process quantities - convert to base unit if alternative unit provided
            $processedItems = [];
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::with('alternativeUnits', 'baseUnit')
                    ->findOrFail($item['product_id']);

                $quantityInBase = $item['quantity'];
                $unitId = $item['unit_id'] ?? $product->base_unit_id;

                // If alternative unit provided, convert to base unit
                if ($unitId != $product->base_unit_id) {
                    $altUnit = $product->alternativeUnits()
                        ->where('unit_id', $unitId)
                        ->first();

                    if (!$altUnit) {
                        throw new \Exception("Unit not configured for product {$product->name}");
                    }

                    $conversionFactor = $altUnit->pivot->conversion_factor;
                    $quantityInBase = $item['quantity'] * $conversionFactor;
                }

                $amount = $quantityInBase * $item['rate'];
                $totalAmount += $amount;

                $processedItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $quantityInBase, // Store in base unit
                    'rate' => $item['rate'],
                    'amount' => $amount,
                ];
            }

            // Create purchase order
            $order = PurchaseOrder::create([
                'vendor_id' => $validated['vendor_id'],
                'purchase_account_id' => $validated['purchase_account_id'],
                'order_number' => 'PO-' . now()->format('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT),
                'order_date' => $validated['order_date'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totalAmount,
            ]);

            // Create order items
            foreach ($processedItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase order creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function destroy(PurchaseOrder $purchaseOrder)
    {
        try {
            // Only allow deletion of pending orders
            if ($purchaseOrder->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending purchase orders can be deleted. This order has already been received.'
                ], 422);
            }

            DB::beginTransaction();
            
            // Delete items first
            $purchaseOrder->items()->delete();
            
            // Delete the order
            $purchaseOrder->delete();
            
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Purchase order deleted successfully.']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error deleting purchase order: ' . $e->getMessage()], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('vendor', 'items.product');
        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('vendor', 'items.product');
        $vendors = Vendor::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        
        return view('purchase_orders.edit', compact('purchaseOrder', 'vendors', 'products'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.rate' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update purchase order
            $purchaseOrder->update([
                'vendor_id' => $validated['vendor_id'],
                'order_date' => $validated['order_date'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => collect($validated['items'])->sum(function($item) {
                    return $item['quantity'] * $item['rate'];
                }),
            ]);

            // Delete old items
            $purchaseOrder->items()->delete();

            // Create new items
            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'amount' => $item['quantity'] * $item['rate'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $purchaseOrder]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
