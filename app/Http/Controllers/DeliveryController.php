<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Sales\DeliveryService;

class DeliveryController extends Controller
{
    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Show delivery creation modal data
     */
    public function create(Request $request)
    {
        $invoiceId = $request->get('invoice_id');
        $invoice = Invoice::with('items')->findOrFail($invoiceId);

        // Get undelivered items
        $items = $invoice->items()
            ->where('delivered_quantity', '<', DB::raw('quantity'))
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'delivered_quantity' => $item->delivered_quantity,
                    'remaining' => $item->quantity - $item->delivered_quantity,
                    'unit' => $item->unit ? $item->unit->symbol : '',
                    'unit_price' => $item->unit_price,
                ];
            });

        $users = User::where('is_active', true)->get();

        return response()->json([
            'invoice' => $invoice->toArray(),
            'items' => $items,
            'users' => $users,
        ]);
    }

    /**
     * Store delivery
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'delivery_date' => 'required|date',
            'delivery_method' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'delivered_by_user_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'items.*.delivered_quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        try {
            $invoice = Invoice::find($validated['invoice_id']);
            $delivery = $this->deliveryService->createDelivery($invoice, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Delivery created successfully!',
                'delivery' => $delivery,
                'challan_number' => $delivery->challan_number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete delivery
     */
    public function destroy(Delivery $delivery)
    {
        try {
            $this->deliveryService->deleteDelivery($delivery);

            return response()->json([
                'success' => true,
                'message' => 'Delivery deleted and reversed successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print delivery challan
     */
    public function print(Delivery $delivery)
    {
        $delivery->load(['invoice.customer', 'items.invoiceItem']);

        return view('sales.delivery.print', compact('delivery'));
    }
}
