<?php

namespace App\Http\Controllers;

use App\Models\PaymentVoucher;
use App\Models\Account;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of payment vouchers with server-side DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PaymentVoucher::with(['paidFromAccount', 'paidToAccount'])
                ->select('payment_vouchers.*');
            
            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('payment_date', '<=', $request->end_date);
            }
            
            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by payment method
            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }
            
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('voucher_number', function ($row) {
                    return '<a href="' . route('vouchers.payment.show', $row->id) . '">' .
                           '<strong>' . $row->voucher_number . '</strong></a>';
                })
                ->addColumn('payment_date', function ($row) {
                    return $row->payment_date->format('d M Y');
                })
                ->addColumn('payee', function ($row) {
                    if ($row->payee_type && $row->payee_id) {
                        $payeeName = $this->getPayeeName($row->payee_type, $row->payee_id);
                        $badge = $row->payee_type === 'vendor' ? 'badge-warning' : 'badge-info';
                        return '<span class="badge ' . $badge . '">' . ucfirst($row->payee_type) . '</span><br>' .
                               '<small>' . $payeeName . '</small>';
                    }
                    return '<span class="badge badge-secondary">N/A</span>';
                })
                ->addColumn('paid_from', function ($row) {
                    return '<span class="badge badge-primary">' . $row->paidFromAccount->name . '</span>';
                })
                ->addColumn('paid_to', function ($row) {
                    return '<span class="badge badge-success">' . $row->paidToAccount->name . '</span>';
                })
                ->addColumn('amount', function ($row) {
                    return '<strong>৳ ' . number_format($row->amount, 2) . '</strong>';
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
                    $viewBtn = '<a href="' . route('vouchers.payment.show', $row->id) . '" class="btn btn-info btn-sm" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>';
                    
                    $editBtn = '';
                    if ($row->canEdit()) {
                        $editBtn = '<a href="' . route('vouchers.payment.edit', $row->id) . '" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>';
                    }
                    
                    $cancelBtn = '';
                    if ($row->canCancel()) {
                        $cancelBtn = '<button type="button" class="btn btn-warning btn-sm cancel-btn" data-id="' . $row->id . '" title="Cancel">
                                        <i class="fas fa-ban"></i>
                                    </button>';
                    }
                    
                    $deleteBtn = '';
                    if ($row->status === 'draft') {
                        $deleteBtn = '<button type="button" class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>';
                    }
                    
                    return '<div class="btn-group">' . $viewBtn . ' ' . $editBtn . ' ' . $cancelBtn . ' ' . $deleteBtn . '</div>';
                })
                ->rawColumns(['voucher_number', 'payee', 'paid_from', 'paid_to', 'amount', 'payment_method', 'status', 'action'])
                ->make(true);
        }
        
        return view('vouchers.payment.index');
    }

    /**
     * Show the form for creating a new payment voucher
     */
    public function create(Request $request)
    {
        // Get accounts for payment source (Cash, Bank accounts)
        $paymentAccounts = Account::where('is_active', true)
            ->whereIn('type', ['asset'])
            ->where(function($query) {
                $query->where('name', 'LIKE', '%Cash%')
                      ->orWhere('name', 'LIKE', '%Bank%')
                      ->orWhere('code', 'LIKE', '1%');
            })
            ->orderBy('name')
            ->get();
        
        // Get all active accounts for payment recipient
        $allAccounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        // Get vendors and customers for quick selection
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        
        // Pre-fill from request parameters
        $preselectedVendor = null;
        $preselectedAccount = null;
        $payeeType = $request->get('payee_type', 'vendor');
        
        if ($request->filled('vendor_id')) {
            $preselectedVendor = Vendor::find($request->vendor_id);
            if ($preselectedVendor) {
                $preselectedAccount = $preselectedVendor->ledger_account_id;
            }
        }
        
        // Generate voucher number
        $voucherNumber = PaymentVoucher::generateVoucherNumber();
        
        return view('vouchers.payment.create', compact(
            'paymentAccounts',
            'allAccounts',
            'vendors',
            'customers',
            'preselectedVendor',
            'preselectedAccount',
            'payeeType',
            'voucherNumber'
        ));
    }

    /**
     * Store a newly created payment voucher in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,cheque,mobile_banking',
            'amount' => 'required|numeric|min:0.01',
            'payee_type' => 'nullable|in:vendor,customer,employee,other',
            'payee_id' => 'nullable|integer',
            'paid_from_account_id' => 'required|exists:accounts,id',
            'paid_to_account_id' => 'required|exists:accounts,id',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
        ]);

        // Validate accounts are different
        if ($validated['paid_from_account_id'] === $validated['paid_to_account_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Payment source and destination accounts must be different.',
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Generate voucher number
            $voucherNumber = PaymentVoucher::generateVoucherNumber();
            
            // Create transaction for double-entry
            $transaction = Transaction::create([
                'date' => $validated['payment_date'],
                'type' => 'payment',
                'reference' => $voucherNumber,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            // Create transaction entries (double-entry bookkeeping)
            // Debit: Paid To Account (increases expense/liability payment or decreases liability)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $validated['paid_to_account_id'],
                'type' => 'debit',
                'amount' => $validated['amount'],
                'memo' => 'Payment: ' . $validated['description'],
            ]);

            // Credit: Paid From Account (decreases cash/bank)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $validated['paid_from_account_id'],
                'type' => 'credit',
                'amount' => $validated['amount'],
                'memo' => 'Payment from: ' . Account::find($validated['paid_from_account_id'])->name,
            ]);

            // Create payment voucher
            $paymentVoucher = PaymentVoucher::create([
                'voucher_number' => $voucherNumber,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'payee_type' => $validated['payee_type'] ?? null,
                'payee_id' => $validated['payee_id'] ?? null,
                'paid_from_account_id' => $validated['paid_from_account_id'],
                'paid_to_account_id' => $validated['paid_to_account_id'],
                'transaction_id' => $transaction->id,
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
                    'message' => 'Payment voucher created successfully!',
                    'data' => $paymentVoucher->load(['paidFromAccount', 'paidToAccount', 'transaction']),
                ]);
            }

            return redirect()
                ->route('vouchers.payment.show', $paymentVoucher->id)
                ->with('success', 'Payment voucher created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment voucher creation error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating payment voucher: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating payment voucher: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payment voucher
     */
    public function show(PaymentVoucher $paymentVoucher)
    {
        $paymentVoucher->load(['paidFromAccount', 'paidToAccount', 'transaction.entries.account']);
        
        // Get payee details if exists
        $payeeDetails = null;
        if ($paymentVoucher->payee_type && $paymentVoucher->payee_id) {
            $payeeDetails = $this->getPayeeDetails($paymentVoucher->payee_type, $paymentVoucher->payee_id);
        }
        
        return view('vouchers.payment.show', compact('paymentVoucher', 'payeeDetails'));
    }

    /**
     * Show the form for editing the specified payment voucher
     */
    public function edit(PaymentVoucher $paymentVoucher)
    {
        if (!$paymentVoucher->canEdit()) {
            return redirect()
                ->route('vouchers.payment.show', $paymentVoucher->id)
                ->with('error', 'Only draft vouchers can be edited.');
        }
        
        $paymentVoucher->load(['paidFromAccount', 'paidToAccount']);
        
        // Get accounts
        $paymentAccounts = Account::where('is_active', true)
            ->whereIn('type', ['asset'])
            ->where(function($query) {
                $query->where('name', 'LIKE', '%Cash%')
                      ->orWhere('name', 'LIKE', '%Bank%')
                      ->orWhere('code', 'LIKE', '1%');
            })
            ->orderBy('name')
            ->get();
        
        $allAccounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        
        return view('vouchers.payment.edit', compact(
            'paymentVoucher',
            'paymentAccounts',
            'allAccounts',
            'vendors',
            'customers'
        ));
    }

    /**
     * Update the specified payment voucher in storage
     */
    public function update(Request $request, PaymentVoucher $paymentVoucher)
    {
        if (!$paymentVoucher->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,cheque,mobile_banking',
            'amount' => 'required|numeric|min:0.01',
            'payee_type' => 'nullable|in:vendor,customer,employee,other',
            'payee_id' => 'nullable|integer',
            'paid_from_account_id' => 'required|exists:accounts,id',
            'paid_to_account_id' => 'required|exists:accounts,id',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
        ]);

        // Validate accounts are different
        if ($validated['paid_from_account_id'] === $validated['paid_to_account_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Payment source and destination accounts must be different.',
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Update transaction
            if ($paymentVoucher->transaction) {
                $paymentVoucher->transaction->update([
                    'date' => $validated['payment_date'],
                    'description' => $validated['description'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => $validated['status'],
                ]);

                // Delete old entries
                $paymentVoucher->transaction->entries()->delete();

                // Create new transaction entries
                TransactionEntry::create([
                    'transaction_id' => $paymentVoucher->transaction->id,
                    'account_id' => $validated['paid_to_account_id'],
                    'type' => 'debit',
                    'amount' => $validated['amount'],
                    'memo' => 'Payment: ' . $validated['description'],
                ]);

                TransactionEntry::create([
                    'transaction_id' => $paymentVoucher->transaction->id,
                    'account_id' => $validated['paid_from_account_id'],
                    'type' => 'credit',
                    'amount' => $validated['amount'],
                    'memo' => 'Payment from: ' . Account::find($validated['paid_from_account_id'])->name,
                ]);
            }

            // Update payment voucher
            $paymentVoucher->update([
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount'],
                'payee_type' => $validated['payee_type'] ?? null,
                'payee_id' => $validated['payee_id'] ?? null,
                'paid_from_account_id' => $validated['paid_from_account_id'],
                'paid_to_account_id' => $validated['paid_to_account_id'],
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
                    'message' => 'Payment voucher updated successfully!',
                    'data' => $paymentVoucher->load(['paidFromAccount', 'paidToAccount', 'transaction']),
                ]);
            }

            return redirect()
                ->route('vouchers.payment.show', $paymentVoucher->id)
                ->with('success', 'Payment voucher updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment voucher update error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating payment voucher: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating payment voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payment voucher from storage
     */
    public function destroy(PaymentVoucher $paymentVoucher)
    {
        if ($paymentVoucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be deleted.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete transaction entries
            if ($paymentVoucher->transaction) {
                $paymentVoucher->transaction->entries()->delete();
                $paymentVoucher->transaction->delete();
            }

            // Delete payment voucher
            $paymentVoucher->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment voucher deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment voucher deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a posted payment voucher
     */
    public function cancel(PaymentVoucher $paymentVoucher)
    {
        if (!$paymentVoucher->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Only posted vouchers can be cancelled.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update voucher status
            $paymentVoucher->update(['status' => 'cancelled']);

            // Void the transaction
            if ($paymentVoucher->transaction) {
                $paymentVoucher->transaction->update(['status' => 'voided']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment voucher cancelled successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment voucher cancellation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling payment voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Get payee name
     */
    private function getPayeeName($type, $id)
    {
        switch ($type) {
            case 'vendor':
                $vendor = Vendor::find($id);
                return $vendor ? $vendor->name : 'Unknown Vendor';
            case 'customer':
                $customer = Customer::find($id);
                return $customer ? $customer->name : 'Unknown Customer';
            default:
                return 'N/A';
        }
    }

    /**
     * Helper: Get payee details
     */
    private function getPayeeDetails($type, $id)
    {
        switch ($type) {
            case 'vendor':
                return Vendor::with('ledgerAccount')->find($id);
            case 'customer':
                return Customer::with('ledgerAccount')->find($id);
            default:
                return null;
        }
    }

    /**
     * Get vendor ledger account via AJAX
     */
    public function getVendorAccount(Request $request)
    {
        $vendorId = $request->get('vendor_id');
        $vendor = Vendor::find($vendorId);
        
        if ($vendor && $vendor->ledger_account_id) {
            $account = Account::find($vendor->ledger_account_id);
            return response()->json([
                'success' => true,
                'account_id' => $account->id,
                'account_name' => $account->name,
                'account_code' => $account->code,
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Vendor ledger account not found.',
        ], 404);
    }
/**
 * Print payment voucher
 */
public function print(PaymentVoucher $paymentVoucher)
{
    // Eager load all relationships with null safety
    $paymentVoucher->load([
        'paidFromAccount',
        'paidToAccount',
        'createdBy'
    ]);
    
    // Only load transaction if it exists
    if ($paymentVoucher->transaction_id) {
        $paymentVoucher->load('transaction.entries.account');
    }
    
    // Get payee details based on payee_type
    $payeeDetails = null;
    $balanceInfo = null;
    
    if ($paymentVoucher->payee_type === 'vendor' && $paymentVoucher->payee_id) {
        $payeeDetails = Vendor::with('ledgerAccount')->find($paymentVoucher->payee_id);
        
        if ($payeeDetails) {
            $balanceInfo = $this->getVendorBalanceAtDate(
                $paymentVoucher->payee_id,
                $paymentVoucher->payment_date
            );
        }
    } elseif ($paymentVoucher->payee_type === 'customer' && $paymentVoucher->payee_id) {
        $payeeDetails = Customer::with('ledgerAccount')->find($paymentVoucher->payee_id);
        
        if ($payeeDetails) {
            $balanceInfo = $this->getCustomerBalanceAtDate(
                $paymentVoucher->payee_id,
                $paymentVoucher->payment_date
            );
        }
    }
    
    return view('vouchers.payment.print', compact('paymentVoucher', 'payeeDetails', 'balanceInfo'));
}


/**
 * Calculate vendor balance at a specific date
 */
private function getVendorBalanceAtDate($vendorId, $date)
{
    $vendor = Vendor::find($vendorId);
    
    if (!$vendor || !$vendor->ledger_account_id) {
        return [
            'balance_before' => 0,
            'balance_after' => 0,
            'payment_amount' => 0,
        ];
    }
    
    $account = Account::find($vendor->ledger_account_id);
    
    if (!$account) {
        return [
            'balance_before' => 0,
            'balance_after' => 0,
            'payment_amount' => 0,
        ];
    }
    
    // Calculate balance BEFORE this transaction date
    $creditsBefore = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<', $date);
        })
        ->where('type', 'credit')
        ->sum('amount');
    
    $debitsBefore = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<', $date);
        })
        ->where('type', 'debit')
        ->sum('amount');
    
    $transactionBalanceBefore = $creditsBefore - $debitsBefore;
    
    // Add opening balance
    $balanceBefore = $transactionBalanceBefore;
    if ($vendor->opening_balance > 0) {
        if ($vendor->opening_balance_type === 'credit') {
            $balanceBefore += $vendor->opening_balance;
        } else {
            $balanceBefore -= $vendor->opening_balance;
        }
    }
    
    // Calculate balance AFTER (including this date)
    $creditsAfter = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<=', $date);
        })
        ->where('type', 'credit')
        ->sum('amount');
    
    $debitsAfter = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<=', $date);
        })
        ->where('type', 'debit')
        ->sum('amount');
    
    $transactionBalanceAfter = $creditsAfter - $debitsAfter;
    
    $balanceAfter = $transactionBalanceAfter;
    if ($vendor->opening_balance > 0) {
        if ($vendor->opening_balance_type === 'credit') {
            $balanceAfter += $vendor->opening_balance;
        } else {
            $balanceAfter -= $vendor->opening_balance;
        }
    }
    
    return [
        'balance_before' => round($balanceBefore, 2),
        'balance_after' => round($balanceAfter, 2),
        'payment_amount' => round($balanceAfter - $balanceBefore, 2),
    ];
}
/**
 * Calculate customer balance at a specific date
 */
private function getCustomerBalanceAtDate($customerId, $date)
{
    $customer = Customer::find($customerId);
    
    if (!$customer || !$customer->ledger_account_id) {
        return [
            'balance_before' => 0,
            'balance_after' => 0,
            'payment_amount' => 0,
        ];
    }
    
    $account = Account::find($customer->ledger_account_id);
    
    if (!$account) {
        return [
            'balance_before' => 0,
            'balance_after' => 0,
            'payment_amount' => 0,
        ];
    }
    
    // For customers: Debit increases receivable, Credit decreases receivable
    $debitsBefore = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<', $date);
        })
        ->where('type', 'debit')
        ->sum('amount');
    
    $creditsBefore = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<', $date);
        })
        ->where('type', 'credit')
        ->sum('amount');
    
    $transactionBalanceBefore = $debitsBefore - $creditsBefore;
    
    // Add opening balance
    $balanceBefore = $transactionBalanceBefore;
    if ($customer->opening_balance > 0) {
        if ($customer->opening_balance_type === 'debit') {
            $balanceBefore += $customer->opening_balance;
        } else {
            $balanceBefore -= $customer->opening_balance;
        }
    }
    
    // Calculate balance AFTER
    $debitsAfter = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<=', $date);
        })
        ->where('type', 'debit')
        ->sum('amount');
    
    $creditsAfter = $account->transactionEntries()
        ->whereHas('transaction', function ($query) use ($date) {
            $query->where('type', '!=', 'opening_balance')
                  ->where('date', '<=', $date);
        })
        ->where('type', 'credit')
        ->sum('amount');
    
    $transactionBalanceAfter = $debitsAfter - $creditsAfter;
    
    $balanceAfter = $transactionBalanceAfter;
    if ($customer->opening_balance > 0) {
        if ($customer->opening_balance_type === 'debit') {
            $balanceAfter += $customer->opening_balance;
        } else {
            $balanceAfter -= $customer->opening_balance;
        }
    }
    
    return [
        'balance_before' => round($balanceBefore, 2),
        'balance_after' => round($balanceAfter, 2),
        'payment_amount' => round($balanceAfter - $balanceBefore, 2),
    ];
}

}
