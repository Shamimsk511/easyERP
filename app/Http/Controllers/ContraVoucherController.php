<?php

namespace App\Http\Controllers;

use App\Models\ContraVoucher;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Observers\TransactionObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ContraVoucherController extends Controller
{
    /**
     * Display a listing of contra vouchers with server-side DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ContraVoucher::with(['fromAccount', 'toAccount'])
                ->select('contra_vouchers.*');
            
            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('contra_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('contra_date', '<=', $request->end_date);
            }
            
            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by transfer method
            if ($request->filled('transfer_method')) {
                $query->where('transfer_method', $request->transfer_method);
            }
            
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('voucher_number', function ($row) {
                    return '<a href="' . route('vouchers.contra.show', $row->id) . '">' .
                           '<strong>' . $row->voucher_number . '</strong></a>';
                })
                ->addColumn('contra_date', function ($row) {
                    return $row->contra_date->format('d M Y');
                })
                ->addColumn('from_account', function ($row) {
                    return '<span class="badge badge-danger">' . 
                           $row->fromAccount->name . '</span>';
                })
                ->addColumn('to_account', function ($row) {
                    return '<span class="badge badge-success">' . 
                           $row->toAccount->name . '</span>';
                })
                ->addColumn('amount', function ($row) {
                    return '<strong>' . number_format($row->amount, 2) . '</strong>';
                })
                ->addColumn('transfer_method', function ($row) {
                    $badges = [
                        'cash' => 'badge-success',
                        'bank_transfer' => 'badge-primary',
                        'cheque' => 'badge-info',
                        'online' => 'badge-warning',
                    ];
                    $badge = $badges[$row->transfer_method] ?? 'badge-secondary';
                    return '<span class="badge ' . $badge . '">' . 
                           ucfirst(str_replace('_', ' ', $row->transfer_method)) . '</span>';
                })
                ->addColumn('status', function ($row) {
                    $badges = [
                        'draft' => 'badge-secondary',
                        'posted' => 'badge-success',
                        'cancelled' => 'badge-danger',
                    ];
                    $badge = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $badge . '">' . 
                           ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $viewBtn = '<a href="' . route('vouchers.contra.show', $row->id) . '" ' .
                               'class="btn btn-info btn-sm" title="View">' .
                               '<i class="fas fa-eye"></i></a>';
                    
                    $editBtn = '';
                    if ($row->can_edit) {
                        $editBtn = '<a href="' . route('vouchers.contra.edit', $row->id) . '" ' .
                                   'class="btn btn-primary btn-sm" title="Edit">' .
                                   '<i class="fas fa-edit"></i></a>';
                    }
                    
                    $cancelBtn = '';
                    if ($row->can_cancel) {
                        $cancelBtn = '<button type="button" class="btn btn-warning btn-sm cancel-btn" ' .
                                     'data-id="' . $row->id . '" title="Cancel">' .
                                     '<i class="fas fa-ban"></i></button>';
                    }
                    
                    $deleteBtn = '';
                    if ($row->status === 'draft') {
                        $deleteBtn = '<button type="button" class="btn btn-danger btn-sm delete-btn" ' .
                                     'data-id="' . $row->id . '" title="Delete">' .
                                     '<i class="fas fa-trash"></i></button>';
                    }
                    
                    return '<div class="btn-group">' . $viewBtn . $editBtn . $cancelBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['voucher_number', 'from_account', 'to_account', 'amount', 'transfer_method', 'status', 'action'])
                ->make(true);
        }
        
        return view('vouchers.contra.index');
    }

    /**
     * Show the form for creating a new contra voucher
     */
    public function create(Request $request)
    {
        // Get Cash and Bank accounts only
        $cashBankAccounts = Account::where('is_active', true)
            ->where(function($query) {
                $query->where('name', 'LIKE', '%Cash%')
                      ->orWhere('name', 'LIKE', '%Bank%')
                      ->orWhere('code', 'LIKE', '11%'); // Asset accounts starting with 11
            })
            ->orderBy('name')
            ->get();
        
        // Pre-fill from request parameters
        $preselectedFromAccount = null;
        $preselectedToAccount = null;
        
        if ($request->filled('from_account_id')) {
            $preselectedFromAccount = Account::find($request->from_account_id);
        }
        
        if ($request->filled('to_account_id')) {
            $preselectedToAccount = Account::find($request->to_account_id);
        }
        
        // Generate voucher number
        $voucherNumber = ContraVoucher::generateVoucherNumber();
        
        return view('vouchers.contra.create', compact(
            'cashBankAccounts',
            'preselectedFromAccount',
            'preselectedToAccount',
            'voucherNumber'
        ));
    }

    /**
     * Store a newly created contra voucher in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contra_date' => 'required|date',
            'transfer_method' => 'required|in:cash,bank_transfer,cheque,online',
            'amount' => 'required|numeric|min:0.01',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
        ]);
        
        // Validate accounts are different
        if ($validated['from_account_id'] === $validated['to_account_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Source and destination accounts must be different.',
            ], 422);
        }
        
        // Validate both accounts are cash/bank accounts
        $fromAccount = Account::find($validated['from_account_id']);
        $toAccount = Account::find($validated['to_account_id']);
        
        if (!$this->isCashOrBankAccount($fromAccount) || !$this->isCashOrBankAccount($toAccount)) {
            return response()->json([
                'success' => false,
                'message' => 'Contra vouchers can only be used between Cash and Bank accounts.',
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Generate voucher number
            $voucherNumber = ContraVoucher::generateVoucherNumber();
            
            // Create transaction for double-entry
            $transaction = Transaction::create([
                'date' => $validated['contra_date'],
                'type' => 'contra',
                'reference' => $voucherNumber,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);
            
            Log::info('Contra Voucher: Transaction created', [
                'transaction_id' => $transaction->id,
                'reference' => $voucherNumber,
                'status' => $validated['status']
            ]);
            
            // Create transaction entries (double-entry bookkeeping)
            
            // 1. Debit To Account (increases cash/bank)
            $debitEntry = TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $validated['to_account_id'],
                'type' => 'debit',
                'amount' => $validated['amount'],
                'memo' => 'Contra: Transfer received in ' . $toAccount->name,
            ]);
            
            Log::info('Contra Voucher: Debit entry created', [
                'entry_id' => $debitEntry->id,
                'account' => $toAccount->name,
                'amount' => $validated['amount']
            ]);
            
            // 2. Credit From Account (decreases cash/bank)
            $creditEntry = TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $validated['from_account_id'],
                'type' => 'credit',
                'amount' => $validated['amount'],
                'memo' => 'Contra: Transfer sent from ' . $fromAccount->name,
            ]);
            
            Log::info('Contra Voucher: Credit entry created', [
                'entry_id' => $creditEntry->id,
                'account' => $fromAccount->name,
                'amount' => $validated['amount']
            ]);
            
            // Create contra voucher
            $contraVoucher = ContraVoucher::create([
                'voucher_number' => $voucherNumber,
                'contra_date' => $validated['contra_date'],
                'amount' => $validated['amount'],
                'from_account_id' => $validated['from_account_id'],
                'to_account_id' => $validated['to_account_id'],
                'transaction_id' => $transaction->id,
                'transfer_method' => $validated['transfer_method'],
                'cheque_number' => $validated['cheque_number'] ?? null,
                'cheque_date' => $validated['cheque_date'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
                'created_by' => Auth::id(),
            ]);
            
            Log::info('Contra Voucher: Voucher created', [
                'voucher_id' => $contraVoucher->id,
                'voucher_number' => $voucherNumber
            ]);
            
            // Manually trigger observer sync if status is posted
            if ($transaction->status === 'posted') {
                Log::info('Contra Voucher: Triggering observer sync', [
                    'transaction_id' => $transaction->id
                ]);
                
                TransactionObserver::syncToCustomerLedger($transaction);
                
                Log::info('Contra Voucher: Observer sync completed');
            }
            
            DB::commit();
            
            Log::info('Contra Voucher: Transaction committed successfully', [
                'voucher_id' => $contraVoucher->id
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contra voucher created successfully!',
                    'data' => $contraVoucher->load(['fromAccount', 'toAccount', 'transaction']),
                ]);
            }
            
            return redirect()
                ->route('vouchers.contra.show', $contraVoucher->id)
                ->with('success', 'Contra voucher created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contra voucher creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating contra voucher: ' . $e->getMessage(),
                ], 500);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating contra voucher: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified contra voucher
     */
    public function show(ContraVoucher $contraVoucher)
    {
        $contraVoucher->load(['fromAccount', 'toAccount', 'transaction.entries.account', 'createdBy']);
        
        return view('vouchers.contra.show', compact('contraVoucher'));
    }

    /**
     * Show the form for editing the specified contra voucher
     */
    public function edit(ContraVoucher $contraVoucher)
    {
        if (!$contraVoucher->can_edit) {
            return redirect()
                ->route('vouchers.contra.show', $contraVoucher->id)
                ->with('error', 'Only draft vouchers can be edited.');
        }
        
        $contraVoucher->load(['fromAccount', 'toAccount']);
        
        // Get Cash and Bank accounts
        $cashBankAccounts = Account::where('is_active', true)
            ->where(function($query) {
                $query->where('name', 'LIKE', '%Cash%')
                      ->orWhere('name', 'LIKE', '%Bank%')
                      ->orWhere('code', 'LIKE', '11%');
            })
            ->orderBy('name')
            ->get();
        
        return view('vouchers.contra.edit', compact('contraVoucher', 'cashBankAccounts'));
    }

    /**
     * Update the specified contra voucher in storage
     */
    public function update(Request $request, ContraVoucher $contraVoucher)
    {
        if (!$contraVoucher->can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be edited.',
            ], 422);
        }
        
        $validated = $request->validate([
            'contra_date' => 'required|date',
            'transfer_method' => 'required|in:cash,bank_transfer,cheque,online',
            'amount' => 'required|numeric|min:0.01',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id',
            'cheque_number' => 'nullable|string|max:100',
            'cheque_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
        ]);
        
        // Validate accounts are different
        if ($validated['from_account_id'] === $validated['to_account_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Source and destination accounts must be different.',
            ], 422);
        }
        
        // Validate both accounts are cash/bank accounts
        $fromAccount = Account::find($validated['from_account_id']);
        $toAccount = Account::find($validated['to_account_id']);
        
        if (!$this->isCashOrBankAccount($fromAccount) || !$this->isCashOrBankAccount($toAccount)) {
            return response()->json([
                'success' => false,
                'message' => 'Contra vouchers can only be used between Cash and Bank accounts.',
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            $oldStatus = $contraVoucher->status;
            
            // Update transaction
            if ($contraVoucher->transaction) {
                $contraVoucher->transaction->update([
                    'date' => $validated['contra_date'],
                    'description' => $validated['description'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => $validated['status'],
                ]);
                
                Log::info('Contra Voucher Update: Transaction updated', [
                    'transaction_id' => $contraVoucher->transaction->id,
                    'old_status' => $oldStatus,
                    'new_status' => $validated['status']
                ]);
                
                // Delete old customer ledger entries before deleting transaction entries
                if ($oldStatus === 'posted') {
                    TransactionObserver::deleteCustomerLedger($contraVoucher->transaction);
                }
                
                // Delete old entries
                $contraVoucher->transaction->entries()->delete();
                
                // Create new transaction entries
                TransactionEntry::create([
                    'transaction_id' => $contraVoucher->transaction->id,
                    'account_id' => $validated['to_account_id'],
                    'type' => 'debit',
                    'amount' => $validated['amount'],
                    'memo' => 'Contra: Transfer received in ' . $toAccount->name,
                ]);
                
                TransactionEntry::create([
                    'transaction_id' => $contraVoucher->transaction->id,
                    'account_id' => $validated['from_account_id'],
                    'type' => 'credit',
                    'amount' => $validated['amount'],
                    'memo' => 'Contra: Transfer sent from ' . $fromAccount->name,
                ]);
                
                Log::info('Contra Voucher Update: New entries created');
            }
            
            // Update contra voucher
            $contraVoucher->update([
                'contra_date' => $validated['contra_date'],
                'amount' => $validated['amount'],
                'from_account_id' => $validated['from_account_id'],
                'to_account_id' => $validated['to_account_id'],
                'transfer_method' => $validated['transfer_method'],
                'cheque_number' => $validated['cheque_number'] ?? null,
                'cheque_date' => $validated['cheque_date'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);
            
            // Manually trigger observer sync if status is posted
            if ($contraVoucher->transaction && $validated['status'] === 'posted') {
                Log::info('Contra Voucher Update: Triggering observer sync');
                TransactionObserver::syncToCustomerLedger($contraVoucher->transaction);
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contra voucher updated successfully!',
                    'data' => $contraVoucher->load(['fromAccount', 'toAccount', 'transaction']),
                ]);
            }
            
            return redirect()
                ->route('vouchers.contra.show', $contraVoucher->id)
                ->with('success', 'Contra voucher updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contra voucher update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating contra voucher: ' . $e->getMessage(),
                ], 500);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating contra voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified contra voucher from storage
     */
    public function destroy(ContraVoucher $contraVoucher)
    {
        if ($contraVoucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be deleted.',
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete transaction entries
            if ($contraVoucher->transaction) {
                $contraVoucher->transaction->entries()->delete();
                $contraVoucher->transaction->delete();
            }
            
            // Delete contra voucher
            $contraVoucher->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Contra voucher deleted successfully!',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contra voucher deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting contra voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a posted contra voucher
     */
    public function cancel(ContraVoucher $contraVoucher)
    {
        if (!$contraVoucher->can_cancel) {
            return response()->json([
                'success' => false,
                'message' => 'Only posted vouchers can be cancelled.',
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete customer ledger entries before cancelling
            if ($contraVoucher->transaction) {
                TransactionObserver::deleteCustomerLedger($contraVoucher->transaction);
                
                // Void the transaction
                $contraVoucher->transaction->update(['status' => 'voided']);
            }
            
            // Update voucher status
            $contraVoucher->update(['status' => 'cancelled']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Contra voucher cancelled successfully!',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contra voucher cancellation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling contra voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quick create account via AJAX (TallyERP-style)
     */
    public function quickCreateAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:accounts,name',
            'account_type' => 'required|in:cash,bank',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        try {
            // Determine parent account based on type
            $parentAccount = null;
            if ($validated['account_type'] === 'cash') {
                $parentAccount = Account::where('code', 'LIKE', '1110%')
                    ->orWhere('name', 'LIKE', '%Cash%')
                    ->where('parent_account_id', null)
                    ->first();
            } else {
                $parentAccount = Account::where('code', 'LIKE', '1120%')
                    ->orWhere('name', 'LIKE', '%Bank%')
                    ->where('parent_account_id', null)
                    ->first();
            }
            
            // Generate account code
            $prefix = $validated['account_type'] === 'cash' ? '1110' : '1120';
            $lastAccount = Account::where('code', 'LIKE', $prefix . '%')
                ->orderBy('code', 'desc')
                ->first();
            
            if ($lastAccount) {
                $lastNumber = (int) substr($lastAccount->code, -3);
                $newCode = $prefix . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newCode = $prefix . '001';
            }
            
            // Create account
            $account = Account::create([
                'code' => $newCode,
                'name' => $validated['name'],
                'type' => 'asset',
                'description' => ucfirst($validated['account_type']) . ' account',
                'parent_account_id' => $parentAccount ? $parentAccount->id : null,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'is_active' => true,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Account created successfully!',
                'data' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'code' => $account->code,
                    'type' => $validated['account_type'],
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quick account creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search accounts via AJAX for Select2
     */
    public function searchAccounts(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = Account::where('is_active', true)
            ->where(function($q) {
                $q->where('name', 'LIKE', '%Cash%')
                  ->orWhere('name', 'LIKE', '%Bank%')
                  ->orWhere('code', 'LIKE', '11%');
            });
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('code', 'LIKE', "%$search%");
            });
        }
        
        $total = $query->count();
        $accounts = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        $results = $accounts->map(function($account) {
            return [
                'id' => $account->id,
                'text' => $account->code . ' - ' . $account->name,
                'code' => $account->code,
                'name' => $account->name,
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
     * Helper: Check if account is Cash or Bank account
     */
    private function isCashOrBankAccount(Account $account): bool
    {
        $isCashOrBank = (
            stripos($account->name, 'cash') !== false ||
            stripos($account->name, 'bank') !== false ||
            str_starts_with($account->code, '11')
        );
        
        return $isCashOrBank && $account->type === 'asset';
    }
}
