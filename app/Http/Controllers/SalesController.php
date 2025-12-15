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
        if ($request->ajax()) {
            $query = Invoice::with(['customer', 'items', 'payments', 'deliveries'])
                ->select('invoices.*');

            return DataTables::eloquent($query)
                ->addColumn('customer_name', fn($invoice) => $invoice->customer->name ?? 'N/A')
                ->addColumn('customer_phone', fn($invoice) => $invoice->customer->phone ?? 'N/A')
                ->addColumn('total_paid', fn($invoice) => number_format($invoice->total_paid, 2))
                ->addColumn('outstanding_balance', fn($invoice) => number_format($invoice->outstanding_balance, 2))
                ->addColumn('delivery_status_badge', function ($invoice) {
                    $badges = [
                        'pending' => '<span class="badge badge-warning">Pending</span>',
                        'partial' => '<span class="badge badge-info">Partial</span>',
                        'delivered' => '<span class="badge badge-success">Delivered</span>',
                    ];
                    return $badges[$invoice->delivery_status] ?? '<span class="badge badge-secondary">Unknown</span>';
                })
                ->addColumn('action', function ($invoice) {
                    return view('sales.partials.actions', compact('invoice'))->render();
                })
                ->editColumn('invoice_date', fn($invoice) => $invoice->invoice_date->format('d M Y'))
                ->editColumn('total_amount', fn($invoice) => number_format($invoice->total_amount, 2))
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->filterColumn('customer_phone', function ($query, $keyword) {
                    $query->whereHas('customer', fn($q) => $q->where('phone', 'like', "%{$keyword}%"));
                })
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
        $customers = Customer::active()->get();
        $products = Product::active()->with(['baseUnit', 'alternativeUnits'])->get();
        $salesAccounts = Account::where('type', 'income')->active()->get();
        $cashAccounts = Account::whereIn('code', ['1110', '1120'])->active()->get();

        return view('sales.create', compact('customers', 'products', 'salesAccounts', 'cashAccounts'));
    }

    /**
     * Store new invoice
     * Fixed: Using StoreInvoiceRequest for proper validation
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = $this->invoiceService->createInvoice($request->validated());

            // Sync to customer ledger
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

        // Check if invoice has deliveries - prevent editing if delivered
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
     * Fixed: Using UpdateInvoiceRequest for proper validation
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        // Prevent editing delivered invoices
        if ($invoice->delivery_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit invoice with deliveries',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete old invoice and create new one (safer for accounting)
            $oldInvoiceNumber = $invoice->invoice_number;

            // Delete customer ledger entries for old transaction
            if ($invoice->transaction_id) {
                $oldTransaction = Transaction::find($invoice->transaction_id);
                if ($oldTransaction) {
                    TransactionObserver::deleteCustomerLedger($oldTransaction);
                }
            }

            $this->invoiceService->deleteInvoice($invoice, auth()->id());

            // Create new invoice with same number logic
            $newInvoice = $this->invoiceService->createInvoice($request->validated());

            // Sync new transaction to customer ledger
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
        // Prevent deleting delivered invoices
        if ($invoice->delivery_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete invoice with deliveries. Reverse deliveries first.',
            ], 422);
        }

        // Prevent deleting invoices with payments
        if ($invoice->payments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete invoice with payments. Delete payments first.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete customer ledger entries
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

    /**
     * Get customer balance for AJAX
     */
    public function getCustomerBalance(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'balance' => $customer->current_balance,
            'formatted' => $customer->formatted_balance,
            'credit_limit' => $customer->credit_limit,
            'available_credit' => $customer->available_credit,
            'is_overdue' => $customer->is_overdue,
        ]);
    }

    /**
     * Get product details for AJAX
     */
    public function getProductDetails(Product $product, Request $request): JsonResponse
    {
        $product->load(['baseUnit', 'alternativeUnits']);

        // Get last rate for customer if provided
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