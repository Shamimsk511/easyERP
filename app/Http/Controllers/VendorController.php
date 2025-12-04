<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors with server-side DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::with('ledgerAccount')->select('vendors.*');
            
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('ledger_account', function ($row) {
                    if ($row->ledgerAccount) {
                        return '<a href="' . route('accounts.show', $row->ledger_account_id) . '">' .
                               '<span class="badge badge-info">' . $row->ledgerAccount->name . '</span></a>';
                    }
                    return '<span class="badge badge-secondary">Not Set</span>';
                })
                ->addColumn('opening_balance', function ($row) {
                    if ($row->opening_balance > 0) {
                        $type = ucfirst($row->opening_balance_type);
                        $badge = $row->opening_balance_type === 'credit' ? 'badge-success' : 'badge-warning';
                        return '<span class="badge ' . $badge . '">' . 
                               '৳ ' . number_format($row->opening_balance, 2) . ' (' . $type . ')' .
                               '</span>';
                    }
                    return '<span class="badge badge-secondary">৳ 0.00</span>';
                })
                ->addColumn('current_balance', function ($row) {
                    $balance = $this->calculateVendorBalance($row);
                    $badge = $balance > 0 ? 'badge-danger' : 'badge-success';
                    return '<span class="badge ' . $badge . '">' . 
                           '৳ ' . number_format(abs($balance), 2) .
                           '</span>';
                })
                ->addColumn('action', function ($row) {
    $viewBtn = '<a href="' . route('vendors.show', $row->id) . '" class="btn btn-info btn-sm" title="View">
                    <i class="fas fa-eye"></i>
                </a>';
    
    $paymentBtn = '<a href="' . route('vouchers.payment.create', ['payee_type' => 'vendor', 'vendor_id' => $row->id]) . '" class="btn btn-success btn-sm" title="Make Payment">
                    <i class="fas fa-money-bill-wave"></i>
                </a>';
    
    $editBtn = '<a href="' . route('vendors.edit', $row->id) . '" class="btn btn-primary btn-sm" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>';
    
    $deleteBtn = '<button type="button" class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>';
    
    return '<div class="btn-group">' . $viewBtn . ' ' . $paymentBtn . ' ' . $editBtn . ' ' . $deleteBtn . '</div>';
})

                ->rawColumns(['ledger_account', 'opening_balance', 'current_balance', 'action'])
                ->make(true);
        }
        
        return view('vendors.index');
    }

    /**
     * Show the form for creating a new vendor
     */
    public function create()
    {
        return view('vendors.create');
    }

    /**
     * Store a newly created vendor in storage
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:vendors,name',
        'description' => 'nullable|string',
        'opening_balance' => 'nullable|numeric|min:0',
        'opening_balance_type' => 'required_with:opening_balance|in:debit,credit',
        'opening_balance_date' => 'nullable|date',
        'is_active' => 'nullable|boolean', // Add this
    ]);

    DB::beginTransaction();
    
    try {
        // Create Sundry Creditor ledger account for vendor
        $account = Account::create([
            'name' => $validated['name'],
            'code' => 'VEND-' . strtoupper(substr($validated['name'], 0, 4)) . '-' . rand(1000, 9999),
            'type' => 'liability',
            'description' => 'Vendor Account: ' . $validated['name'],
            'is_active' => true,
            'parent_account_id' => null,
        ]);

        // Create vendor and link to ledger account
        $vendor = Vendor::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'ledger_account_id' => $account->id,
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'opening_balance_type' => $validated['opening_balance_type'] ?? 'credit',
            'opening_balance_date' => $validated['opening_balance_date'] ?? now(),
            'is_active' => $validated['is_active'] ?? true, // Add this
        ]);

        // Create opening balance transaction entry if opening balance exists
        if (!empty($validated['opening_balance']) && $validated['opening_balance'] > 0) {
            $this->createOpeningBalanceTransaction($vendor);
        }

        DB::commit();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor created successfully!',
                'data' => $vendor->load('ledgerAccount'),
            ]);
        }

        return redirect()
            ->route('vendors.index')
            ->with('success', 'Vendor created successfully!');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Vendor creation error: ' . $e->getMessage());
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating vendor: ' . $e->getMessage(),
            ], 500);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Error creating vendor: ' . $e->getMessage());
    }
}


    /**
     * Display the specified vendor
     */
    public function show(Vendor $vendor)
    {
        $vendor->load(['ledgerAccount', 'purchaseOrders']);
        
        // Calculate current balance
        $currentBalance = $this->calculateVendorBalance($vendor);
        
        return view('vendors.show', compact('vendor', 'currentBalance'));
    }

    /**
     * Show the form for editing the specified vendor
     */
    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    /**
     * Update the specified vendor in storage
     */
