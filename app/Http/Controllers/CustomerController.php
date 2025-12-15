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
use App\Models\Transaction;
use App\Models\CustomerLedgerTransaction;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index()
    {
        $groups = CustomerGroup::where('is_active', true)->get();
        return view('customers.index', compact('groups'));
    }

    /**
     * DataTable server-side data
     */
    public function getData(Request $request)
    {
        $query = Customer::with(['group', 'ledgerAccount'])
            ->select('customers.*')
            ->selectRaw('(SELECT COALESCE(SUM(debit - credit), 0) FROM customer_ledger_transactions WHERE customer_id = customers.id) + 
                CASE WHEN customers.opening_balance_type = "debit" THEN customers.opening_balance ELSE -customers.opening_balance END as calculated_balance');

        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('group_id')) {
            $query->where('customer_group_id', $request->group_id);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }
        if ($request->filled('overdue') && $request->overdue == 1) {
            $query->whereNotNull('current_due_date')
                  ->whereDate('current_due_date', '<', now())
                  ->having('calculated_balance', '>', 0);
        }

        $totalRecords = Customer::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->order[0]['column'] ?? 0;
        $orderDir = $request->order[0]['dir'] ?? 'asc';
        $columns = ['customer_code', 'name', 'phone', 'address', 'calculated_balance'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 25;
        $customers = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($customers as $customer) {
            $balance = $customer->current_balance;
            $balanceClass = $balance >= 0 ? 'text-danger' : 'text-success';
            $balanceLabel = $balance >= 0 ? 'Dr' : 'Cr';
            
            $overdueBadge = '';
            if ($customer->current_due_date && $balance > 0) {
                $dueDate = \Carbon\Carbon::parse($customer->current_due_date);
                if ($dueDate->lt(now())) {
                    $overdueBadge = ' <span class="badge badge-danger">Overdue</span>';
                }
            }

            $data[] = [
                'customer_code' => $customer->customer_code,
                'name' => '<a href="' . route('customers.show', $customer) . '">' . $customer->name . '</a>' . 
                         ($customer->group ? '<br><small class="text-muted">' . $customer->group->name . '</small>' : ''),
                'phone' => $customer->phone,
                'address' => $customer->address ? (strlen($customer->address) > 40 ? substr($customer->address, 0, 40) . '...' : $customer->address) : '-',
                'current_balance' => '<span class="' . $balanceClass . '">৳ ' . number_format(abs($balance), 2) . ' ' . $balanceLabel . '</span>' . $overdueBadge,
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
        return view('customers.create', compact('groups'));
    }

    /**
     * Store new customer (AJAX)
     */
    public function store(Request $request): JsonResponse
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
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Get Sundry Debtors parent account
            $sundryDebtorsAccount = Account::where('code', '1130')
                ->orWhere('name', 'like', '%Accounts Receivable%')
                ->orWhere('name', 'like', '%Sundry Debtors%')
                ->first();

            if (!$sundryDebtorsAccount) {
                throw new \Exception('Sundry Debtors/Accounts Receivable account not found (code 1130).');
            }

            // Create ledger account
            $ledgerAccount = Account::create([
                'code' => 'CUST-' . time() . '-' . rand(100, 999),
                'name' => $request->name,
                'type' => 'asset',
                'description' => 'Customer ledger - ' . $request->name,
                'parent_account_id' => $sundryDebtorsAccount->id,
                'opening_balance' => $request->opening_balance_type === 'debit' ? $request->opening_balance : -$request->opening_balance,
                'opening_balance_date' => $request->opening_balance_date,
                'is_active' => true,
            ]);

            // Create customer
            $customer = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => 'Bangladesh',
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

            // Create opening balance transaction if exists
            if ($request->opening_balance > 0) {
                $this->createOpeningBalanceTransaction($customer, $ledgerAccount, $request);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully!',
                'redirect_url' => route('customers.show', $customer),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Customer $customer)
    {
        $customer->load(['group', 'ledgerAccount', 'transactions' => function ($q) {
            $q->orderBy('transaction_date', 'desc')->limit(10);
        }, 'dueExtensions']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $groups = CustomerGroup::where('is_active', true)->get();
        return view('customers.edit', compact('customer', 'groups'));
    }

    /**
     * Update customer (AJAX)
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('customers')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers')->ignore($customer->id)],
            'email' => ['nullable', 'email', Rule::unique('customers')->ignore($customer->id)],
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_period_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'customer_group_id' => $request->customer_group_id,
                'credit_limit' => $request->credit_limit ?? 0,
                'credit_period_days' => $request->credit_period_days ?? 0,
                'notes' => $request->notes,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Update ledger account name if changed
            if ($customer->ledgerAccount && $customer->wasChanged('name')) {
                $customer->ledgerAccount->update(['name' => $request->name]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully!',
                'redirect_url' => route('customers.show', $customer),
            ]);

        } catch (\Exception $e) {
            Log::error('Customer update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete customer (AJAX)
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // Check if customer has transactions
        $hasTransactions = CustomerLedgerTransaction::where('customer_id', $customer->id)->exists();

        if ($hasTransactions) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with transaction history. Deactivate instead.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Delete ledger account
            if ($customer->ledgerAccount) {
                $customer->ledgerAccount->delete();
            }

            $customer->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ledger page
     */
    public function ledger(Customer $customer)
    {
        return view('customers.ledger', compact('customer'));
    }

    /**
     * Ledger data for DataTable
     */
    public function getLedgerData(Request $request, Customer $customer)
    {
        $query = CustomerLedgerTransaction::where('customer_id', $customer->id);

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')
                              ->orderBy('id', 'asc')
                              ->get();

        // Calculate running balance
        $runningBalance = $customer->opening_balance_type === 'debit' 
            ? $customer->opening_balance 
            : -$customer->opening_balance;

        $data = $transactions->map(function ($txn) use (&$runningBalance) {
            $runningBalance += ($txn->debit - $txn->credit);
            return [
                'transaction_date' => $txn->transaction_date->format('d M Y'),
                'voucher_type' => $txn->voucher_type,
                'voucher_number' => $txn->voucher_number,
                'debit' => $txn->debit > 0 ? number_format($txn->debit, 2) : '-',
                'credit' => $txn->credit > 0 ? number_format($txn->credit, 2) : '-',
                'balance' => number_format(abs($runningBalance), 2) . ' ' . ($runningBalance >= 0 ? 'Dr' : 'Cr'),
                'narration' => $txn->narration,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $transactions->count(),
            'recordsFiltered' => $transactions->count(),
            'data' => $data,
        ]);
    }

    /**
     * Extend due date
     */
    public function extendDueDate(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'new_due_date' => 'required|date|after:today',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $oldDueDate = $customer->current_due_date;

            $customer->dueExtensions()->create([
                'old_due_date' => $oldDueDate,
                'new_due_date' => $request->new_due_date,
                'reason' => $request->reason,
                'extended_by' => auth()->id(),
            ]);

            $customer->update(['current_due_date' => $request->new_due_date]);

            return response()->json([
                'success' => true,
                'message' => 'Due date extended successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend due date: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate customer
     */
    public function deactivate(Customer $customer): JsonResponse
    {
        $customer->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Customer deactivated successfully!',
        ]);
    }

    /**
     * Chart data for customer show page
     */
    public function getChartData(Customer $customer)
    {
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();

        $monthlyData = CustomerLedgerTransaction::where('customer_id', $customer->id)
            ->where('transaction_date', '>=', $sixMonthsAgo)
            ->selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as month, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'labels' => $monthlyData->pluck('month'),
            'debits' => $monthlyData->pluck('total_debit'),
            'credits' => $monthlyData->pluck('total_credit'),
        ]);
    }

    /**
     * Create opening balance transaction
     */
    private function createOpeningBalanceTransaction(Customer $customer, Account $ledgerAccount, Request $request): void
    {
        $equityAccount = Account::where('code', '3100')
            ->orWhere('name', 'like', '%Opening Balance Equity%')
            ->orWhere('name', 'like', '%Retained Earnings%')
            ->first();

        if (!$equityAccount) {
            $equityAccount = Account::where('type', 'equity')->where('is_active', true)->first();
        }

        if (!$equityAccount) {
            throw new \Exception('Equity account not found for opening balance.');
        }

        $transaction = Transaction::create([
            'date' => $request->opening_balance_date ?? now(),
            'reference' => 'OB-' . $customer->customer_code,
            'description' => 'Opening balance for customer: ' . $customer->name,
            'status' => 'posted',
        ]);

        if ($request->opening_balance_type === 'debit') {
            $transaction->entries()->create([
                'account_id' => $ledgerAccount->id,
                'amount' => $request->opening_balance,
                'type' => 'debit',
                'memo' => 'Opening balance receivable',
            ]);
            $transaction->entries()->create([
                'account_id' => $equityAccount->id,
                'amount' => $request->opening_balance,
                'type' => 'credit',
                'memo' => 'Opening balance contra',
            ]);

            if ($request->credit_period_days > 0) {
                $customer->update(['current_due_date' => now()->addDays($request->credit_period_days)]);
            } else {
                $customer->update(['current_due_date' => now()]);
            }
        } else {
            $transaction->entries()->create([
                'account_id' => $ledgerAccount->id,
                'amount' => $request->opening_balance,
                'type' => 'credit',
                'memo' => 'Opening balance advance',
            ]);
            $transaction->entries()->create([
                'account_id' => $equityAccount->id,
                'amount' => $request->opening_balance,
                'type' => 'debit',
                'memo' => 'Opening balance contra',
            ]);
        }
    }
}