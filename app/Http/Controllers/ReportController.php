<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Vendor;
use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    // ==================== TRIAL BALANCE REPORT ====================
    
    /**
     * Display Trial Balance Report
     */
    public function trialBalance(Request $request)
    {
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $accountType = $request->input('account_type', 'all');
        $showZeroBalance = $request->boolean('show_zero_balance', false);
        
        // Get account types for filter
        $accountTypes = [
            'all' => 'All Accounts',
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expenses',
        ];
        
        // AJAX request for data refresh
        if ($request->ajax()) {
            $accounts = $this->getTrialBalanceData($asOfDate, $accountType, $showZeroBalance);
            
            return response()->json([
                'accounts' => $accounts,
                'totalDebit' => number_format($accounts->sum('debit_balance'), 2),
                'totalCredit' => number_format($accounts->sum('credit_balance'), 2),
                'difference' => number_format(abs($accounts->sum('debit_balance') - $accounts->sum('credit_balance')), 2),
                'isBalanced' => abs($accounts->sum('debit_balance') - $accounts->sum('credit_balance')) < 0.01,
            ]);
        }
        
        return view('reports.trial-balance', compact('asOfDate', 'accountType', 'showZeroBalance', 'accountTypes'));
    }
    
    /**
     * Get trial balance data
     */
    private function getTrialBalanceData($asOfDate, $accountType = 'all', $showZeroBalance = false)
    {
        $query = Account::where('is_active', true)
            ->with(['transactionEntries' => function($query) use ($asOfDate) {
                $query->whereHas('transaction', function($q) use ($asOfDate) {
                    $q->where('date', '<=', $asOfDate)
                      ->where('status', 'posted');
                });
            }]);
        
        // Filter by account type
        if ($accountType !== 'all') {
            $query->where('type', $accountType);
        }
        
        $accounts = $query->get()->map(function($account) {
            $debits = $account->transactionEntries->where('type', 'debit')->sum('amount');
            $credits = $account->transactionEntries->where('type', 'credit')->sum('amount');
            
            // Calculate balance based on account type
            if (in_array($account->type, ['asset', 'expense'])) {
                // Normal debit balance accounts
                $netBalance = $debits - $credits;
                $debitBalance = $netBalance > 0 ? $netBalance : 0;
                $creditBalance = $netBalance < 0 ? abs($netBalance) : 0;
            } else {
                // Normal credit balance accounts (liability, equity, income)
                $netBalance = $credits - $debits;
                $creditBalance = $netBalance > 0 ? $netBalance : 0;
                $debitBalance = $netBalance < 0 ? abs($netBalance) : 0;
            }
            
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit_total' => $debits,
                'credit_total' => $credits,
                'debit_balance' => round($debitBalance, 2),
                'credit_balance' => round($creditBalance, 2),
            ];
        });
        
        // Filter out zero balances if requested
        if (!$showZeroBalance) {
            $accounts = $accounts->filter(function($account) {
                return $account['debit_balance'] != 0 || $account['credit_balance'] != 0;
            });
        }
        
        return $accounts->sortBy('code')->values();
    }
    
    /**
     * Export Trial Balance to CSV
     */
    public function trialBalanceCsv(Request $request)
    {
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $accountType = $request->input('account_type', 'all');
        $accounts = $this->getTrialBalanceData($asOfDate, $accountType, true);
        
        $filename = 'trial-balance-' . $asOfDate . '.csv';
        
        return $this->generateCsvResponse($filename, function($file) use ($accounts, $asOfDate) {
            // Header
            fputcsv($file, ['TRIAL BALANCE REPORT']);
            fputcsv($file, ['As of: ' . Carbon::parse($asOfDate)->format('d M Y')]);
            fputcsv($file, ['Generated: ' . now()->format('d M Y h:i A')]);
            fputcsv($file, []); // Empty row
            
            // Column headers
            fputcsv($file, ['Account Code', 'Account Name', 'Type', 'Debit Balance', 'Credit Balance']);
            
            // Data rows
            $totalDebit = 0;
            $totalCredit = 0;
            
            foreach ($accounts as $account) {
                fputcsv($file, [
                    $account['code'],
                    $account['name'],
                    ucfirst($account['type']),
                    number_format($account['debit_balance'], 2),
                    number_format($account['credit_balance'], 2),
                ]);
                
                $totalDebit += $account['debit_balance'];
                $totalCredit += $account['credit_balance'];
            }
            
            // Total row
            fputcsv($file, []);
            fputcsv($file, ['', 'TOTAL:', '', number_format($totalDebit, 2), number_format($totalCredit, 2)]);
            fputcsv($file, ['', 'DIFFERENCE:', '', '', number_format(abs($totalDebit - $totalCredit), 2)]);
        });
    }
    
    /**
     * Print Trial Balance
     */
    public function trialBalancePrint(Request $request)
    {
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $accountType = $request->input('account_type', 'all');
        $accounts = $this->getTrialBalanceData($asOfDate, $accountType, false);
        
        $totals = [
            'debit' => $accounts->sum('debit_balance'),
            'credit' => $accounts->sum('credit_balance'),
            'difference' => abs($accounts->sum('debit_balance') - $accounts->sum('credit_balance')),
        ];
        
        return view('reports.trial-balance-print', compact('accounts', 'asOfDate', 'totals'));
    }
    
    // ==================== PURCHASE REGISTER REPORT ====================
    
    /**
     * Display Purchase Register Report
     */
    public function purchaseRegister(Request $request)
    {
        if ($request->ajax()) {
            $query = PurchaseOrder::with(['vendor', 'items.product'])
                ->select('purchase_orders.*');
            
            // Apply filters
            if ($request->filled('start_date')) {
                $query->whereDate('order_date', '>=', $request->start_date);
            }
            
            if ($request->filled('end_date')) {
                $query->whereDate('order_date', '<=', $request->end_date);
            }
            
            if ($request->filled('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('product_id')) {
                $query->whereHas('items', function($q) use ($request) {
                    $q->where('product_id', $request->product_id);
                });
            }
            
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('order_number', function ($row) {
                    return '<a href="' . route('purchase-orders.show', $row->id) . '" class="text-primary">' .
                           '<strong>' . $row->order_number . '</strong></a>';
                })
                ->addColumn('order_date_formatted', function ($row) {
                    return Carbon::parse($row->order_date)->format('d M Y');
                })
                ->addColumn('vendor_name', function ($row) {
                    return $row->vendor ? $row->vendor->name : '-';
                })
                ->addColumn('items_count', function ($row) {
                    return '<span class="badge badge-info">' . $row->items->count() . ' items</span>';
                })
                ->addColumn('total_quantity', function ($row) {
                    return '<strong>' . number_format($row->items->sum('quantity'), 2) . '</strong>';
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return '<strong class="text-success">৳ ' . number_format($row->total_amount, 2) . '</strong>';
                })
                ->addColumn('status_badge', function ($row) {
                    if ($row->status === 'received') {
                        $date = $row->received_date ? '<br><small>' . Carbon::parse($row->received_date)->format('d M Y') . '</small>' : '';
                        return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Received</span>' . $date;
                    }
                    return '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('purchase-orders.show', $row->id) . '" class="btn btn-info btn-sm">' .
                           '<i class="fas fa-eye"></i></a>';
                })
                ->rawColumns(['order_number', 'items_count', 'total_quantity', 'total_amount_formatted', 'status_badge', 'action'])
                ->make(true);
        }
        
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        
        return view('reports.purchase-register', compact('vendors', 'products'));
    }
    
    /**
     * Export Purchase Register to CSV
     */
    public function purchaseRegisterCsv(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'items.product']);
        
        // Apply same filters
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->orderBy('order_date', 'desc')->get();
        
        $filename = 'purchase-register-' . now()->format('Y-m-d') . '.csv';
        
        return $this->generateCsvResponse($filename, function($file) use ($orders, $request) {
            // Header
            fputcsv($file, ['PURCHASE REGISTER REPORT']);
            fputcsv($file, ['Period: ' . ($request->start_date ?? 'All') . ' to ' . ($request->end_date ?? 'All')]);
            fputcsv($file, ['Generated: ' . now()->format('d M Y h:i A')]);
            fputcsv($file, []);
            
            // Column headers
            fputcsv($file, [
                'Order Number', 'Order Date', 'Vendor Name', 'Items Count', 
                'Total Quantity', 'Total Amount', 'Status', 'Received Date'
            ]);
            
            // Data rows
            $totalAmount = 0;
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    Carbon::parse($order->order_date)->format('d M Y'),
                    $order->vendor ? $order->vendor->name : '-',
                    $order->items->count(),
                    number_format($order->items->sum('quantity'), 2),
                    number_format($order->total_amount, 2),
                    ucfirst($order->status),
                    $order->received_date ? Carbon::parse($order->received_date)->format('d M Y') : '-',
                ]);
                
                $totalAmount += $order->total_amount;
            }
            
            // Total row
            fputcsv($file, []);
            fputcsv($file, ['', '', '', '', 'TOTAL:', number_format($totalAmount, 2), '', '']);
        });
    }
    
    // ==================== VENDOR-WISE PURCHASE REPORT ====================
    
    /**
     * Display Vendor-wise Purchase Report
     */
    public function vendorWisePurchase(Request $request)
    {
        if ($request->ajax()) {
            $query = Vendor::with(['purchaseOrders' => function($q) use ($request) {
                if ($request->filled('start_date')) {
                    $q->whereDate('order_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('order_date', '<=', $request->end_date);
                }
                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }
            }])
            ->withCount(['purchaseOrders as total_orders' => function($q) use ($request) {
                if ($request->filled('start_date')) {
                    $q->whereDate('order_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('order_date', '<=', $request->end_date);
                }
                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }
            }])
            ->withSum(['purchaseOrders as total_amount' => function($q) use ($request) {
                if ($request->filled('start_date')) {
                    $q->whereDate('order_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('order_date', '<=', $request->end_date);
                }
                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }
            }], 'total_amount');
            
            // Filter by vendor
            if ($request->filled('vendor_id')) {
                $query->where('id', $request->vendor_id);
            }
            
            // Only show vendors with purchase orders
            $query->has('purchaseOrders');
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('vendor_name', function ($row) {
                    $phone = $row->phone ? '<br><small class="text-muted">' . $row->phone . '</small>' : '';
                    return '<strong>' . $row->name . '</strong>' . $phone;
                })
                ->addColumn('total_orders', function ($row) {
                    return '<span class="badge badge-primary">' . $row->total_orders . '</span>';
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return '<strong class="text-success">৳ ' . number_format($row->total_amount ?? 0, 2) . '</strong>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('vendors.show', $row->id) . '" class="btn btn-info btn-sm">' .
                           '<i class="fas fa-eye"></i></a>';
                })
                ->rawColumns(['vendor_name', 'total_orders', 'total_amount_formatted', 'action'])
                ->make(true);
        }
        
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        
        return view('reports.vendor-wise-purchase', compact('vendors'));
    }
    
    // ==================== PAYABLES REPORT ====================
    
    /**
     * Display Payables Report
     */
    public function payables(Request $request)
    {
        $liabilityAccounts = Account::where('type', 'liability')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        
        $vendors = Vendor::where('is_active', true)
            ->whereNotNull('ledger_account_id')
            ->orderBy('name')
            ->get();
        
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        
        return view('reports.payables', compact('liabilityAccounts', 'vendors', 'startDate', 'endDate'));
    }
    
    /**
     * Get Payables Data for DataTables
     */
    public function getPayablesData(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $accountId = $request->input('account_id');
        $vendorId = $request->input('vendor_id');
        $showZeroBalance = $request->boolean('show_zero_balance', false);
        
        $query = Account::where('type', 'liability')
            ->where('is_active', true)
            ->with(['parentAccount', 'transactionEntries' => function($q) use ($startDate, $endDate) {
                $q->whereHas('transaction', function($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted');
                    
                    if ($startDate) {
                        $query->whereDate('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->whereDate('date', '<=', $endDate);
                    }
                });
            }])
            ->select('accounts.*');
        
        // Apply filters
        if ($accountId) {
            $query->where('id', $accountId);
        }
        
        if ($vendorId) {
            $vendor = Vendor::find($vendorId);
            if ($vendor && $vendor->ledger_account_id) {
                $query->where('id', $vendor->ledger_account_id);
            }
        }
        
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('account_code', function($account) {
                return '<span class="badge badge-primary">' . e($account->code) . '</span>';
            })
            ->addColumn('account_name', function($account) {
                $html = '<strong>' . e($account->name) . '</strong>';
                if ($account->parentAccount) {
                    $html .= '<br><small class="text-muted">Parent: ' . e($account->parentAccount->name) . '</small>';
                }
                return $html;
            })
            ->addColumn('opening_balance', function($account) {
                $openingBalance = $account->opening_balance ?? 0;
                $class = $openingBalance > 0 ? 'text-success' : ($openingBalance < 0 ? 'text-danger' : 'text-muted');
                return '<span class="' . $class . '">৳ ' . number_format(abs($openingBalance), 2) . '</span>';
            })
            ->addColumn('total_debits', function($account) {
                $debits = $account->transactionEntries->where('type', 'debit')->sum('amount');
                return '<span class="text-danger">৳ ' . number_format($debits, 2) . '</span>';
            })
            ->addColumn('total_credits', function($account) {
                $credits = $account->transactionEntries->where('type', 'credit')->sum('amount');
                return '<span class="text-success">৳ ' . number_format($credits, 2) . '</span>';
            })
            ->addColumn('current_balance', function($account) {
                $openingBalance = $account->opening_balance ?? 0;
                $debits = $account->transactionEntries->where('type', 'debit')->sum('amount');
                $credits = $account->transactionEntries->where('type', 'credit')->sum('amount');
                
                // For liability: Credit increases, Debit decreases
                $currentBalance = $openingBalance + $credits - $debits;
                
                $class = $currentBalance > 0 ? 'text-danger font-weight-bold' : ($currentBalance < 0 ? 'text-success' : 'text-muted');
                $label = $currentBalance > 0 ? ' (Payable)' : ($currentBalance < 0 ? ' (Advance)' : '');
                
                return '<span class="' . $class . '">৳ ' . number_format(abs($currentBalance), 2) . $label . '</span>';
            })
            ->addColumn('transaction_count', function($account) {
                $count = $account->transactionEntries->count();
                return '<span class="badge badge-info">' . $count . ' transactions</span>';
            })
            ->addColumn('vendor_info', function($account) {
                $vendor = Vendor::where('ledger_account_id', $account->id)->first();
                
                if ($vendor) {
                    return '<span class="badge badge-warning">' . e($vendor->name) . '</span><br>' .
                           '<small class="text-muted">' . e($vendor->phone ?? 'No phone') . '</small>';
                }
                
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function($account) {
                $detailsBtn = '<button type="button" class="btn btn-info btn-sm view-details" ' .
                             'data-account-id="' . $account->id . '" ' .
                             'data-account-name="' . e($account->name) . '" ' .
                             'title="View Transactions">' .
                             '<i class="fas fa-eye"></i></button>';
                
                $vendor = Vendor::where('ledger_account_id', $account->id)->first();
                
                $paymentBtn = '<a href="' . route('vouchers.payment.create', [
                    'vendor_id' => $vendor->id ?? null,
                    'paid_to_account_id' => $account->id
                ]) . '" class="btn btn-success btn-sm" title="Make Payment">' .
                '<i class="fas fa-money-bill-wave"></i></a>';
                
                $ledgerBtn = '<a href="' . route('accounts.show', $account->id) . '" ' .
                            'class="btn btn-primary btn-sm" title="View Ledger" target="_blank">' .
                            '<i class="fas fa-book"></i></a>';
                
                return '<div class="btn-group">' . $detailsBtn . ' ' . $paymentBtn . ' ' . $ledgerBtn . '</div>';
            })
            ->filterColumn('account_name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                      ->orWhere('code', 'like', "%{$keyword}%");
            })
            ->rawColumns(['account_code', 'account_name', 'opening_balance', 'total_debits', 
                         'total_credits', 'current_balance', 'transaction_count', 'vendor_info', 'actions'])
            ->make(true);
    }
    
    /**
     * Get detailed transactions for a payable account
     */
    public function getPayableTransactions(Request $request, Account $account)
    {
        if ($account->type !== 'liability') {
            return response()->json([
                'success' => false,
                'message' => 'This account is not a liability account.'
            ], 400);
        }
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = DB::table('transaction_entries as te')
            ->join('transactions as t', 'te.transaction_id', '=', 't.id')
            ->where('te.account_id', $account->id)
            ->where('t.status', 'posted')
            ->whereNull('t.deleted_at')
            ->whereNull('te.deleted_at')
            ->select(
                't.id as transaction_id',
                't.date',
                't.reference',
                't.description',
                't.type as transaction_type',
                'te.type as entry_type',
                'te.amount',
                'te.memo'
            )
            ->orderBy('t.date', 'desc');
        
        if ($startDate) {
            $query->whereDate('t.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('t.date', '<=', $endDate);
        }
        
        return DataTables::of($query)
            ->editColumn('date', function($entry) {
                return Carbon::parse($entry->date)->format('d M Y');
            })
            ->addColumn('reference_link', function($entry) {
                return '<a href="' . route('transactions.show', $entry->transaction_id) . '" target="_blank">' .
                       '<strong>' . e($entry->reference ?? 'TXN-' . $entry->transaction_id) . '</strong></a>';
            })
            ->addColumn('debit', function($entry) {
                if ($entry->entry_type === 'debit') {
                    return '<span class="text-danger font-weight-bold">৳ ' . number_format($entry->amount, 2) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('credit', function($entry) {
                if ($entry->entry_type === 'credit') {
                    return '<span class="text-success font-weight-bold">৳ ' . number_format($entry->amount, 2) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('transaction_type_badge', function($entry) {
                $badges = [
                    'payment' => 'badge-success',
                    'receipt' => 'badge-info',
                    'purchase' => 'badge-warning',
                    'journal' => 'badge-primary',
                ];
                $badge = $badges[$entry->transaction_type] ?? 'badge-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst(str_replace('_', ' ', $entry->transaction_type)) . '</span>';
            })
            ->addColumn('description_full', function($entry) {
                $html = '<strong>' . e($entry->description) . '</strong>';
                if ($entry->memo) {
                    $html .= '<br><small class="text-muted">' . e($entry->memo) . '</small>';
                }
                return $html;
            })
            ->rawColumns(['reference_link', 'debit', 'credit', 'transaction_type_badge', 'description_full'])
            ->make(true);
    }
    
    /**
     * Export Payables Report to CSV
     */
    public function payablesCsv(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $accountId = $request->input('account_id');
        $vendorId = $request->input('vendor_id');
        
        $accounts = $this->getPayablesReportData($startDate, $endDate, $accountId, $vendorId);
        
        $filename = 'payables-report-' . $startDate . '-to-' . $endDate . '.csv';
        
        return $this->generateCsvResponse($filename, function($file) use ($accounts, $startDate, $endDate) {
            // Header
            fputcsv($file, ['PAYABLES REPORT']);
            fputcsv($file, ['Period: ' . Carbon::parse($startDate)->format('d M Y') . ' to ' . Carbon::parse($endDate)->format('d M Y')]);
            fputcsv($file, ['Generated: ' . now()->format('d M Y h:i A')]);
            fputcsv($file, []);
            
            // Column headers
            fputcsv($file, [
                'Account Code',
                'Account Name',
                'Opening Balance',
                'Total Debits',
                'Total Credits',
                'Current Balance',
                'Balance Type',
                'Vendor Name',
                'Transaction Count'
            ]);
            
            // Data rows
            $totalDebits = 0;
            $totalCredits = 0;
            $totalBalance = 0;
            
            foreach ($accounts as $item) {
                $account = $item['account'];
                $vendor = $item['vendor'];
                
                fputcsv($file, [
                    $account->code,
                    $account->name,
                    number_format($item['opening_balance'], 2),
                    number_format($item['total_debits'], 2),
                    number_format($item['total_credits'], 2),
                    number_format(abs($item['current_balance']), 2),
                    $item['current_balance'] > 0 ? 'Payable' : ($item['current_balance'] < 0 ? 'Advance' : 'Zero'),
                    $vendor ? $vendor->name : '-',
                    $account->transactionEntries->count()
                ]);
                
                $totalDebits += $item['total_debits'];
                $totalCredits += $item['total_credits'];
                $totalBalance += $item['current_balance'];
            }
            
            // Total row
            fputcsv($file, []);
            fputcsv($file, [
                '',
                'TOTAL:',
                '',
                number_format($totalDebits, 2),
                number_format($totalCredits, 2),
                number_format(abs($totalBalance), 2),
                $totalBalance > 0 ? 'Payable' : 'Advance',
                '',
                ''
            ]);
        });
    }
    
    /**
     * Print view for Payables Report
     */
    public function payablesPrint(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $accountId = $request->input('account_id');
        $vendorId = $request->input('vendor_id');
        
        $accounts = $this->getPayablesReportData($startDate, $endDate, $accountId, $vendorId);
        
        $totals = [
            'opening_balance' => $accounts->sum('opening_balance'),
            'total_debits' => $accounts->sum('total_debits'),
            'total_credits' => $accounts->sum('total_credits'),
            'current_balance' => $accounts->sum('current_balance'),
            'total_payable' => $accounts->where('current_balance', '>', 0)->sum('current_balance'),
            'total_advance' => abs($accounts->where('current_balance', '<', 0)->sum('current_balance')),
        ];
        
        return view('reports.payables-print', compact('accounts', 'startDate', 'endDate', 'totals'));
    }
    
    /**
     * Helper: Get payables report data
     */
    private function getPayablesReportData($startDate, $endDate, $accountId = null, $vendorId = null)
    {
        $query = Account::where('type', 'liability')
            ->where('is_active', true)
            ->with(['parentAccount', 'transactionEntries' => function($q) use ($startDate, $endDate) {
                $q->whereHas('transaction', function($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted')
                          ->whereDate('date', '>=', $startDate)
                          ->whereDate('date', '<=', $endDate);
                });
            }]);
        
        if ($accountId) {
            $query->where('id', $accountId);
        }
        
        if ($vendorId) {
            $vendor = Vendor::find($vendorId);
            if ($vendor && $vendor->ledger_account_id) {
                $query->where('id', $vendor->ledger_account_id);
            }
        }
        
        return $query->orderBy('code')
            ->get()
            ->map(function($account) {
                $openingBalance = $account->opening_balance ?? 0;
                $debits = $account->transactionEntries->where('type', 'debit')->sum('amount');
                $credits = $account->transactionEntries->where('type', 'credit')->sum('amount');
                $currentBalance = $openingBalance + $credits - $debits;
                
                return [
                    'account' => $account,
                    'opening_balance' => $openingBalance,
                    'total_debits' => $debits,
                    'total_credits' => $credits,
                    'current_balance' => $currentBalance,
                    'vendor' => Vendor::where('ledger_account_id', $account->id)->first()
                ];
            });
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Generate CSV Response with UTF-8 BOM
     */
    private function generateCsvResponse($filename, $callback)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
        
        return response()->stream(function() use ($callback) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel opening
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Execute callback to write data
            $callback($file);
            
            fclose($file);
        }, 200, $headers);
    }
}