public function update(Request $request, Vendor $vendor)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:vendors,name,' . $vendor->id,
        'description' => 'nullable|string',
        'opening_balance' => 'nullable|numeric|min:0',
        'opening_balance_type' => 'required_with:opening_balance|in:debit,credit',
        'opening_balance_date' => 'nullable|date',
        'is_active' => 'nullable|boolean', // Add this
    ]);

    DB::beginTransaction();
    
    try {
        $oldOpeningBalance = $vendor->opening_balance;
        $oldOpeningBalanceType = $vendor->opening_balance_type;

        // Update vendor
        $vendor->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'opening_balance_type' => $validated['opening_balance_type'] ?? 'credit',
            'opening_balance_date' => $validated['opening_balance_date'] ?? $vendor->opening_balance_date,
            'is_active' => $validated['is_active'] ?? true, // Add this
        ]);

        // Update linked ledger account name and status
        if ($vendor->ledgerAccount) {
            $vendor->ledgerAccount->update([
                'name' => $validated['name'],
                'description' => 'Vendor Account: ' . $validated['name'],
                'is_active' => $validated['is_active'] ?? true, // Add this
            ]);
        }

        // Handle opening balance transaction changes
        $newOpeningBalance = $validated['opening_balance'] ?? 0;
        $newOpeningBalanceType = $validated['opening_balance_type'] ?? 'credit';

        if ($oldOpeningBalance != $newOpeningBalance || $oldOpeningBalanceType != $newOpeningBalanceType) {
            // Delete old opening balance transaction
            $this->deleteOpeningBalanceTransaction($vendor);
            
            // Create new opening balance transaction if amount > 0
            if ($newOpeningBalance > 0) {
                $this->createOpeningBalanceTransaction($vendor);
            }
        }

        DB::commit();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully!',
                'data' => $vendor->load('ledgerAccount'),
            ]);
        }

        return redirect()
            ->route('vendors.index')
            ->with('success', 'Vendor updated successfully!');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Vendor update error: ' . $e->getMessage());
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating vendor: ' . $e->getMessage(),
            ], 500);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Error updating vendor: ' . $e->getMessage());
    }
}


    /**
     * Remove the specified vendor from storage
     */
    public function destroy(Vendor $vendor)
    {
        try {
            // Check if vendor has purchase orders
            if ($vendor->purchaseOrders()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete vendor with existing purchase orders.',
                ], 422);
            }

            DB::beginTransaction();

            // Delete opening balance transaction
            $this->deleteOpeningBalanceTransaction($vendor);

            // Store ledger account ID before deleting vendor
            $ledgerAccountId = $vendor->ledger_account_id;

            // Delete vendor
            $vendor->delete();

            // Optionally delete the ledger account if no transactions exist
            if ($ledgerAccountId) {
                $account = Account::find($ledgerAccountId);
                if ($account && $account->transactionEntries()->count() === 0) {
                    $account->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Vendor deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting vendor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get vendor balance
     */
/**
 * Get vendor balance for AJAX requests
 */
public function getBalance(Vendor $vendor)
{
    try {
        // Use the model's accessor which properly calculates balance from ledger
        $balance = $vendor->current_balance;
        
        return response()->json([
            'success' => true,
            'balance' => $balance,
            'formatted' => '৳ ' . number_format(abs($balance), 2),
            'type' => $balance > 0 ? 'payable' : ($balance < 0 ? 'receivable' : 'zero')
        ]);
        
    } catch (\Exception $e) {
        Log::error('Vendor balance fetch error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'balance' => 0,
            'message' => 'Error fetching vendor balance'
        ], 500);
    }
}


    /**
     * Calculate vendor balance including opening balance
     */
private function calculateVendorBalance(Vendor $vendor): float
{
    $balance = 0;
    
    if ($vendor->ledger_account_id) {
        $account = Account::find($vendor->ledger_account_id);
        
        if ($account) {
            // Get transaction entries (excluding opening balance transactions to avoid double counting)
            $credits = $account->transactionEntries()
                ->whereHas('transaction', function ($query) {
                    $query->where('type', '!=', 'opening_balance');
                })
                ->where('type', 'credit')
                ->sum('amount');
            
            $debits = $account->transactionEntries()
                ->whereHas('transaction', function ($query) {
                    $query->where('type', '!=', 'opening_balance');
                })
                ->where('type', 'debit')
                ->sum('amount');
            
            // Calculate transaction balance (excluding opening balance)
            $transactionBalance = $credits - $debits;
            
            // Add opening balance based on type
            if ($vendor->opening_balance > 0) {
                if ($vendor->opening_balance_type === 'credit') {
                    // Credit opening balance means we owe vendor
                    $balance = $transactionBalance + $vendor->opening_balance;
                } else {
                    // Debit opening balance means vendor owes us
                    $balance = $transactionBalance - $vendor->opening_balance;
                }
            } else {
                $balance = $transactionBalance;
            }
        }
    }
    
    return round($balance, 2);
}

    /**
     * Create opening balance transaction
     */
/**
 * Create opening balance transaction
 */
private function createOpeningBalanceTransaction(Vendor $vendor): void
{
    if (!$vendor->ledger_account_id || $vendor->opening_balance <= 0) {
        return;
    }

    // Get or create Capital Account for contra entry
    $capitalAccount = Account::firstOrCreate(
        ['code' => 'CAPITAL-001'],
        [
            'name' => 'Capital Account',
            'type' => 'equity',
            'description' => 'Owner Capital Account for Opening Balances',
            'is_active' => true,
        ]
    );

    // Create transaction
    $transaction = Transaction::create([
        'date' => $vendor->opening_balance_date ?? now(),
        'type' => 'opening_balance',
        'reference' => 'OB-VEND-' . $vendor->id . '-' . time(),
        'description' => 'Opening Balance for Vendor: ' . $vendor->name,
    ]);

    // Create transaction entries based on opening balance type
    if ($vendor->opening_balance_type === 'credit') {
        // Credit vendor account (liability increases)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $vendor->ledger_account_id,
            'type' => 'credit',
            'amount' => $vendor->opening_balance,
            'description' => 'Opening Balance - Vendor Credit',
        ]);

        // Debit capital account
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $capitalAccount->id,
            'type' => 'debit',
            'amount' => $vendor->opening_balance,
            'description' => 'Opening Balance - Contra Entry',
        ]);
    } else {
        // Debit vendor account (vendor owes us)
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $vendor->ledger_account_id,
            'type' => 'debit',
            'amount' => $vendor->opening_balance,
            'description' => 'Opening Balance - Vendor Debit',
        ]);

        // Credit capital account
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $capitalAccount->id,
            'type' => 'credit',
            'amount' => $vendor->opening_balance,
            'description' => 'Opening Balance - Contra Entry',
        ]);
    }
}


    /**
     * Delete opening balance transaction
     */
private function deleteOpeningBalanceTransaction(Vendor $vendor): void
{
    if (!$vendor->ledger_account_id) {
        return;
    }

    $transaction = Transaction::where('type', 'opening_balance')
        ->where('reference', 'LIKE', 'OB-VEND-' . $vendor->id . '-%')
        ->first();

    if ($transaction) {
        // Delete transaction entries first
        $transaction->entries()->delete();
        // Delete transaction
        $transaction->delete();
    }
}
}
