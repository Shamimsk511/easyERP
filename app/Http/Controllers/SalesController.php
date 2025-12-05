<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Account;
use App\Services\Sales\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SalesController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    // ===== VIEW METHODS =====
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getInvoicesDataTable($request);
        }

        return view('sales.index');
    }

    public function create()
    {
        $salesAccounts = Account::where('type', 'income')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $products = Product::active()
            ->select('id', 'name', 'code', 'base_unit_id', 'selling_price', 'current_stock')
            ->with('baseUnit')
            ->limit(50)
            ->get();

        return view('sales.create', compact('salesAccounts', 'products'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items.product', 'items.unit', 'deliveries', 'payments']);
        return view('sales.show', compact('invoice'));
    }
 /**
     * Update invoice (NEW - for edit functionality)
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id'                 => 'required|exists:customers,id',
            'invoice_date'                => 'required|date',
            'due_date'                    => 'nullable|date',
            'sales_account_id'            => 'nullable|exists:accounts,id',
            'items'                       => 'required|array|min:1',
            'items.*.product_id'          => 'required_if:items.*.item_type,product|exists:products,id',
            'items.*.quantity'            => 'required|numeric|min:0.01',
            'items.*.unit_id'             => 'nullable|exists:units,id', // Always defaults to base unit in service
            'items.*.unit_price'          => 'required|numeric|min:0',
            'items.*.discount_percent'    => 'nullable|numeric|min:0|max:100',
            'internal_notes'              => 'nullable|string',
            'customer_notes'              => 'nullable|string',
        ]);

        try {
            // TODO: Implement update logic via InvoiceService (void old transaction, create new)
            // For Phase 3, just redirect to show
            return redirect()->route('sales.show', $invoice)
                ->with('success', 'Invoice updated successfully!');
        } catch (\Exception $e) {
            Log::error('Invoice update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update invoice');
        }
    }

    /**
     * Edit invoice form (NEW)
     */
    public function edit(Invoice $invoice)
    {
        $salesAccounts = Account::where('type', 'income')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $products = Product::active()
            ->select('id', 'name', 'code', 'base_unit_id', 'selling_price', 'current_stock')
            ->with('baseUnit')
            ->limit(50)
            ->get();

        return view('sales.edit', compact('invoice', 'salesAccounts', 'products'));
    }
    // ===== AJAX ENDPOINTS =====

    /**
     * Search customers with pagination
     * Returns: name, phone, customer_code, outstanding_balance
     */
/**
 * Search customers for Select2 AJAX
 */
public function searchCustomers(Request $request)
{
    try {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 10;

        $query = Customer::where('is_active', true);

        if ($search) {
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
            return [
                'id' => $customer->id,
                'text' => "{$customer->customer_code} - {$customer->name}",
                'customercode' => $customer->customer_code,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ];
        });

        // FIXED: Proper response structure
        return response()->json([
            'results' => $results->values()->all(),  // Convert to array
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Search customers error: ' . $e->getMessage());
        return response()->json([
            'results' => [],
            'pagination' => ['more' => false]
        ]);
    }
}


public function searchProducts(Request $request)
{
    try {
        $search = $request->get('q', '');

        $query = Product::where('is_active', true)
            ->with('baseUnit', 'alternativeUnits');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')
            ->limit(20)
            ->get();

        $results = $products->map(function ($product) {
            // FIX: Check both column names
            $stock = $product->current_stock ?? $product->currentstock ?? 0;
            
            return [
                'id' => $product->id,
                'text' => $product->name . ($product->code ? ' (' . $product->code . ')' : ''),
                'data' => [  // ← SIMPLIFIED: no HTML in data
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code ?? '',
                    'stock' => $stock,
                    'unit' => $product->baseUnit->symbol ?? 'pc',
                    'price' => $product->selling_price ?? 0,
                ],
            ];
        });

        return response()->json([
            'results' => $results->values()->all(),
            'pagination' => [
                'more' => false
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Search products error: ' . $e->getMessage());
        return response()->json([
            'results' => [],
            'pagination' => ['more' => false]
        ]);
    }
}






    /**
     * Quick add customer
     */
    public function quickAddCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
        ]);

        try {
            // Get or create ledger account
            $accountName = "AR - {$validated['name']}";
            $arAccount = Account::where('name', $accountName)->first();
            
            if (!$arAccount) {
                $arAccount = Account::create([
                    'code' => 'AR-CUST-' . str_pad(Customer::count() + 1, 4, '0', STR_PAD_LEFT),
                    'name' => $accountName,
                    'type' => 'asset',
                    'description' => "Accounts Receivable for {$validated['name']}",
                    'is_active' => true,
                ]);
            }

            $customer = Customer::create([
                'customer_code' => Customer::generateCustomerCode(),
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? '',
                'city' => $validated['city'] ?? '',
                'ledger_account_id' => $arAccount->id,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'customer' => $customer->getCustomerInfo(),
            ]);

        } catch (\Exception $e) {
            Log::error('Quick add customer error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get invoices data table
     */
    private function getInvoicesDataTable(Request $request)
    {
        $query = Invoice::with('customer')
            ->select('invoices.*');

        // Filters
        $deliveryStatus = $request->get('delivery_status');
        $showDeleted = $request->get('show_deleted', 'no') === 'yes';

        if ($deliveryStatus) {
            $query->where('delivery_status', $deliveryStatus);
        }

        if ($showDeleted) {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        return DataTables::eloquent($query)
            ->addColumn('invoice_number', function($invoice) {
                return $invoice->invoice_number;
            })
            ->addColumn('customer_name', function($invoice) {
                return $invoice->customer->name ?? '-';
            })
            ->addColumn('invoice_date', function($invoice) {
                return $invoice->invoice_date->format('d M Y');
            })
            ->addColumn('total_amount', function($invoice) {
                return '৳ ' . number_format($invoice->total_amount, 2);
            })
            ->addColumn('total_paid', function($invoice) {
                return '৳ ' . number_format($invoice->total_paid, 2);
            })
            ->addColumn('outstanding', function($invoice) {
                $outstanding = $invoice->outstanding_balance;
                $class = $outstanding > 0 ? 'text-danger' : 'text-success';
                return '<span class="' . $class . '">৳ ' . number_format(abs($outstanding), 2) . '</span>';
            })
            ->addColumn('delivery_badge', function($invoice) {
                $badges = [
                    'pending' => '<span class="badge badge-warning">Pending</span>',
                    'partial' => '<span class="badge badge-info">Partial</span>',
                    'delivered' => '<span class="badge badge-success">Delivered</span>',
                ];
                return $badges[$invoice->delivery_status] ?? '-';
            })
            ->addColumn('actions', function($invoice) {
                $viewBtn = '<a href="' . route('sales.show', $invoice) . '" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>';
                
                if ($invoice->deleted_at) {
                    return '<span class="text-muted">Deleted on ' . $invoice->deleted_at->format('d M Y') . '</span> ' . $viewBtn;
                }

                $editBtn = '<a href="' . route('sales.edit', $invoice) . '" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>';
                $deliveryBtn = '<button class="btn btn-success btn-sm delivery-btn" data-id="' . $invoice->id . '" title="Create Delivery"><i class="fas fa-box"></i></button>';
                $paymentBtn = '<button class="btn btn-primary btn-sm payment-btn" data-id="' . $invoice->id . '" title="Add Payment"><i class="fas fa-money-bill"></i></button>';
                $deleteBtn = '<button class="btn btn-danger btn-sm delete-invoice" data-id="' . $invoice->id . '" title="Delete"><i class="fas fa-trash"></i></button>';

                return $viewBtn . ' ' . $editBtn . ' ' . $deliveryBtn . ' ' . $paymentBtn . ' ' . $deleteBtn;
            })
            ->rawColumns(['outstanding', 'delivery_badge', 'actions'])
            ->make(true);
    }

    // ===== STORE/UPDATE =====
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'sales_account_id' => 'nullable|exists:accounts,id',
            'items' => 'required|array|min:1',
            'items.*.product_id'          => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'internal_notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
        ]);

        try {
            $invoice = $this->invoiceService->createInvoice($validated);

             if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully!',
                'invoice_id' => $invoice->id,
                'redirect' => route('sales.show', $invoice)
            ]);
        }
            return redirect()
                ->route('sales.show', $invoice)
                ->with('success', 'Invoice created successfully!');
        } catch (\Exception $e) {
            Log::error('Invoice creation error: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    // ===== DELETE =====
    public function destroy(Invoice $invoice)
    {
        try {
            $this->invoiceService->deleteInvoice($invoice, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Invoice deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice: ' . $e->getMessage(),
            ], 422);
        }
    }

     public function getCustomerDetails($customerId)
    {
       try {
        $customer = Customer::with('ledgerAccount')->findOrFail($customerId);

        // Calculate outstanding balance
        $outstandingBalance = $this->calculateCustomerBalance($customer->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'customer_code' => $customer->customer_code,
                'phone' => $customer->phone,
                'city' => $customer->city,
                'outstandingbalance' => round($outstandingBalance, 2),
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Get customer details error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
    }
}
    private function calculateCustomerBalance($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        if (!$customer->ledger_account_id) {
            return 0;
        }

        $account = Account::find($customer->ledger_account_id);
        if (!$account) {
            return 0;
        }

        // Get total debits (sales/invoices)
        $debits = $account->transactionEntries()
            ->where('type', 'debit')
            ->sum('amount');

        // Get total credits (payments received)
        $credits = $account->transactionEntries()
            ->where('type', 'credit')
            ->sum('amount');

        // Outstanding = Debits - Credits (what customer owes us)
        $outstanding = $debits - $credits;

        // Add opening balance if exists
        if ($customer->opening_balance > 0) {
            if ($customer->opening_balance_type === 'debit') {
                $outstanding += $customer->opening_balance;
            } else {
                $outstanding -= $customer->opening_balance;
            }
        }

        return $outstanding;
    }
}
