<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
{
    $query = Account::with('parentAccount');
    
    // Filter by type
    if ($request->filled('type')) {
        $query->where('type', $request->type);
    }
    
    // Filter by status
    if ($request->filled('is_active')) {
        $query->where('is_active', $request->is_active);
    }
    
    // Search
    if ($request->filled('search')) {
        $query->where(function($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('code', 'like', '%' . $request->search . '%');
        });
    }
    
    $accounts = $query->orderBy('code')->paginate(15);
    
    // Calculate current balance for each account
    foreach ($accounts as $account) {
        $account->current_balance = $account->getCurrentBalance();
    }
    
    return view('accounts.index', compact('accounts'));
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
