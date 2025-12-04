<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('entries.account');
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }
        
        $transactions = $query->latest('date')->paginate(15);
        
        return view('transactions.index', compact('transactions'));
    }

public function create(Request $request)
{
    $accounts = Account::where('is_active', true)
        ->orderBy('code')
        ->get();
    
    $preselectedAccountId = $request->query('account_id'); // Get from URL
    
    return view('transactions.create', compact('accounts', 'preselectedAccountId'));
}


    public function store(StoreTransactionRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Create transaction
            $transaction = Transaction::create([
                'date' => $request->date,
                'reference' => $request->reference,
                'description' => $request->description,
                'notes' => $request->notes,
                'status' => $request->status ?? 'posted',
            ]);
            
            // Create entries
            foreach ($request->entries as $entry) {
                $transaction->entries()->create([
                    'account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                    'memo' => $entry['memo'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', 'Transaction created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating transaction: ' . $e->getMessage());
        }
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('entries.account');
        
        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        // Only allow editing draft transactions
        if ($transaction->status !== 'draft') {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', 'Only draft transactions can be edited.');
        }
        
        $transaction->load('entries.account');
        
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        return view('transactions.edit', compact('transaction', 'accounts'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        // Only allow updating draft transactions
        if ($transaction->status !== 'draft') {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', 'Only draft transactions can be updated.');
        }
        
        DB::beginTransaction();
        
        try {
            // Update transaction
            $transaction->update([
                'date' => $request->date,
                'reference' => $request->reference,
                'description' => $request->description,
                'notes' => $request->notes,
                'status' => $request->status ?? 'posted',
            ]);
            
            // Delete old entries
            $transaction->entries()->delete();
            
            // Create new entries
            foreach ($request->entries as $entry) {
                $transaction->entries()->create([
                    'account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                    'memo' => $entry['memo'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', 'Transaction updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating transaction: ' . $e->getMessage());
        }
    }

public function destroy(Transaction $transaction)
{
    try {
        // Delete transaction (will cascade delete all entries)
        $transaction->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting transaction: ' . $e->getMessage()
        ], 500);
    }
}
    
    // Additional method to void a transaction
    public function void(Transaction $transaction)
    {
        if ($transaction->status === 'voided') {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', 'Transaction is already voided.');
        }
        
        $transaction->void();
        
        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction voided successfully.');
    }

    public function createForCustomer(Customer $customer)
{
    $accounts = Account::where('is_active', true)
        ->orderBy('code')
        ->get();
    
    $preselectedAccountId = $customer->ledger_account_id;
    
    return view('transactions.create', compact('accounts', 'preselectedAccountId', 'customer'));
}
}
