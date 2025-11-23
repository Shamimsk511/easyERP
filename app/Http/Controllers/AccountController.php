<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
class AccountController extends Controller
{
 public function index(Request $request)
    {
        $viewType = $request->get('view', 'table'); // default to table view
        
        if ($request->ajax()) {
            return $this->getDataTableData($request);
        }
        
        if ($viewType === 'tree') {
            $accounts = $this->getTreeData();
            return view('accounts.index', compact('accounts', 'viewType'));
        }
        
        // For table view, get paginated accounts
        $query = Account::with('parentAccount');
        
        // Apply filters
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
        
        // Apply filters from request
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
            ->addColumn('actions', function($account) {
                return '
                    <a href="' . route('accounts.show', $account) . '" class="btn btn-info btn-sm" title="View">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="' . route('accounts.edit', $account) . '" class="btn btn-warning btn-sm" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="' . route('accounts.destroy', $account) . '" method="POST" style="display: inline-block;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this account?\')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                ';
            })
            ->rawColumns(['type_badge', 'balance_formatted', 'status_badge', 'actions'])
            ->make(true);
    }
    
    private function getTreeData()
    {
        // Get all parent accounts (accounts without parent_account_id or parent_account_id = null)
        $parentAccounts = Account::whereNull('parent_account_id')
            ->orWhere('parent_account_id', 0)
            ->with(['children' => function($query) {
                $query->with('children'); // Load nested children
            }])
            ->orderBy('code')
            ->get();
        
        // Calculate totals for each parent account
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
        $account->load(['parentAccount', 'childAccounts', 'transactionEntries.transaction']);
        
        // Get recent transactions
        $recentEntries = $account->transactionEntries()
            ->with('transaction')
            ->latest()
            ->take(20)
            ->get();
        
        $currentBalance = $account->getCurrentBalance();
        
        return view('accounts.show', compact('account', 'recentEntries', 'currentBalance'));
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
        // Check if account has transactions
        if ($account->transactionEntries()->count() > 0) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Cannot delete account with existing transactions.');
        }
        
        // Check if account has child accounts
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
