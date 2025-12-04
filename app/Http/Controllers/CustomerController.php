<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use Illuminate\Validation\Rule;
use App\Models\TransactionEntry; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;  // ADD THIS

class CustomerController extends Controller
{
    public function index()
    {
        $groups = CustomerGroup::where('is_active', true)->get();
        return view('customers.index', compact('groups'));
    }

public function getData(Request $request)
{
    $query = Customer::with(['group', 'ledgerAccount'])
        ->select('customers.*')
        ->selectRaw('(SELECT SUM(debit - credit) FROM customer_ledger_transactions WHERE customer_id = customers.id) + 
            CASE WHEN customers.opening_balance_type = "debit" THEN customers.opening_balance ELSE -customers.opening_balance END as current_balance');

    // Search functionality
    if ($request->has('search') && !empty($request->search['value'])) {
        $search = $request->search['value'];
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('customer_code', 'like', "%{$search}%");
        });
    }

    // Filter by group
    if ($request->filled('group_id')) {
        $query->where('customer_group_id', $request->group_id);
    }

    // Filter by status
    if ($request->filled('status')) {
        $query->where('is_active', $request->status);
    }

    // Filter by overdue
    if ($request->filled('overdue') && $request->overdue == 1) {
        $query->whereNotNull('current_due_date')
              ->whereDate('current_due_date', '<', now())
              ->having('current_balance', '>', 0);
    }

    $totalRecords = Customer::count();
    $filteredRecords = $query->count();

    // Sorting
    $orderColumn = $request->order[0]['column'] ?? 0;
    $orderDir = $request->order[0]['dir'] ?? 'asc';
    
    $columns = ['customer_code', 'name', 'phone', 'address', 'current_balance'];
    if (isset($columns[$orderColumn])) {
        $query->orderBy($columns[$orderColumn], $orderDir);
    }

    // Pagination
    $start = $request->start ?? 0;
    $length = $request->length ?? 10;
    $customers = $query->skip($start)->take($length)->get();

    $data = [];
    
    foreach ($customers as $customer) {
        $overdueBadge = '';
        
        // Check for overdue
        if ($customer->current_due_date) {
            $dueDate = \Carbon\Carbon::parse($customer->current_due_date);
            $today = \Carbon\Carbon::today();
            
            // Customer is overdue if: due date is before today AND has positive balance
            if ($dueDate->lt($today) && floatval($customer->current_balance) > 0) {
                $overdueDays = $today->diffInDays($dueDate);
                
                if ($overdueDays > 0) {
                    $overdueBadge = '<br><span class="badge badge-danger">Overdue: ' . $overdueDays . ' ' . 
                                    ($overdueDays == 1 ? 'day' : 'days') . '</span>';
                }
            }
        }

        $balanceClass = $customer->current_balance >= 0 ? 'text-danger' : 'text-success';
        $balanceLabel = $customer->current_balance >= 0 ? 'Dr' : 'Cr';
        $balanceAmount = abs($customer->current_balance);

        $data[] = [
            'id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'name' => $customer->name . ($customer->group ? '<br><small class="text-muted">' . $customer->group->name . '</small>' : ''),
            'phone' => $customer->phone,
            'address' => $customer->address ? (strlen($customer->address) > 50 ? substr($customer->address, 0, 50) . '...' : $customer->address) : '-',
            'current_balance' => '<span class="' . $balanceClass . '">৳ ' . number_format($balanceAmount, 2) . ' ' . $balanceLabel . '</span>' . $overdueBadge,
            'actions' => view('customers.partials.actions', compact('customer'))->render()
        ];
    }

