<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $viewType = $request->get('view', 'table');
        
        if ($request->ajax()) {
            return $this->getDataTableData($request);
        }
        
        if ($viewType === 'tree') {
            $accounts = $this->getTreeData();
            return view('accounts.index', compact('accounts', 'viewType'));
        }
        
        $query = Account::with('parentAccount');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        $accounts = $query->paginate(15);
        
        return view('accounts.index', compact('accounts', 'viewType'));
    }

    private function getDataTableData(Request $request)
    {
        $query = Account::with('parentAccount')
            ->select('accounts.*');
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        return DataTables::eloquent($query)
            ->addColumn('type_badge', function($account) {
                $colors = [
                    'asset' => 'primary',
                    'liability' => 'danger',
                    'equity' => 'info',
                    'income' => 'success',
                    'expense' => 'warning'
                ];
                $color = $colors[$account->type] ?? 'secondary';
                return '<span class="badge badge-' . $color . '">' . ucfirst($account->type) . '</span>';
            })
            ->addColumn('parent_name', function($account) {
                return $account->parentAccount ? $account->parentAccount->name : '-';
            })
            ->addColumn('balance_formatted', function($account) {
                $class = $account->current_balance < 0 ? 'text-danger' : '';
                return '<span class="' . $class . '">' . number_format($account->current_balance, 2) . '</span>';
            })
            ->addColumn('status_badge', function($account) {
                return $account->is_active 
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('actions', function ($account) {
                $viewBtn = '<a href="' . route('accounts.show', $account) . '" class="btn btn-info btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>';
                
                $editBtn = '<a href="' . route('accounts.edit', $account) . '" class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>';
                
                $deleteBtn = '<button type="button" class="btn btn-danger btn-sm delete-account" data-id="' . $account->id . '" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>';
                
                return $viewBtn . ' ' . $editBtn . ' ' . $deleteBtn;
            })
            ->rawColumns(['type_badge', 'balance_formatted', 'status_badge', 'actions'])
            ->make(true);
    }

    private function getTreeData()
    {
        $parentAccounts = Account::whereNull('parent_account_id')
            ->orWhere('parent_account_id', 0)
            ->with(['children' => function($query) {
                $query->with('children');
            }])
            ->orderBy('code')
            ->get();
        
        foreach ($parentAccounts as $parent) {
            $parent->total_balance = $this->calculateAccountTotal($parent);
        }
        
        return $parentAccounts;
    }
    
    private function calculateAccountTotal($account)
    {
        $total = $account->current_balance;
        
        if ($account->children && $account->children->count() > 0) {
            foreach ($account->children as $child) {
                $total += $this->calculateAccountTotal($child);
            }
        }
        
        return $total;
    }

    public function create()
    {
        $accountTypes = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expense',
        ];
        
        $parentAccounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        return view('accounts.create', compact('accountTypes', 'parentAccounts'));
    }

    public function store(StoreAccountRequest $request)
    {
        $account = Account::create($request->validated());
        
        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

public function show(Account $account)
{
    $account->load(['parentAccount', 'childAccounts']);
    $currentBalance = $account->getCurrentBalance();
    
    return view('accounts.show', compact('account', 'currentBalance'));
}

// Add this new method
public function getTransactions(Request $request, Account $account)
{
    if ($request->ajax()) {
        $query = DB::table('transaction_entries as te')
            ->join('transactions as t', 'te.transaction_id', '=', 't.id')
            ->where('te.account_id', $account->id)
            ->select(
                't.id as transaction_id',
                't.date',
                't.reference',
                't.description',
                'te.type',
                'te.amount',
                'te.memo',
                't.status'
            )
            ->orderBy('t.date', 'desc')
            ->orderBy('t.id', 'desc');

        // Apply filters
        if ($request->filled('date_from')) {
            $query->where('t.date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('t.date', '<=', $request->date_to);
        }
        
        if ($request->filled('transaction_type')) {
            $query->where('te.type', $request->transaction_type);
        }

        if ($request->filled('other_account_id')) {
            $otherAccountId = $request->other_account_id;
            $query->whereExists(function($q) use ($account, $otherAccountId) {
                $q->select(DB::raw(1))
                  ->from('transaction_entries as te2')
                  ->whereRaw('te2.transaction_id = te.transaction_id')
                  ->where('te2.account_id', '!=', $account->id)
                  ->where('te2.account_id', $otherAccountId);
            });
        }

        return DataTables::of($query)
            ->editColumn('date', function ($entry) {
                return date('d M Y', strtotime($entry->date));
            })
            ->editColumn('reference', function ($entry) {
                return $entry->reference ?? '-';
            })
            ->addColumn('other_account', function ($entry) use ($account) {
                // Get the other account(s) in this transaction
                $otherEntries = DB::table('transaction_entries')
                    ->join('accounts', 'transaction_entries.account_id', '=', 'accounts.id')
                    ->where('transaction_entries.transaction_id', $entry->transaction_id)
                    ->where('transaction_entries.account_id', '!=', $account->id)
                    ->select('accounts.code', 'accounts.name', 'accounts.id')
                    ->get();
                
                if ($otherEntries->count() === 1) {
                    $other = $otherEntries->first();
                    return '<a href="' . route('accounts.show', $other->id) . '">' . 
                           '<span class="badge badge-info">' . e($other->code) . '</span> ' . 
                           e($other->name) . '</a>';
                } elseif ($otherEntries->count() > 1) {
                    return '<span class="text-muted"><i>Split (' . $otherEntries->count() . ' accounts)</i></span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('debit', function ($entry) {
                return $entry->type === 'debit' 
                    ? '<span class="text-danger font-weight-bold">' . number_format($entry->amount, 2) . '</span>' 
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('credit', function ($entry) {
                return $entry->type === 'credit' 
                    ? '<span class="text-success font-weight-bold">' . number_format($entry->amount, 2) . '</span>' 
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($entry) {
                $viewBtn = '<a href="' . route('transactions.show', $entry->transaction_id) . '" 
                            class="btn btn-sm btn-info" title="View Transaction">
                            <i class="fas fa-eye"></i>
                            </a>';
                
                $deleteBtn = '<button type="button" 
                                class="btn btn-sm btn-danger delete-transaction" 
                                data-id="' . $entry->transaction_id . '" 
                                title="Delete Transaction">
                                <i class="fas fa-trash"></i>
                                </button>';
                
                return $viewBtn . ' ' . $deleteBtn;
            })

            ->rawColumns(['other_account', 'debit', 'credit', 'actions'])
            ->make(true);
    }
}




    public function edit(Account $account)
    {
        $accountTypes = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expense',
        ];
        
        $parentAccounts = Account::where('is_active', true)
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();
        
        return view('accounts.edit', compact('account', 'accountTypes', 'parentAccounts'));
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $account->update($request->validated());
        
        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account)
    {
        if ($account->transactionEntries()->count() > 0) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Cannot delete account with existing transactions.');
        }
        
        if ($account->childAccounts()->count() > 0) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Cannot delete account with child accounts.');
        }
        
        $account->delete();
        
        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
