<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Delivery;
use App\Models\Transaction;
use App\Services\Sales\DeliveryService;
use App\Http\Requests\Sales\StoreDeliveryRequest;
use App\Observers\TransactionObserver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DeliveryController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService
    ) {}

    /**
     * Display listing with DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Delivery::with(['invoice.customer', 'deliveredBy'])
                ->select('deliveries.*');

            return DataTables::eloquent($query)
                ->addColumn('invoice_number', fn($delivery) => $delivery->invoice->invoice_number ?? 'N/A')
                ->addColumn('customer_name', fn($delivery) => $delivery->invoice->customer->name ?? 'N/A')
                ->addColumn('customer_phone', fn($delivery) => $delivery->invoice->customer->phone ?? 'N/A')
                ->addColumn('delivered_by_name', fn($delivery) => $delivery->deliveredBy->name ?? 'N/A')
                ->addColumn('total_items', fn($delivery) => $delivery->items->count())
                ->addColumn('action', function ($delivery) {
                    $viewBtn = '<a href="' . route('deliveries.show', $delivery->id) . '" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>';
                    $printBtn = '<a href="' . route('deliveries.print', $delivery->id) . '" class="btn btn-sm btn-secondary" target="_blank" title="Print Challan"><i class="fas fa-print"></i></a>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-delivery-btn" data-id="' . $delivery->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                    return '<div class="btn-group">' . $viewBtn . $printBtn . $deleteBtn . '</div>';
                })
                ->editColumn('delivery_date', fn($delivery) => $delivery->delivery_date->format('d M Y'))
                ->editColumn('delivery_method', fn($delivery) => ucfirst($delivery->delivery_method))
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereHas('invoice.customer', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->filterColumn('invoice_number', function ($query, $keyword) {
                    $query->whereHas('invoice', fn($q) => $q->where('invoice_number', 'like', "%{$keyword}%"));
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('deliveries.index');
    }

    /**
     * Show create delivery form
     */
    public function create(Request $request)
    {
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with(['customer', 'items.product.baseUnit', 'items.unit'])
                ->findOrFail($request->invoice_id);

            // Check if fully delivered
            if ($invoice->delivery_status === 'delivered') {
                return redirect()->route('sales.show', $invoice)
                    ->with('warning', 'Invoice is already fully delivered');
            }
        }

        // Get invoices with pending deliveries
        $invoices = Invoice::with('customer')
            ->whereIn('delivery_status', ['pending', 'partial'])
            ->orderBy('invoice_date', 'desc')
            ->get();

        $users = \App\Models\User::where('is_active', true)->get();

        return view('deliveries.create', compact('invoice', 'invoices', 'users'));
    }

    /**
     * Store delivery
     * Fixed: Using StoreDeliveryRequest for validation
     */
    public function store(StoreDeliveryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($request->invoice_id);

            // Validate invoice is not fully delivered
            if ($invoice->delivery_status === 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice is already fully delivered',
                ], 422);
            }

            $delivery = $this->deliveryService->createDelivery($invoice, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery created successfully',
                'delivery' => [
                    'id' => $delivery->id,
                    'challan_number' => $delivery->challan_number,
                    'delivery_date' => $delivery->delivery_date->format('d M Y'),
                ],
                'invoice' => [
                    'id' => $invoice->id,
                    'delivery_status' => $invoice->fresh()->delivery_status,
                ],
                'redirect_url' => route('deliveries.show', $delivery->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delivery creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show delivery details
     */
    public function show(Delivery $delivery)
    {
        $delivery->load([
            'invoice.customer',
            'items.invoiceItem.product.baseUnit',
            'items.invoiceItem.unit',
            'deliveredBy',
            'transaction.entries.account',
        ]);

        return view('deliveries.show', compact('delivery'));
    }

    /**
     * Delete delivery (reverse stock and transactions)
     */
    public function destroy(Delivery $delivery): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = $delivery->invoice;

            // Delete customer ledger entry if exists
            if ($delivery->transaction_id) {
                $transaction = Transaction::find($delivery->transaction_id);
                if ($transaction) {
                    TransactionObserver::deleteCustomerLedger($transaction);
                }
            }

            $this->deliveryService->deleteDelivery($delivery);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery deleted and stock restored successfully',
                'invoice' => [
                    'id' => $invoice->id,
                    'delivery_status' => $invoice->fresh()->delivery_status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delivery deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print delivery challan (no prices shown)
     */
    public function print(Delivery $delivery)
    {
        $delivery->load([
            'invoice.customer',
            'items.invoiceItem.product.baseUnit',
            'items.invoiceItem.unit',
            'deliveredBy',
        ]);

        return view('deliveries.print', compact('delivery'));
    }

    /**
     * Get pending items for invoice (AJAX)
     */
    public function getPendingItems(Invoice $invoice): JsonResponse
    {
        $invoice->load(['items.product.baseUnit', 'items.unit']);

        $pendingItems = $invoice->items
            ->filter(fn($item) => $item->remaining_quantity > 0)
            ->map(fn($item) => [
                'id' => $item->id,
                'product_name' => $item->product?->name ?? $item->description,
                'product_code' => $item->product?->code,
                'unit' => $item->unit?->symbol ?? $item->product?->baseUnit?->symbol ?? 'Unit',
                'ordered_quantity' => (float) $item->quantity,
                'delivered_quantity' => (float) $item->delivered_quantity,
                'remaining_quantity' => (float) $item->remaining_quantity,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'items' => $pendingItems,
            'customer' => [
                'name' => $invoice->customer->name,
                'phone' => $invoice->customer->phone,
                'address' => $invoice->customer->address,
                'city' => $invoice->customer->city,
            ],
        ]);
    }

    /**
     * Quick delivery - deliver all remaining items
     */
    public function quickDeliver(Invoice $invoice): JsonResponse
    {
        if ($invoice->delivery_status === 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already fully delivered',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Build delivery data with all remaining quantities
            $items = $invoice->items
                ->filter(fn($item) => $item->remaining_quantity > 0)
                ->map(fn($item) => [
                    'invoice_item_id' => $item->id,
                    'delivered_quantity' => $item->remaining_quantity,
                ])
                ->values()
                ->toArray();

            $deliveryData = [
                'delivery_date' => now()->toDateString(),
                'delivery_method' => 'auto',
                'delivered_by_user_id' => auth()->id(),
                'notes' => 'Quick delivery - all items',
                'items' => $items,
            ];

            $delivery = $this->deliveryService->createDelivery($invoice, $deliveryData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All items delivered successfully',
                'delivery' => [
                    'id' => $delivery->id,
                    'challan_number' => $delivery->challan_number,
                ],
                'redirect_url' => route('deliveries.show', $delivery->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quick delivery failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver: ' . $e->getMessage(),
            ], 500);
        }
    }
}