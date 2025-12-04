<?php

namespace App\Http\Controllers;

use App\Models\JournalVoucher;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Observers\TransactionObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class JournalVoucherController extends Controller
{
    /**
     * Display a listing of journal vouchers with server-side DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = JournalVoucher::with(['transaction'])
                ->select('journal_vouchers.*');
            
            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('journal_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('journal_date', '<=', $request->end_date);
            }
            
            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('voucher_number', function ($row) {
                    return '<a href="' . route('vouchers.journal.show', $row->id) . '">' .
                           '<strong>' . $row->voucher_number . '</strong></a>';
                })
                ->addColumn('journal_date', function ($row) {
                    return $row->journal_date->format('d M Y');
                })
                ->addColumn('description', function ($row) {
                    return \Str::limit($row->description, 50);
                })
                ->addColumn('debit', function ($row) {
                    return '<strong class="text-danger">' . number_format($row->total_debit, 2) . '</strong>';
                })
                ->addColumn('credit', function ($row) {
                    return '<strong class="text-success">' . number_format($row->total_credit, 2) . '</strong>';
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
                    $viewBtn = '<a href="' . route('vouchers.journal.show', $row->id) . '" ' .
                               'class="btn btn-info btn-sm" title="View">' .
                               '<i class="fas fa-eye"></i></a>';
                    
                    $editBtn = '';
                    if ($row->can_edit) {
                        $editBtn = '<a href="' . route('vouchers.journal.edit', $row->id) . '" ' .
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
                ->rawColumns(['voucher_number', 'debit', 'credit', 'status', 'action'])
                ->make(true);
        }
        
        return view('vouchers.journal.index');
    }

    /**
     * Show the form for creating a new journal voucher
     */
    public function create(Request $request)
    {
        // Get all active accounts
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        // Generate voucher number
        $voucherNumber = JournalVoucher::generateVoucherNumber();
        
        return view('vouchers.journal.create', compact('accounts', 'voucherNumber'));
    }

    /**
     * Store a newly created journal voucher in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_date' => 'required|date',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.type' => 'required|in:debit,credit',
            'entries.*.amount' => 'required|numeric|min:0.01',
            'entries.*.memo' => 'nullable|string|max:255',
        ]);

        // Calculate totals
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($validated['entries'] as $entry) {
            if ($entry['type'] === 'debit') {
                $totalDebit += $entry['amount'];
            } else {
                $totalCredit += $entry['amount'];
            }
        }

        // Validate balanced entries
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entries must be balanced! Debit: ' . number_format($totalDebit, 2) . ', Credit: ' . number_format($totalCredit, 2),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate voucher number
            $voucherNumber = JournalVoucher::generateVoucherNumber();
            
            // Create transaction
            $transaction = Transaction::create([
                'date' => $validated['journal_date'],
                'type' => 'journal',
                'reference' => $voucherNumber,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);
            
            Log::info('Journal Voucher: Transaction created', [
                'transaction_id' => $transaction->id,
                'reference' => $voucherNumber
            ]);
            
            // Create transaction entries
            foreach ($validated['entries'] as $entryData) {
                TransactionEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $entryData['account_id'],
                    'type' => $entryData['type'],
                    'amount' => $entryData['amount'],
                    'memo' => $entryData['memo'] ?? null,
                ]);
            }
            
            Log::info('Journal Voucher: Entries created', [
                'count' => count($validated['entries'])
            ]);
            
            // Create journal voucher
            $journalVoucher = JournalVoucher::create([
                'voucher_number' => $voucherNumber,
                'journal_date' => $validated['journal_date'],
                'transaction_id' => $transaction->id,
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
                'created_by' => Auth::id(),
            ]);
            
            Log::info('Journal Voucher: Voucher created', [
                'voucher_id' => $journalVoucher->id,
                'voucher_number' => $voucherNumber
            ]);
            
            // Manually trigger observer sync if status is posted
            if ($transaction->status === 'posted') {
                Log::info('Journal Voucher: Triggering observer sync');
                TransactionObserver::syncToCustomerLedger($transaction);
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Journal voucher created successfully!',
                    'data' => $journalVoucher->load(['transaction']),
                ]);
            }
            
            return redirect()
                ->route('vouchers.journal.show', $journalVoucher->id)
                ->with('success', 'Journal voucher created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal voucher creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating journal voucher: ' . $e->getMessage(),
                ], 500);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating journal voucher: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified journal voucher
     */
    public function show(JournalVoucher $journalVoucher)
    {
        $journalVoucher->load(['transaction.entries.account', 'createdBy']);
        
        return view('vouchers.journal.show', compact('journalVoucher'));
    }

    /**
     * Show the form for editing the specified journal voucher
     */
    public function edit(JournalVoucher $journalVoucher)
    {
        if (!$journalVoucher->can_edit) {
            return redirect()
                ->route('vouchers.journal.show', $journalVoucher->id)
                ->with('error', 'Only draft vouchers can be edited.');
        }
        
        $journalVoucher->load(['transaction.entries.account']);
        
        // Get all active accounts
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        return view('vouchers.journal.edit', compact('journalVoucher', 'accounts'));
    }

    /**
     * Update the specified journal voucher in storage
     */
    public function update(Request $request, JournalVoucher $journalVoucher)
    {
        if (!$journalVoucher->can_edit) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be edited.',
            ], 422);
        }
        
        $validated = $request->validate([
            'journal_date' => 'required|date',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,posted',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.type' => 'required|in:debit,credit',
            'entries.*.amount' => 'required|numeric|min:0.01',
            'entries.*.memo' => 'nullable|string|max:255',
        ]);

        // Calculate totals
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($validated['entries'] as $entry) {
            if ($entry['type'] === 'debit') {
                $totalDebit += $entry['amount'];
            } else {
                $totalCredit += $entry['amount'];
            }
        }

        // Validate balanced entries
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entries must be balanced! Debit: ' . number_format($totalDebit, 2) . ', Credit: ' . number_format($totalCredit, 2),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldStatus = $journalVoucher->status;
            
            // Update transaction
            if ($journalVoucher->transaction) {
                $journalVoucher->transaction->update([
                    'date' => $validated['journal_date'],
                    'description' => $validated['description'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => $validated['status'],
                ]);
                
                // Delete old customer ledger entries
                if ($oldStatus === 'posted') {
                    TransactionObserver::deleteCustomerLedger($journalVoucher->transaction);
                }
                
                // Delete old entries
                $journalVoucher->transaction->entries()->delete();
                
                // Create new entries
                foreach ($validated['entries'] as $entryData) {
                    TransactionEntry::create([
                        'transaction_id' => $journalVoucher->transaction->id,
                        'account_id' => $entryData['account_id'],
                        'type' => $entryData['type'],
                        'amount' => $entryData['amount'],
                        'memo' => $entryData['memo'] ?? null,
                    ]);
                }
            }
            
            // Update journal voucher
            $journalVoucher->update([
                'journal_date' => $validated['journal_date'],
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);
            
            // Manually trigger observer sync if status is posted
            if ($journalVoucher->transaction && $validated['status'] === 'posted') {
                TransactionObserver::syncToCustomerLedger($journalVoucher->transaction);
            }
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Journal voucher updated successfully!',
                    'data' => $journalVoucher->load(['transaction']),
                ]);
            }
            
            return redirect()
                ->route('vouchers.journal.show', $journalVoucher->id)
                ->with('success', 'Journal voucher updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal voucher update error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating journal voucher: ' . $e->getMessage(),
                ], 500);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating journal voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified journal voucher from storage
     */
    public function destroy(JournalVoucher $journalVoucher)
    {
        if ($journalVoucher->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft vouchers can be deleted.',
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete transaction entries
            if ($journalVoucher->transaction) {
                $journalVoucher->transaction->entries()->delete();
                $journalVoucher->transaction->delete();
            }
            
            // Delete journal voucher
            $journalVoucher->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Journal voucher deleted successfully!',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal voucher deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting journal voucher: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a posted journal voucher
     */
    public function cancel(JournalVoucher $journalVoucher)
    {
        if (!$journalVoucher->can_cancel) {
            return response()->json([
                'success' => false,
                'message' => 'Only posted vouchers can be cancelled.',
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete customer ledger entries
            if ($journalVoucher->transaction) {
                TransactionObserver::deleteCustomerLedger($journalVoucher->transaction);
                
                // Void the transaction
                $journalVoucher->transaction->update(['status' => 'voided']);
            }
            
            // Update voucher status
            $journalVoucher->update(['status' => 'cancelled']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Journal voucher cancelled successfully!',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal voucher cancellation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling journal voucher: ' . $e->getMessage(),
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
        
        $query = Account::where('is_active', true);
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('code', 'LIKE', "%$search%");
            });
        }
        
        $total = $query->count();
        $accounts = $query->orderBy('code')
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
}
