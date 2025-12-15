@extends('adminlte::page')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Invoice {{ $invoice->invoice_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('sales.print', $invoice) }}" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <a href="{{ route('sales.edit', $invoice) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-danger btn-sm delete-invoice" data-id="{{ $invoice->id }}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Invoice Header -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Invoice Details</h5>
                            <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                            <p><strong>Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}</p>
                            <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Customer</h5>
                            <p><strong>{{ $invoice->customer->name }}</strong></p>
                            <p>{{ $invoice->customer->phone }}</p>
                            <p>{{ $invoice->customer->address }}</p>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <h5>Line Items</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Discount</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>৳ {{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ $item->discount_percent }}%</td>
                                <td>৳ {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <div class="form-group row">
                                <label class="col-md-6">Subtotal:</label>
                                <div class="col-md-6 text-right">৳ {{ number_format($invoice->subtotal, 2) }}</div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-6">Tax:</label>
                                <div class="col-md-6 text-right">৳ {{ number_format($invoice->tax_amount, 2) }}</div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-6"><strong>Total:</strong></label>
                                <div class="col-md-6 text-right"><strong>৳ {{ number_format($invoice->total_amount, 2) }}</strong></div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-6">Paid:</label>
                                <div class="col-md-6 text-right">৳ {{ number_format($invoice->total_paid, 2) }}</div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-6"><strong>Outstanding:</strong></label>
                                <div class="col-md-6 text-right"><strong class="text-danger">৳ {{ number_format($invoice->outstanding_balance, 2) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Deliveries -->
                    @if($invoice->deliveries->count() > 0)
                    <hr>
                    <h5>Deliveries</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Challan #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->deliveries as $delivery)
                            <tr>
                                <td>{{ $delivery->challan_number }}</td>
                                <td>{{ $delivery->delivery_date->format('d M Y') }}</td>
                                <td>৳ {{ number_format($delivery->getTotalDeliveredAmount(), 2) }}</td>
                                <td>
                                    <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-info">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif

                    <!-- Payments -->
                    @if($invoice->outstanding_balance > 0)
                    <hr>
                    <h5>Record Payment</h5>
                    <form id="payment-form">
                        @csrf
                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Amount</label>
                                <input type="number" name="amount" class="form-control" step="0.01" max="{{ $invoice->outstanding_balance }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Method</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Account</label>
                                <select name="account_id" class="form-control" required>
                                    <option value="">-- Select Account --</option>
                                    @foreach($cashAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-success btn-block">Record Payment</button>
                            </div>
                        </div>
                    </form>
                    @endif

                    @if($invoice->payments->count() > 0)
                    <h5 class="mt-4">Payment History</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                <td>৳ {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger delete-payment" data-id="{{ $payment->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Record payment
    $('#payment-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ route('sales.payments.store') }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire('Success!', response.message, 'success');
                location.reload();
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON.message, 'error');
            }
        });
    });

    // Delete invoice
    $('.delete-invoice').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Invoice?',
            text: 'This will also delete related deliveries and payments.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/sales/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function() {
                        Swal.fire('Deleted!', 'Invoice deleted successfully', 'success');
                        setTimeout(() => window.location.href = '{{ route('sales.index') }}', 1500);
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message, 'error');
                    }
                });
            }
        });
    });

    // Delete payment
    $('.delete-payment').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Payment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/payments/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function() {
                        Swal.fire('Deleted!', 'Payment deleted successfully', 'success');
                        location.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
