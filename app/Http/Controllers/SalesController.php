<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Sales\InvoiceService;
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
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Display listing with DataTables
     */
    public function index(Request $request)
    {
        return view('sales.index');
    }

    /**
     * DataTables server-side data
     */
    public function getData(Request $request)
    {
        $query = Invoice::with(['customer', 'items', 'payments', 'deliveries'])
            ->select('invoices.*');

        // Filter by delivery status
        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        // Filter deleted
        if ($request->input('show_deleted') === 'yes') {
            $query->onlyTrashed();
        }

        return DataTables::eloquent($query)
            ->addColumn('customer_name', fn($inv) => $inv->customer->name ?? 'N/A')
            ->addColumn('customer_phone', fn($inv) => $inv->customer->phone ?? 'N/A')
            ->addColumn('total_paid', fn($inv) => number_format($inv->total_paid, 2))
            ->addColumn('outstanding_balance', fn($inv) => number_format($inv->outstanding_balance, 2))
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
            ->filterColumn('customer_name', fn($q, $kw) => $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$kw}%")))
            ->filterColumn('customer_phone', fn($q, $kw) => $q->whereHas('customer', fn($sq) => $sq->where('phone', 'like', "%{$kw}%")))
            ->rawColumns(['action', 'delivery_status_badge'])
            ->make(true);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $customers = Customer::active()->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->get();
        $salesAccounts = Account::where('type', 'income')->active()->get();
        $cashAccounts = Account::whereIn('code', ['1110', '1120'])->active()->get();

        return view('sales.create', compact('customers', 'products', 'salesAccounts', 'cashAccounts'));
    }

    /**
     * Store new invoice
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = $this->invoiceService->createInvoice($request->validated());

            if ($invoice && $invoice->transaction_id) {
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
                'request' => $request->validated(),
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
            'items.unit',
            'deliveries.items.invoiceItem',
            'payments.account',
            'salesAccount',
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
        if ($invoice->trashed()) {
            return redirect()->route('sales.index')
                ->with('error', 'Cannot edit deleted invoice');
        }

        if ($invoice->delivery_status !== 'pending') {
            return redirect()->route('sales.show', $invoice)
                ->with('warning', 'Cannot edit invoice with deliveries. Create a credit note instead.');
        }

        $invoice->load(['customer', 'items.product.baseUnit', 'items.unit']);
        $customers = Customer::active()->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->get();
        $salesAccounts = Account::where('type', 'income')->active()->get();

        return view('sales.edit', compact('invoice', 'customers', 'products', 'salesAccounts'));
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

            $oldInvoiceNumber = $invoice->invoice_number;

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
            Log::error('Invoice update failed: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString(),
            ]);

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
                'message' => 'Cannot delete invoice with deliveries',
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
            'items.unit',
            'salesAccount',
        ]);

        return view('sales.print', compact('invoice'));
    }

    // ============================================
    // AJAX ENDPOINTS FOR SELECT2 & SIDEBAR
    // ============================================

    /**
     * Search customers for Select2 (by name, code, or phone)
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = Customer::where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();

        $customers = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $customers->map(function ($customer) {
            $balance = $customer->current_balance;
            $balanceFormatted = number_format(abs($balance), 2);
            $balanceLabel = $balance >= 0 ? 'Dr' : 'Cr';

            return [
                'id' => $customer->id,
                'text' => $customer->name . ' | ' . $customer->phone,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'phone' => $customer->phone ?? '-',
                'balance' => $balanceFormatted . ' ' . $balanceLabel,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Search products for Select2 (by name or code)
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = Product::where('is_active', true)
            ->with(['baseUnit', 'alternativeUnits']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();

        $products = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->name . ($product->code ? ' (' . $product->code . ')' : ''),
                'name' => $product->name,
                'code' => $product->code,
                'stock' => $product->current_stock,
                'price' => $product->selling_price,
                'unit' => $product->baseUnit->symbol ?? 'PCS',
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Get customer details for sidebar profile
     */
    public function getCustomerDetails(Customer $customer): JsonResponse
    {
        $customer->load('group');

        $balance = $customer->current_balance;
        $creditLimit = $customer->credit_limit ?? 0;
        $creditRemaining = max(0, $creditLimit - $balance);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'name' => $customer->name,
                'phone' => $customer->phone ?? '-',
                'email' => $customer->email ?? '-',
                'address' => $customer->address ?? '-',
                'city' => $customer->city ?? '-',
                'group' => $customer->group->name ?? 'No Group',
                'outstanding_balance' => $balance,
                'credit_limit' => $creditLimit,
                'credit_remaining' => $creditRemaining,
                'is_overdue' => $customer->is_overdue ?? false,
            ],
        ]);
    }

    /**
     * Get customer balance (simple balance check)
     */
    public function getCustomerBalance(Customer $customer): JsonResponse
    {
        $balance = $customer->current_balance;
        $creditLimit = $customer->credit_limit ?? 0;

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'formatted' => '৳ ' . number_format(abs($balance), 2) . ($balance >= 0 ? ' Dr' : ' Cr'),
            'credit_limit' => $creditLimit,
            'available_credit' => max(0, $creditLimit - $balance),
            'is_overdue' => $customer->is_overdue ?? false,
        ]);
    }

    /**
     * Get product details for line item
     */
    public function getProductDetails(Product $product, Request $request): JsonResponse
    {
        $product->load(['baseUnit', 'alternativeUnits']);

        $lastRate = null;
        if ($request->filled('customer_id')) {
            $lastRate = $product->getLastRateForCustomer($request->customer_id);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'current_stock' => $product->current_stock,
                'selling_price' => $product->selling_price,
                'last_rate' => $lastRate,
                'base_unit' => [
                    'id' => $product->baseUnit->id,
                    'name' => $product->baseUnit->name,
                    'symbol' => $product->baseUnit->symbol,
                ],
                'units' => $product->alternativeUnits->map(fn($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'symbol' => $unit->symbol,
                    'conversion_factor' => $unit->pivot->conversion_factor,
                ]),
            ],
        ]);
    }
}