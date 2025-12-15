<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    /**
     * Display listing with DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::with('ledgerAccount')->select('vendors.*');

            return DataTables::eloquent($query)
                ->addColumn('ledger_account', function ($vendor) {
                    if ($vendor->ledgerAccount) {
                        return '<span class="badge badge-info">' . e($vendor->ledgerAccount->code) . '</span> ' 
                            . e($vendor->ledgerAccount->name);
                    }
                    return '<span class="badge badge-secondary">Not Linked</span>';
                })
                ->addColumn('opening_balance', function ($vendor) {
                    if ($vendor->opening_balance > 0) {
                        $type = $vendor->opening_balance_type === 'credit' ? 'Cr' : 'Dr';
                        $class = $vendor->opening_balance_type === 'credit' ? 'text-danger' : 'text-success';
                        return '<span class="' . $class . '">৳ ' . number_format($vendor->opening_balance, 2) . ' ' . $type . '</span>';
                    }
                    return '<span class="text-muted">৳ 0.00</span>';
                })
                ->addColumn('current_balance', function ($vendor) {
                    $balance = $vendor->current_balance;
                    if ($balance > 0) {
                        return '<span class="text-danger font-weight-bold">৳ ' . number_format($balance, 2) . ' (Payable)</span>';
                    } elseif ($balance < 0) {
                        return '<span class="text-success font-weight-bold">৳ ' . number_format(abs($balance), 2) . ' (Advance)</span>';
                    }
                    return '<span class="text-muted">৳ 0.00</span>';
                })
                ->addColumn('status', function ($vendor) {
                    return $vendor->is_active 
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-danger">Inactive</span>';
                })
                ->addColumn('action', function ($vendor) {
                    return view('vendors.partials.actions', compact('vendor'))->render();
                })
                ->rawColumns(['ledger_account', 'opening_balance', 'current_balance', 'status', 'action'])
                ->make(true);
        }

        return view('vendors.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('vendors.create');
    }

    /**
     * Store new vendor
     * Fixed: Using StoreVendorRequest
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Generate unique vendor code
            $vendorCode = $this->generateVendorCode($validated['name']);

            // Create ledger account (Sundry Creditor - Liability)
            $account = Account::create([
                'name' => $validated['name'],
                'code' => 'VEND-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 4)) . '-' . rand(1000, 9999),
                'type' => 'liability',
                'description' => 'Vendor Account: ' . $validated['name'],
                'is_active' => true,
            ]);

            // Create vendor
            $vendor = Vendor::create([
                'vendor_code' => $vendorCode,
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? 'Bangladesh',
                'description' => $validated['description'] ?? null,
                'ledger_account_id' => $account->id,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'opening_balance_type' => $validated['opening_balance_type'] ?? 'credit',
                'opening_balance_date' => $validated['opening_balance_date'] ?? now(),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create opening balance transaction if applicable
            if ($vendor->opening_balance > 0) {
                $this->createOpeningBalanceTransaction($vendor);
            }

            DB::commit();

            Log::info('Vendor created successfully', [
                'vendor_id' => $vendor->id,
                'vendor_code' => $vendor->vendor_code,
                'name' => $vendor->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor created successfully!',
                'data' => $vendor->load('ledgerAccount'),
                'redirect_url' => route('vendors.show', $vendor->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Vendor creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating vendor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show vendor details
     */
    public function show(Vendor $vendor)
    {
        $vendor->load(['ledgerAccount', 'purchaseOrders' => function ($q) {
            $q->latest('order_date')->limit(10);
        }]);

        $stats = [
            'current_balance' => $vendor->current_balance,
            'total_purchases' => $vendor->total_purchases,
            'total_orders' => $vendor->purchaseOrders()->count(),
            'pending_orders' => $vendor->purchaseOrders()->where('status', 'pending')->count(),
        ];

        return view('vendors.show', compact('vendor', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    /**
     * Update vendor
     * Fixed: Using UpdateVendorRequest
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            $oldOpeningBalance = $vendor->opening_balance;
            $oldOpeningBalanceType = $vendor->opening_balance_type;

            // Update vendor
            $vendor->update([
                'name' => $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? 'Bangladesh',
                'description' => $validated['description'] ?? null,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'opening_balance_type' => $validated['opening_balance_type'] ?? 'credit',
                'opening_balance_date' => $validated['opening_balance_date'] ?? $vendor->opening_balance_date,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Update linked ledger account
            if ($vendor->ledgerAccount) {
                $vendor->ledgerAccount->update([
                    'name' => $validated['name'],
                    'description' => 'Vendor Account: ' . $validated['name'],
                    'is_active' => $validated['is_active'] ?? true,
                ]);
            }

            // Handle opening balance changes
            $newOpeningBalance = $validated['opening_balance'] ?? 0;
            $newOpeningBalanceType = $validated['opening_balance_type'] ?? 'credit';

            if ($oldOpeningBalance != $newOpeningBalance || $oldOpeningBalanceType != $newOpeningBalanceType) {
                $this->deleteOpeningBalanceTransaction($vendor);

                if ($newOpeningBalance > 0) {
                    $this->createOpeningBalanceTransaction($vendor);
                }
            }

            DB::commit();

            Log::info('Vendor updated successfully', [
                'vendor_id' => $vendor->id,
                'name' => $vendor->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully!',
                'data' => $vendor->fresh('ledgerAccount'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Vendor update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating vendor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete vendor
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        // Check for purchase orders
        if ($vendor->purchaseOrders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete vendor with existing purchase orders. Deactivate instead.',
            ], 422);
        }

        // Check for transactions
        if ($vendor->ledger_account_id) {
            $hasTransactions = TransactionEntry::where('account_id', $vendor->ledger_account_id)
                ->whereHas('transaction', fn($q) => $q->where('type', '!=', 'opening_balance'))
                ->exists();

            if ($hasTransactions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete vendor with transaction history. Deactivate instead.',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Delete opening balance transaction
            $this->deleteOpeningBalanceTransaction($vendor);

            $ledgerAccountId = $vendor->ledger_account_id;

            // Soft delete vendor
            $vendor->delete();

            // Delete orphan ledger account
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
     * Deactivate vendor (alternative to delete)
     */
    public function deactivate(Vendor $vendor): JsonResponse
    {
        try {
            DB::beginTransaction();

            $vendor->update(['is_active' => false]);
            $vendor->ledgerAccount?->update(['is_active' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vendor deactivated successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deactivating vendor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get vendor balance (AJAX)
     */
    public function getBalance(Vendor $vendor): JsonResponse
    {
        try {
            $balance = $vendor->current_balance;

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted' => '৳ ' . number_format(abs($balance), 2),
                'type' => $balance > 0 ? 'payable' : ($balance < 0 ? 'receivable' : 'zero'),
            ]);

        } catch (\Exception $e) {
            Log::error('Vendor balance fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'balance' => 0,
                'message' => 'Error fetching vendor balance',
            ], 500);
        }
    }

    /**
     * Generate unique vendor code
     */
    private function generateVendorCode(string $name): string
    {
        $prefix = 'V';
        $lastVendor = Vendor::withTrashed()->latest('id')->first();
        $nextId = $lastVendor ? $lastVendor->id + 1 : 1;

        return $prefix . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create opening balance transaction
     */
    private function createOpeningBalanceTransaction(Vendor $vendor): void
    {
        if (!$vendor->ledger_account_id || $vendor->opening_balance <= 0) {
            return;
        }

        // Get or create Capital Account
        $capitalAccount = Account::firstOrCreate(
            ['code' => 'CAPITAL-001'],
            [
                'name' => 'Capital Account',
                'type' => 'equity',
                'description' => 'Owner Capital Account for Opening Balances',
                'is_active' => true,
            ]
        );

        $transaction = Transaction::create([
            'date' => $vendor->opening_balance_date ?? now(),
            'type' => 'opening_balance',
            'reference' => 'OB-VEND-' . $vendor->id,
            'description' => 'Opening Balance for Vendor: ' . $vendor->name,
            'status' => 'posted',
            'source_type' => Vendor::class,
            'source_id' => $vendor->id,
        ]);

        if ($vendor->opening_balance_type === 'credit') {
            // Credit: Vendor account (we owe them)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $vendor->ledger_account_id,
                'type' => 'credit',
                'amount' => $vendor->opening_balance,
                'memo' => 'Opening Balance - Vendor Credit',
            ]);

            // Debit: Capital account
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $capitalAccount->id,
                'type' => 'debit',
                'amount' => $vendor->opening_balance,
                'memo' => 'Opening Balance - Contra Entry',
            ]);
        } else {
            // Debit: Vendor account (they owe us - advance paid)
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $vendor->ledger_account_id,
                'type' => 'debit',
                'amount' => $vendor->opening_balance,
                'memo' => 'Opening Balance - Vendor Debit (Advance)',
            ]);

            // Credit: Capital account
            TransactionEntry::create([
                'transaction_id' => $transaction->id,
                'account_id' => $capitalAccount->id,
                'type' => 'credit',
                'amount' => $vendor->opening_balance,
                'memo' => 'Opening Balance - Contra Entry',
            ]);
        }
    }

    /**
     * Delete opening balance transaction
     */
    private function deleteOpeningBalanceTransaction(Vendor $vendor): void
    {
        $transaction = Transaction::where('type', 'opening_balance')
            ->where('source_type', Vendor::class)
            ->where('source_id', $vendor->id)
            ->first();

        if ($transaction) {
            $transaction->entries()->forceDelete();
            $transaction->forceDelete();
        }
    }
}