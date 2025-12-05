<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Account;
use App\Services\Sales\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment form data (AJAX)
     */
    public function create(Request $request)
    {
        $invoiceId = $request->get('invoice_id');
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $cashBankAccounts = Account::whereIn('code', ['1110', '1120'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'outstanding_balance' => $invoice->outstanding_balance,
            ],
            'accounts' => $cashBankAccounts,
            'payment_methods' => ['cash', 'bank', 'cheque', 'online'],
        ]);
    }

    /**
     * Record payment (AJAX)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id'      => 'required|exists:invoices,id',
            'payment_date'    => 'required|date',
            'amount'          => 'required|numeric|min:0.01',
            'payment_method'  => 'required|in:cash,bank,cheque,online',
            'account_id'      => 'required|exists:accounts,id',
            'cheque_number'   => 'nullable|string',
            'cheque_date'     => 'nullable|date',
            'bank_name'       => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        try {
            $invoice = Invoice::find($validated['invoice_id']);
            $payment = $this->paymentService->recordPayment($invoice, $validated);

            return response()->json([
                'success'         => true,
                'message'         => 'Payment recorded successfully!',
                'payment'         => $payment,
                'new_outstanding' => $invoice->outstanding_balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete payment (AJAX, reverse transaction)
     */
    public function destroy(InvoicePayment $payment)
    {
        try {
            $this->paymentService->deletePayment($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted and reversed successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
