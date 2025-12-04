<?php

namespace App\Http\Controllers;

use App\Models\ReceiptVoucher;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\CustomerLedgerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ReceiptVoucherController extends Controller
{
    /**
     * Display a listing of receipt vouchers with server-side DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ReceiptVoucher::with('customer', 'receivedInAccount', 'customerAccount')
                ->select('receipt_vouchers.*');

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('receipt_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('receipt_date', '<=', $request->end_date);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment method
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by customer
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('voucher_number', function ($row) {
                    return '<a href="' . route('vouchers.receipt.show', $row->id) . '"><strong>' . $row->voucher_number . '</strong></a>';
                })
                ->addColumn('receipt_date', function ($row) {
                    return $row->receipt_date->format('d M Y');
                })
                ->addColumn('customer', function ($row) {
                    if ($row->customer) {
                        return '<span class="badge badge-info">' . $row->customer->customer_code . '</span><br>' .
                               '<small>' . $row->customer->name . '</small>';
                    }
                    return '<span class="badge badge-secondary">N/A</span>';
                })
                ->addColumn('received_in', function ($row) {
                    return '<span class="badge badge-success">' . $row->receivedInAccount->name . '</span>';
                })
                ->addColumn('amount', function ($row) {
                    return '<strong>' . number_format($row->amount, 2) . '</strong>';
                })
                ->addColumn('payment_method', function ($row) {
                    $badges = [
                        'cash' => 'badge-success',
                        'bank' => 'badge-primary',
                        'cheque' => 'badge-info',
                        'mobile_banking' => 'badge-warning',
                    ];
                    $badge = $badges[$row->payment_method] ?? 'badge-secondary';
                    return '<span class="badge ' . $badge . '">' . ucfirst(str_replace('_', ' ', $row->payment_method)) . '</span>';
                })
                ->addColumn('status', function ($row) {
                    $badges = [
                        'draft' => 'badge-secondary',
                        'posted' => 'badge-success',
                        'cancelled' => 'badge-danger',
                    ];
                    $badge = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $badge . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $viewBtn = '<a href="' . route('vouchers.receipt.show', $row->id) . '" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>';
                    
                    $editBtn = '';
                    if ($row->canEdit()) {
                        $editBtn = '<a href="' . route('vouchers.receipt.edit', $row->id) . '" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    
                    $cancelBtn = '';
                    if ($row->canCancel()) {
                        $cancelBtn = '<button type="button" class="btn btn-warning btn-sm cancel-btn" data-id="' . $row->id . '" title="Cancel"><i class="fas fa-ban"></i></button>';
                    }
                    
                    $deleteBtn = '';
                    if ($row->status === 'draft') {
                        $deleteBtn = '<button type="button" class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    
                    return '<div class="btn-group">' . $viewBtn . ' ' . $editBtn . ' ' . $cancelBtn . ' ' . $deleteBtn . '</div>';
                })
                ->rawColumns(['voucher_number', 'customer', 'received_in', 'amount', 'payment_method', 'status', 'action'])
                ->make(true);
        }

        // Get customers for filter dropdown
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('vouchers.receipt.index', compact('customers'));
    }

    /**
     * Show the form for creating a new receipt voucher
     */
public function create(Request $request)
{
    // Get accounts for receiving payment (Cash, Bank accounts)
    $receiptAccounts = Account::where('is_active', true)
        ->whereIn('type', ['asset'])
        ->where(function($query) {
            $query->where('name', 'LIKE', '%Cash%')
                  ->orWhere('name', 'LIKE', '%Bank%')
                  ->orWhere('code', 'LIKE', '1%');
        })
        ->orderBy('name')
        ->get();

    // Pre-fill from request parameters
    $preselectedCustomer = null;
    if ($request->filled('customer_id')) {
        $preselectedCustomer = Customer::with('ledgerAccount', 'group')->find($request->customer_id);
    }

    // Generate voucher number
    $voucherNumber = ReceiptVoucher::generateVoucherNumber();

    return view('vouchers.receipt.create', compact(
        'receiptAccounts',
        'preselectedCustomer',
        'voucherNumber'
    ));
}

    /**
     * Store a newly created receipt voucher in storage
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'receipt_date' => 'required|date',
        'payment_method' => 'required|in:cash,bank,cheque,mobile_banking',
        'amount' => 'required|numeric|min:0.01',
        'customer_id' => 'required|exists:customers,id',
        'received_in_account_id' => 'required|exists:accounts,id',
        'cheque_number' => 'nullable|string|max:100',
        'cheque_date' => 'nullable|date',
        'bank_name' => 'nullable|string|max:255',
        'description' => 'required|string|max:500',
        'notes' => 'nullable|string',
        'status' => 'required|in:draft,posted',
    ]);

    DB::beginTransaction();

    try {
        // Get customer and their ledger account
        $customer = Customer::with('ledgerAccount')->findOrFail($validated['customer_id']);

        if (!$customer->ledger_account_id) {
            throw new \Exception('Customer does not have a ledger account.');
        }

        // Generate voucher number
        $voucherNumber = ReceiptVoucher::generateVoucherNumber();

        // Create transaction for double-entry
        $transaction = Transaction::create([
            'date' => $validated['receipt_date'],
            'type' => 'receipt',
            'reference' => $voucherNumber,
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
        ]);

        // Create transaction entries (double-entry bookkeeping)
        // 1. Debit: Received In Account (Cash/Bank increases)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $validated['received_in_account_id'],
            'type' => 'debit',
            'amount' => $validated['amount'],
            'memo' => 'Receipt from ' . $customer->name,
        ]);

        // 2. Credit: Customer Account (Reduces receivable/outstanding)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $customer->ledger_account_id,
            'type' => 'credit',
            'amount' => $validated['amount'],
            'memo' => 'Payment received - ' . $validated['description'],
        ]);

        // Create receipt voucher
        $receiptVoucher = ReceiptVoucher::create([
            'voucher_number' => $voucherNumber,
            'receipt_date' => $validated['receipt_date'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'customer_id' => $validated['customer_id'],
            'received_in_account_id' => $validated['received_in_account_id'],
            'customer_account_id' => $customer->ledger_account_id,
            'transaction_id' => $transaction->id,
            'cheque_number' => $validated['cheque_number'] ?? null,
            'cheque_date' => $validated['cheque_date'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
        ]);

        // ✅ ADD THIS: Manually sync to customer ledger AFTER entries are created
        if ($transaction->status === 'posted') {
            \App\Observers\TransactionObserver::syncToCustomerLedger($transaction);
        }

        DB::commit();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Receipt voucher created successfully!',
                'data' => $receiptVoucher->load('customer', 'receivedInAccount', 'customerAccount', 'transaction'),
            ]);
        }

        return redirect()
            ->route('vouchers.receipt.show', $receiptVoucher->id)
            ->with('success', 'Receipt voucher created successfully!');

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Receipt voucher creation error: ' . $e->getMessage());

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating receipt voucher: ' . $e->getMessage(),
            ], 500);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Error creating receipt voucher: ' . $e->getMessage());
    }
}

    /**
     * Display the specified receipt voucher
     */
    public function show(ReceiptVoucher $receiptVoucher)
    {
        $receiptVoucher->load('customer', 'receivedInAccount', 'customerAccount', 'transaction.entries.account');

        return view('vouchers.receipt.show', compact('receiptVoucher'));
    }

    /**
     * Show the form for editing the specified receipt voucher
     */
    public function edit(ReceiptVoucher $receiptVoucher)
    {
        if (!$receiptVoucher->canEdit()) {
            return redirect()
                ->route('vouchers.receipt.show', $receiptVoucher->id)
                ->with('error', 'Only draft vouchers can be edited.');
        }

        $receiptVoucher->load('customer', 'receivedInAccount', 'customerAccount');

        // Get accounts
        $receiptAccounts = Account::where('is_active', true)
            ->whereIn('type', ['asset'])
            ->where(function($query) {
                $query->where('name', 'LIKE', '%Cash%')
                      ->orWhere('name', 'LIKE', '%Bank%')
                      ->orWhere('code', 'LIKE', '1%');
            })
            ->orderBy('name')
            ->get();

        $customers = Customer::where('is_active', true)
            ->with('ledgerAccount')
            ->orderBy('name')
            ->get();

        return view('vouchers.receipt.edit', compact(
            'receiptVoucher',
            'receiptAccounts',
            'customers'
        ));
    }

    /**
     * Update the specified receipt voucher in storage
     */
    public function update(Request $request, ReceiptVoucher $receiptVoucher)
    {
        if (!$receiptVoucher->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'receipt_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,cheque,mobile_banking',
            'amount' => 'required|numeric|min:0.01',
            'customer_id' => 'required|exists:customers,id',
            'received_in_account_id' => 'required|exists:accounts,id',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
        ]);

        DB::beginTransaction();
        try {
            // Get customer and their ledger account
            $customer = Customer::with('ledgerAccount')->findOrFail($validated['customer_id']);
            
            if (!$customer->ledger_account_id) {
                throw new \Exception('Customer does not have a ledger account.');
            }

            // Update transaction
            if ($receiptVoucher->transaction) {
                $receiptVoucher->transaction->update([
                    'date' => $validated['receipt_date'],
                    'description' => $validated['description'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => $validated['status'],
                ]);

                // Delete old entries
                $receiptVoucher->transaction->entries()->delete();

                // Create new transaction entries
                TransactionEntry::create([
                    'transaction_id' => $receiptVoucher->transaction->id,
                    'account_id' => $validated['received_in_account_id'],
                    'type' => 'debit',
                    'amount' => $validated['amount'],
                    'memo' => 'Receipt from ' . $customer->name,
                ]);

                TransactionEntry::create([
                    'transaction_id' => $receiptVoucher->transaction->id,
                    'account_id' => $customer->ledger_account_id,
                    'type' => 'credit',
                    'amount' => $validated['amount'],
                    'memo' => 'Payment received - ' . $validated['description'],
                ]);
            }

            // Update receipt voucher
            $receiptVoucher->update([
                'receipt_date' => $validated['receipt_date'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'customer_id' => $validated['customer_id'],
                'received_in_account_id' => $validated['received_in_account_id'],
                'customer_account_id' => $customer->ledger_account_id,
                'cheque_number' => $validated['cheque_number'] ?? null,
                'cheque_date' => $validated['cheque_date'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Receipt voucher updated successfully!',
                    'data' => $receiptVoucher->load('customer', 'receivedInAccount', 'customerAccount', 'transaction'),
                ]);
            }

            return redirect()
                ->route('vouchers.receipt.show', $receiptVoucher->id)
                ->with('success', 'Receipt voucher updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Receipt voucher update error: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating receipt voucher: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating receipt voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified receipt voucher from storage
     */
    public function destroy(ReceiptVoucher $receiptVoucher)
    {
        if ($receiptVoucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be deleted.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete transaction entries
            if ($receiptVoucher->transaction) {
                $receiptVoucher->transaction->entries()->delete();
                $receiptVoucher->transaction->delete();
            }

            // Delete receipt voucher
            $receiptVoucher->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Receipt voucher deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Receipt voucher deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting receipt voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a posted receipt voucher
     */
    public function cancel(ReceiptVoucher $receiptVoucher)
    {
        if (!$receiptVoucher->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Only posted vouchers can be cancelled.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update voucher status
            $receiptVoucher->update(['status' => 'cancelled']);

            // Void the transaction
            if ($receiptVoucher->transaction) {
                $receiptVoucher->transaction->update(['status' => 'voided']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Receipt voucher cancelled successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Receipt voucher cancellation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error cancelling receipt voucher: ' . $e->getMessage(),
            ], 500);
        }
    }
/**
 * Search customers via AJAX for Select2 (by name, code, or phone)
 */
public function searchCustomers(Request $request)
{
    $search = $request->get('q', '');
    $page = $request->get('page', 1);
    $perPage = 20;

    $query = Customer::where('is_active', true)
        ->with('ledgerAccount', 'group');

    // Search by name, customer code, OR phone number
    if (!empty($search)) {
        $query->where(function($q) use ($search) {
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

    // Format for Select2
    $results = $customers->map(function($customer) {
        // Calculate current balance
        $currentBalance = $customer->transactions()->sum(DB::raw('debit - credit'));
        $openingBalance = $customer->opening_balance_type === 'debit' 
            ? $customer->opening_balance 
            : -$customer->opening_balance;
        $totalBalance = $openingBalance + $currentBalance;
        $balanceFormatted = number_format(abs($totalBalance), 2);
        $balanceLabel = $totalBalance >= 0 ? 'Dr' : 'Cr';

        return [
            'id' => $customer->id,
            'text' => $customer->customer_code . ' - ' . $customer->name . ' | ' . $customer->phone,
            'customer_code' => $customer->customer_code,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'ledger_account_id' => $customer->ledger_account_id,
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
     * Get customer details via AJAX for fast searching
     */
    public function getCustomerDetails(Customer $customer)
    {
        try {
            $customer->load('ledgerAccount', 'group');

            // Calculate current balance
            $currentBalance = $customer->transactions()->sum(DB::raw('debit - credit'));
            $openingBalance = $customer->opening_balance_type === 'debit' 
                ? $customer->opening_balance 
                : -$customer->opening_balance;
            $totalBalance = $openingBalance + $currentBalance;

            // Get recent outstanding invoices
            $outstandingInvoices = CustomerLedgerTransaction::where('customer_id', $customer->id)
                ->where('voucher_type', 'Sales Invoice')
                ->where('balance', '>', 0)
                ->orderBy('transaction_date', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'voucher_number' => $invoice->voucher_number,
                        'date' => $invoice->transaction_date->format('d M Y'),
                        'amount' => number_format($invoice->debit, 2),
                        'balance' => number_format($invoice->balance, 2),
                        'due_date' => $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A',
                        'is_overdue' => $invoice->due_date && $invoice->due_date->isPast(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'city' => $customer->city,
                    'group' => $customer->group ? $customer->group->name : 'N/A',
                    'ledger_account_id' => $customer->ledger_account_id,
                    'ledger_account_name' => $customer->ledgerAccount->name ?? 'N/A',
                    'current_balance' => $totalBalance,
                    'current_balance_formatted' => number_format(abs($totalBalance), 2),
                    'balance_type' => $totalBalance >= 0 ? 'Dr (Receivable)' : 'Cr (Advance)',
                    'balance_class' => $totalBalance >= 0 ? 'text-danger' : 'text-success',
                    'credit_limit' => number_format($customer->credit_limit, 2),
                    'credit_period_days' => $customer->credit_period_days,
                    'current_due_date' => $customer->current_due_date ? $customer->current_due_date->format('d M Y') : 'N/A',
                    'is_overdue' => $customer->current_due_date && $customer->current_due_date->isPast() && $totalBalance > 0,
                    'outstanding_invoices' => $outstandingInvoices,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching customer details: ' . $e->getMessage(),
            ], 500);
        }
    }
}
