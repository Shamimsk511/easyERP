<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Sales\PaymentService;
use App\Http\Requests\Sales\StorePaymentRequest;
use App\Observers\TransactionObserver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Display payment listing
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = InvoicePayment::with(['invoice.customer', 'account', 'recordedByUser'])
                ->select('invoice_payments.*');

            return DataTables::eloquent($query)
                ->addColumn('invoice_number', fn($payment) => $payment->invoice->invoice_number ?? 'N/A')
                ->addColumn('customer_name', fn($payment) => $payment->invoice->customer->name ?? 'N/A')
                ->addColumn('account_name', fn($payment) => $payment->account->name ?? 'N/A')
                ->addColumn('recorded_by', fn($payment) => $payment->recordedByUser->name ?? 'N/A')
                ->addColumn('action', function ($payment) {
                    $viewBtn = '<a href="' . route('payments.show', $payment->id) . '" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>';
                    $printBtn = '<a href="' . route('payments.print', $payment->id) . '" class="btn btn-sm btn-secondary" target="_blank" title="Print"><i class="fas fa-print"></i></a>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-payment-btn" data-id="' . $payment->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                    return '<div class="btn-group">' . $viewBtn . $printBtn . $deleteBtn . '</div>';
                })
                ->editColumn('payment_date', fn($payment) => $payment->payment_date->format('d M Y'))
                ->editColumn('amount', fn($payment) => '৳ ' . number_format($payment->amount, 2))
                ->editColumn('payment_method', fn($payment) => $payment->method_display)
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereHas('invoice.customer', fn($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->filterColumn('invoice_number', function ($query, $keyword) {
                    $query->whereHas('invoice', fn($q) => $q->where('invoice_number', 'like', "%{$keyword}%"));
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('payments.index');
    }

    /**
     * Show create payment form (for invoice)
     */
    public function create(Request $request)
    {
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('customer')->findOrFail($request->invoice_id);
        }

        $invoices = Invoice::with('customer')
            ->whereRaw('total_amount > total_paid')
            ->orderBy('invoice_date', 'desc')
            ->get();

        $cashAccounts = Account::whereIn('type', ['asset'])
            ->where(function ($q) {
                $q->where('code', 'like', '111%')  // Cash accounts
                    ->orWhere('code', 'like', '112%'); // Bank accounts
            })
            ->active()
            ->get();

        return view('payments.create', compact('invoice', 'invoices', 'cashAccounts'));
    }

    /**
     * Store payment
     * Fixed: Using StorePaymentRequest for validation
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($request->invoice_id);

            // Validate payment amount doesn't exceed outstanding
            $outstanding = $invoice->outstanding_balance;
            if ($request->amount > $outstanding) {
                return response()->json([
                    'success' => false,
                    'message' => "Payment amount (৳{$request->amount}) exceeds outstanding balance (৳{$outstanding})",
                ], 422);
            }

            $payment = $this->paymentService->recordPayment($invoice, $request->validated());

            // Sync to customer ledger
            if ($payment->transaction_id) {
                $transaction = Transaction::find($payment->transaction_id);
                if ($transaction) {
                    TransactionObserver::syncToCustomerLedger($transaction);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment' => [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => number_format($payment->amount, 2),
                    'payment_date' => $payment->payment_date->format('d M Y'),
                ],
                'invoice' => [
                    'id' => $invoice->id,
                    'outstanding_balance' => number_format($invoice->fresh()->outstanding_balance, 2),
                    'is_paid' => $invoice->fresh()->is_paid,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment recording failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show payment details
     */
    public function show(InvoicePayment $payment)
    {
        $payment->load([
            'invoice.customer',
            'account',
            'transaction.entries.account',
            'recordedByUser',
        ]);

        return view('payments.show', compact('payment'));
    }

    /**
     * Delete payment
     */
    public function destroy(InvoicePayment $payment): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = $payment->invoice;

            // Delete customer ledger entry first
            if ($payment->transaction_id) {
                $transaction = Transaction::find($payment->transaction_id);
                if ($transaction) {
                    TransactionObserver::deleteCustomerLedger($transaction);
                }
            }

            $this->paymentService->deletePayment($payment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
                'invoice' => [
                    'id' => $invoice->id,
                    'outstanding_balance' => number_format($invoice->fresh()->outstanding_balance, 2),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print payment receipt
     */
    public function print(InvoicePayment $payment)
    {
        $payment->load([
            'invoice.customer',
            'account',
        ]);

        return view('payments.print', compact('payment'));
    }

    /**
     * Get payments for invoice (AJAX)
     */
    public function getForInvoice(Invoice $invoice): JsonResponse
    {
        $payments = $invoice->payments()
            ->with('account')
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(fn($payment) => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payment_date' => $payment->payment_date->format('d M Y'),
                'amount' => number_format($payment->amount, 2),
                'method' => $payment->method_display,
                'account' => $payment->account->name,
            ]);

        return response()->json([
            'success' => true,
            'payments' => $payments,
            'total_paid' => number_format($invoice->total_paid, 2),
            'outstanding' => number_format($invoice->outstanding_balance, 2),
        ]);
    }
}