    return response()->json([
        'draw' => intval($request->draw),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
}




    public function create()
    {
        $groups = CustomerGroup::where('is_active', true)->get();
        // Get Sundry Debtors parent account (from your default accounts structure)
        $sundryDebtorsAccount = Account::where('code', '1130')->first(); // Accounts Receivable
        
        return view('customers.create', compact('groups', 'sundryDebtorsAccount'));
    }

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:customers,name',
        'phone' => 'required|string|max:20|unique:customers,phone',
        'email' => 'nullable|email|unique:customers,email',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:100',
        'state' => 'nullable|string|max:100',
        'postal_code' => 'nullable|string|max:20',
        'customer_group_id' => 'nullable|exists:customer_groups,id',
        'opening_balance' => 'required|numeric|min:0',
        'opening_balance_type' => 'required|in:debit,credit',
        'opening_balance_date' => 'nullable|date',
        'credit_limit' => 'nullable|numeric|min:0',
        'credit_period_days' => 'nullable|integer|min:0',
    ]);

    DB::beginTransaction();
    try {
        // Get Sundry Debtors/Accounts Receivable parent account
        $sundryDebtorsAccount = Account::where('code', '1130')
            ->orWhere('name', 'like', '%Accounts Receivable%')
            ->orWhere('name', 'like', '%Sundry Debtors%')
            ->first();
        
        if (!$sundryDebtorsAccount) {
            throw new \Exception('Sundry Debtors/Accounts Receivable account not found. Please create it first with code 1130.');
        }

        // Create ledger account for this customer under Sundry Debtors
        $ledgerAccount = Account::create([
            'code' => 'CUST-' . time() . '-' . rand(100, 999),
            'name' => $request->name,
            'type' => 'asset',
            'description' => 'Customer ledger account - ' . $request->name,
            'parent_account_id' => $sundryDebtorsAccount->id,
            'opening_balance' => $request->opening_balance_type === 'debit' 
                ? $request->opening_balance 
                : -$request->opening_balance,
            'opening_balance_date' => $request->opening_balance_date,
            'is_active' => true,
        ]);

        // Create customer record
        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country ?? 'Bangladesh',
            'customer_group_id' => $request->customer_group_id,
            'ledger_account_id' => $ledgerAccount->id,
            'opening_balance' => $request->opening_balance,
            'opening_balance_type' => $request->opening_balance_type,
            'opening_balance_date' => $request->opening_balance_date ?? now(),
            'credit_limit' => $request->credit_limit ?? 0,
            'credit_period_days' => $request->credit_period_days ?? 0,
            'notes' => $request->notes,
            'is_active' => true,
        ]);

        // Create opening balance transaction if opening balance exists
        if ($request->opening_balance > 0) {
            // Get Opening Balance Equity account (contra account for opening balances)
            $equityAccount = Account::where('code', '3100')
                ->orWhere('name', 'like', '%Opening Balance Equity%')
                ->orWhere('name', 'like', '%Retained Earnings%')
                ->first();
            
            if (!$equityAccount) {
                // Try to find any equity account as fallback
                $equityAccount = Account::where('type', 'equity')
                    ->where('is_active', true)
                    ->first();
            }

            if (!$equityAccount) {
                throw new \Exception('Equity account not found for opening balance contra entry. Please create an Opening Balance Equity account with code 3100.');
            }

            // Create the opening balance transaction
            $transaction = Transaction::create([
                'date' => $request->opening_balance_date ?? now(),
                'reference' => 'OB-' . $customer->customer_code,
                'description' => 'Opening balance for customer: ' . $customer->name,
                'notes' => 'System generated opening balance entry',
                'status' => 'posted',
            ]);

            if ($request->opening_balance_type === 'debit') {
                // Outstanding amount (Customer owes us)
                // Dr. Customer Account (Asset increases)
                $transaction->entries()->create([
                    'account_id' => $ledgerAccount->id,
                    'amount' => $request->opening_balance,
                    'type' => 'debit',
                    'memo' => 'Opening balance - Amount receivable from ' . $customer->name,
                ]);

                // Cr. Opening Balance Equity (Equity increases)
                $transaction->entries()->create([
                    'account_id' => $equityAccount->id,
                    'amount' => $request->opening_balance,
                    'type' => 'credit',
                    'memo' => 'Opening balance contra entry for ' . $customer->name,
                ]);

                // Set due date for outstanding amount
                if ($request->credit_period_days > 0) {
                    $dueDate = now()->addDays($request->credit_period_days);
                    $customer->update([
                        'current_due_date' => $dueDate
                    ]);
                } else {
                    // If no credit period, due immediately
                    $customer->update([
                        'current_due_date' => now()
                    ]);
                }
            } else {
                // Advance payment (Customer paid in advance)
                // Cr. Customer Account (Liability increases - we owe them goods/services)
                $transaction->entries()->create([
                    'account_id' => $ledgerAccount->id,
                    'amount' => $request->opening_balance,
                    'type' => 'credit',
                    'memo' => 'Opening balance - Advance payment from ' . $customer->name,
                ]);

                // Dr. Opening Balance Equity (Equity decreases)
                $transaction->entries()->create([
                    'account_id' => $equityAccount->id,
                    'amount' => $request->opening_balance,
                    'type' => 'debit',
                    'memo' => 'Opening balance contra entry for ' . $customer->name,
                ]);
            }
            \App\Observers\TransactionObserver::syncToCustomerLedger($transaction);

            // The TransactionObserver will automatically create the 
            // CustomerLedgerTransaction entry when the transaction is posted
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully!',
            'customer_id' => $customer->id,
            'redirect_url' => route('customers.show', $customer->id)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Customer creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error creating customer: ' . $e->getMessage()
        ], 500);
    }
}


    public function show(Customer $customer)
    {
        $customer->load(['group', 'ledgerAccount', 'dueExtensions.extendedBy']);
        return view('customers.show', compact('customer'));
    }

    public function ledger(Customer $customer)
    {
        return view('customers.ledger', compact('customer'));
    }

    public function getLedgerData(Request $request, Customer $customer)
    {
        $query = $customer->transactions();

        // Date filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        $totalRecords = $query->count();

        // Sorting
        $orderColumn = $request->order[0]['column'] ?? 0;
        $orderDir = $request->order[0]['dir'] ?? 'desc';
        
        $columns = ['transaction_date', 'voucher_type', 'voucher_number', 'debit', 'credit', 'balance'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $transactions = $query->skip($start)->take($length)->get();

        $data = $transactions->map(function($transaction) {
            return [
                'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
                'voucher_type' => '<span class="badge badge-info">' . $transaction->voucher_type . '</span>',
                'voucher_number' => $transaction->voucher_number,
                'debit' => $transaction->debit > 0 ? '৳ ' . number_format($transaction->debit, 2) : '-',
                'credit' => $transaction->credit > 0 ? '৳ ' . number_format($transaction->credit, 2) : '-',
                'balance' => '<span class="' . ($transaction->balance >= 0 ? 'text-danger' : 'text-success') . '">৳ ' . number_format(abs($transaction->balance), 2) . ' ' . ($transaction->balance >= 0 ? 'Dr' : 'Cr') . '</span>',
                'narration' => $transaction->narration ?? '-',
                'due_date' => $transaction->due_date ? $transaction->due_date->format('d/m/Y') : '-',
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    public function edit(Customer $customer)
    {
        $groups = CustomerGroup::where('is_active', true)->get();
        return view('customers.edit', compact('customer', 'groups'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('customers')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers')->ignore($customer->id)],
            'email' => ['nullable', 'email', Rule::unique('customers')->ignore($customer->id)],
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_period_days' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $customer->update($request->only([
                'name', 'phone', 'email', 'address', 'city', 'state', 
                'postal_code', 'customer_group_id', 'credit_limit', 
                'credit_period_days', 'notes', 'is_active'
            ]));

            // Update ledger account name
            $customer->ledgerAccount->update([
                'name' => $request->name,
                'description' => 'Customer account - ' . $request->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function extendDueDate(Request $request, Customer $customer)
    {
        $request->validate([
            'extended_due_date' => 'required|date|after:' . $customer->current_due_date,
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $customer->extendDueDate($request->extended_due_date, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Due date extended successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error extending due date: ' . $e->getMessage()
            ], 500);
        }
    }

public function destroy(Customer $customer)
{
    try {
        DB::beginTransaction();
        
        // Check if customer has any ledger transactions
        if ($customer->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with existing transactions! Please delete all transactions first or contact administrator.'
            ], 400);
        }

        // Check if the ledger account has any transaction entries
        if ($customer->ledgerAccount->transactionEntries()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with existing accounting entries! This customer has ' . 
                            $customer->ledgerAccount->transactionEntries()->count() . ' transaction(s) recorded.'
            ], 400);
        }

        // Safe to delete - no transactions exist
        $ledgerAccountId = $customer->ledger_account_id;
        
        // Delete customer first (will cascade delete customer_ledger_transactions)
        $customer->delete();
        
        // Then delete the ledger account
        Account::find($ledgerAccountId)->delete();
        
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Customer deletion failed', [
            'customer_id' => $customer->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error deleting customer: ' . $e->getMessage()
        ], 500);
    }
}

public function deactivate(Customer $customer)
{
    try {
        DB::beginTransaction();
        
        $customer->update(['is_active' => false]);
        $customer->ledgerAccount->update(['is_active' => false]);
        
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Customer deactivated successfully! The customer will no longer appear in active lists.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Error deactivating customer: ' . $e->getMessage()
        ], 500);
    }
}
public function getChartData(Customer $customer)
{
    $months = [];
    $sales = [];
    $payments = [];
    $balance = [];
    
    // Get opening balance
    $runningBalance = $customer->opening_balance_type === 'debit' 
        ? $customer->opening_balance 
        : -$customer->opening_balance;

    // Get last 12 months data
    for ($i = 11; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        
        // Get sales (debit) for this month
        $monthSales = $customer->transactions()
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('debit');
            
        // Get payments (credit) for this month
        $monthPayments = $customer->transactions()
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->sum('credit');
        
        // Calculate running balance
        $runningBalance += ($monthSales - $monthPayments);
        
        $months[] = $date->format('M Y');
        $sales[] = round($monthSales, 2);
        $payments[] = round($monthPayments, 2);
        $balance[] = round($runningBalance, 2);
    }

    return response()->json([
        'labels' => $months,
        'sales' => $sales,
        'payments' => $payments,
        'balance' => $balance
    ]);
}



}
