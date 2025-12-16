<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\PassiveIncomeItem;
use App\Services\Sales\InvoiceService;
use App\Services\UnitConversionService;
use App\Http\Requests\Sales\StoreInvoiceRequest;
use App\Http\Requests\Sales\UpdateInvoiceRequest;
use App\Observers\TransactionObserver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SalesController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected UnitConversionService $unitConversionService
    ) {}

    /**
     * Display listing with DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Invoice::with(['customer', 'items', 'payments', 'deliveries'])
                ->select('invoices.*');

            return DataTables::eloquent($query)
                ->addColumn('customer_name', fn($inv) => $inv->customer->name ?? 'N/A')
                ->addColumn('customer_phone', fn($inv) => $inv->customer->phone ?? 'N/A')
                ->addColumn('total_paid', fn($inv) => number_format($inv->total_paid, 2))
                ->addColumn('outstanding_balance', fn($inv) => number_format($inv->outstanding_balance, 2))
                ->addColumn('additional_charges', function ($inv) {
                    $charges = [];
                    if ($inv->labour_amount > 0) $charges[] = 'L: ' . number_format($inv->labour_amount, 0);
                    if ($inv->transportation_amount > 0) $charges[] = 'T: ' . number_format($inv->transportation_amount, 0);
                    return implode(' | ', $charges) ?: '-';
                })
                ->addColumn('delivery_status_badge', function ($inv) {
                    $badges = [
                        'pending' => '<span class="badge badge-warning">Pending</span>',
                        'partial' => '<span class="badge badge-info">Partial</span>',
                        'delivered' => '<span class="badge badge-success">Delivered</span>',
                    ];
                    return $badges[$inv->delivery_status] ?? '<span class="badge badge-secondary">Unknown</span>';
                })
                ->addColumn('action', fn($inv) => view('sales.partials.actions', ['invoice' => $inv])->render())
                ->editColumn('invoice_date', fn($inv) => $inv->invoice_date->format('d M Y'))
                ->editColumn('total_amount', fn($inv) => number_format($inv->total_amount, 2))
                ->filterColumn('customer_name', fn($q, $kw) => $q->whereHas('customer', fn($c) => $c->where('name', 'like', "%{$kw}%")))
                ->filterColumn('customer_phone', fn($q, $kw) => $q->whereHas('customer', fn($c) => $c->where('phone', 'like', "%{$kw}%")))
                ->rawColumns(['action', 'delivery_status_badge'])
                ->make(true);
        }

        return view('sales.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        $customers = Customer::active()->orderBy('name')->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->orderBy('name')->get();
        
        // Tally-like account selection
        $salesAccounts = Account::where('type', 'income')
            ->where('code', 'like', '4%')
            ->active()
            ->orderBy('code')
            ->get();
        
        $labourAccounts = Account::where('type', 'income')
            ->active()
            ->where(function ($q) {
                $q->where('name', 'like', '%labour%')
                  ->orWhere('name', 'like', '%labor%')
                  ->orWhere('code', '4510');
            })
            ->orderBy('code')
            ->get();

        $transportationAccounts = Account::where('type', 'income')
            ->active()
            ->where(function ($q) {
                $q->where('name', 'like', '%transport%')
                  ->orWhere('name', 'like', '%delivery%')
                  ->orWhere('name', 'like', '%freight%')
                  ->orWhere('code', '4520');
            })
            ->orderBy('code')
            ->get();

        // If no specific accounts found, get all income accounts
        if ($labourAccounts->isEmpty()) {
            $labourAccounts = Account::where('type', 'income')->active()->orderBy('code')->get();
        }
        if ($transportationAccounts->isEmpty()) {
            $transportationAccounts = Account::where('type', 'income')->active()->orderBy('code')->get();
        }

        $passiveIncomeItems = PassiveIncomeItem::active()->with('account')->get();
        $cashAccounts = Account::whereIn('code', ['1110', '1120'])->active()->get();

        return view('sales.create', compact(
            'customers',
            'products',
            'salesAccounts',
            'labourAccounts',
            'transportationAccounts',
            'passiveIncomeItems',
            'cashAccounts'
        ));
    }

    /**
     * Store new invoice
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = $this->invoiceService->createInvoice($request->validated());

            // Sync to customer ledger
            if ($invoice->transaction_id) {
                $transaction = Transaction::find($invoice->transaction_id);
                if ($transaction) {
                    TransactionObserver::syncToCustomerLedger($transaction);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'redirect_url' => route('sales.show', $invoice->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show invoice details
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items.product.baseUnit',
            'items.product.alternativeUnits',
            'items.unit',
            'items.passiveAccount',
            'deliveries.items.invoiceItem',
            'payments.account',
            'salesAccount',
            'labourAccount',
            'transportationAccount',
            'customerLedgerAccount',
            'transaction.entries.account',
        ]);

        return view('sales.show', compact('invoice'));
    }

    /**
     * Show edit form
     */
    public function edit(Invoice $invoice)
    {
        if ($invoice->delivery_status !== 'pending') {
            return redirect()->route('sales.show', $invoice)
                ->with('warning', 'Cannot edit invoice with deliveries. Create a credit note instead.');
        }

        $invoice->load(['customer', 'items.product.baseUnit', 'items.product.alternativeUnits', 'items.unit']);
        
        $customers = Customer::active()->orderBy('name')->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->orderBy('name')->get();
        $salesAccounts = Account::where('type', 'income')->where('code', 'like', '4%')->active()->orderBy('code')->get();
        $labourAccounts = Account::where('type', 'income')->active()->orderBy('code')->get();
        $transportationAccounts = Account::where('type', 'income')->active()->orderBy('code')->get();
        $passiveIncomeItems = PassiveIncomeItem::active()->with('account')->get();

        return view('sales.edit', compact(
            'invoice',
            'customers',
            'products',
            'salesAccounts',
            'labourAccounts',
            'transportationAccounts',
            'passiveIncomeItems'
        ));
    }

    /**
     * Update invoice
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->delivery_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit invoice with deliveries',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete old transaction entries
            if ($invoice->transaction_id) {
                $oldTransaction = Transaction::find($invoice->transaction_id);
                if ($oldTransaction) {
                    TransactionObserver::deleteCustomerLedger($oldTransaction);
                }
            }

            $this->invoiceService->deleteInvoice($invoice, auth()->id());
            $newInvoice = $this->invoiceService->createInvoice($request->validated());

            if ($newInvoice->transaction_id) {
                $transaction = Transaction::find($newInvoice->transaction_id);
                if ($transaction) {
                    TransactionObserver::syncToCustomerLedger($transaction);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'invoice_id' => $newInvoice->id,
                'redirect_url' => route('sales.show', $newInvoice->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete invoice
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->delivery_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete invoice with deliveries.',
            ], 422);
        }

        if ($invoice->payments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete invoice with payments.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($invoice->transaction_id) {
                $transaction = Transaction::find($invoice->transaction_id);
                if ($transaction) {
                    TransactionObserver::deleteCustomerLedger($transaction);
                }
            }

            $this->invoiceService->deleteInvoice($invoice, auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print invoice
     */
    public function print(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items.product.baseUnit',
            'items.product.alternativeUnits',
            'items.unit',
            'salesAccount',
            'labourAccount',
            'transportationAccount',
        ]);

        return view('sales.print', compact('invoice'));
    }

    /**
     * AJAX: Get customer balance and profile
     */
    public function getCustomerBalance(Customer $customer): JsonResponse
    {
        $customer->load('ledgerAccount');
        
        $recentInvoices = Invoice::where('customer_id', $customer->id)
            ->orderByDesc('invoice_date')
            ->limit(5)
            ->get(['id', 'invoice_number', 'invoice_date', 'total_amount', 'total_paid']);

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'credit_limit' => $customer->credit_limit,
            ],
            'balance' => $customer->current_balance ?? 0,
            'recent_invoices' => $recentInvoices,
        ]);
    }

    /**
     * AJAX: Get product details with unit conversion info
     */
    public function getProductDetails(Product $product): JsonResponse
    {
        $product->load(['baseUnit', 'alternativeUnits', 'inventoryAccount']);

        $units = $this->unitConversionService->getProductUnits($product);
        $currentStock = $product->currentstock ?? $product->opening_quantity ?? 0;
        
        // Calculate stock display in alternative units
        $stockDisplay = $this->unitConversionService->convertToAlternativeUnits($currentStock, $product);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'base_unit_id' => $product->base_unit_id,
                'base_unit_symbol' => $product->baseUnit?->symbol,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'current_stock' => $currentStock,
                'stock_display' => $stockDisplay['display'],
                'stock_breakdown' => $stockDisplay,
                'minimum_stock' => $product->minimum_stock,
            ],
            'units' => $units,
        ]);
    }

    /**
     * AJAX: Calculate alternative quantity display for given base qty
     */
    public function calculateAltQty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'unit_id' => 'nullable|exists:units,id',
        ]);

        $product = Product::with(['baseUnit', 'alternativeUnits'])->find($validated['product_id']);
        
        // Convert to base unit first
        $baseQty = $validated['quantity'];
        if (!empty($validated['unit_id']) && $validated['unit_id'] != $product->base_unit_id) {
            $baseQty = $this->unitConversionService->convertToBaseUnit(
                $validated['quantity'],
                $validated['unit_id'],
                $product
            );
        }

        // Get alternative breakdown
        $result = $this->unitConversionService->convertToAlternativeUnits($baseQty, $product);

        return response()->json([
            'success' => true,
            'base_quantity' => $baseQty,
            'display' => $result['display'],
            'boxes' => $result['boxes'],
            'pieces' => $result['pieces'],
            'breakdown' => $result['breakdown'],
        ]);
    }

    /**
     * AJAX: Get passive income accounts
     */
    public function getPassiveIncomeAccounts(): JsonResponse
    {
        $items = PassiveIncomeItem::active()
            ->with('account:id,code,name')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'account_id' => $item->account_id,
                'account_code' => $item->account?->code,
                'account_name' => $item->account?->name,
            ]);

        $incomeAccounts = Account::where('type', 'income')
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'passive_items' => $items,
            'income_accounts' => $incomeAccounts,
        ]);
    }
}