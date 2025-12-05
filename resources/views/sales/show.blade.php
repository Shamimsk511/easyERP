@extends('adminlte::page')
@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Invoice #{{ $invoice->invoice_number }}</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            @if(!$invoice->deleted_at)
                <a href="{{ route('sales.edit', $invoice) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button type="button" class="btn btn-danger btn-sm" id="deleteInvoiceBtn">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="printBtn">
                    <i class="fas fa-print"></i> Print
                </button>
            @else
                <span class="badge badge-danger">Deleted by {{ $invoice->deletedBy->name ?? 'Unknown' }}</span>
            @endif
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Invoice Details Card -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <h5 class="card-title">Invoice Details</h5>
                    <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}</p>
                    <p><strong>Due Date:</strong> {{ $invoice->due_date?->format('d M Y') ?? '-' }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-{{ $invoice->delivery_status === 'delivered' ? 'success' : ($invoice->delivery_status === 'partial' ? 'info' : 'warning') }}">{{ ucfirst($invoice->delivery_status) }}</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <h5 class="card-title">Customer Details</h5>
                    <p><strong>{{ $invoice->customer->name }}</strong></p>
                    <p><i class="fas fa-phone"></i> {{ $invoice->customer->phone }}</p>
                    <p><i class="fas fa-map-marker-alt"></i> {{ $invoice->customer->address ?? '-' }}, {{ $invoice->customer->city ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#items-tab" data-toggle="tab">Items</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#delivery-tab" data-toggle="tab">Deliveries</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#payment-tab" data-toggle="tab">Payments</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#totals-tab" data-toggle="tab">Totals</a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Items Tab -->
        <div class="tab-pane fade show active" id="items-tab">
            <div class="card mt-3">
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Amount</th>
                                <th>Delivered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->unit?->symbol ?? '-' }}</td>
                                    <td>৳ {{ number_format($item->unit_price, 2) }}</td>
                                    <td>{{ $item->discount_percent }}% (৳ {{ number_format($item->discount_amount, 2) }})</td>
                                    <td>৳ {{ number_format($item->line_total, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $item->delivered_quantity >= $item->quantity ? 'success' : 'warning' }}">
                                            {{ $item->delivered_quantity }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delivery Tab -->
        <div class="tab-pane fade" id="delivery-tab">
            <div class="card mt-3">
                <div class="card-header">
                    @if(!$invoice->deleted_at && $invoice->delivery_status !== 'delivered')
                        <button type="button" class="btn btn-success btn-sm float-right" id="createDeliveryBtn">
                            <i class="fas fa-plus"></i> Create Delivery
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($invoice->deliveries->count() > 0)
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Challan #</th>
                                    <th>Date</th>
                                    <th>Delivered By</th>
                                    <th>Items</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->deliveries as $delivery)
                                    <tr>
                                        <td>{{ $delivery->challan_number }}</td>
                                        <td>{{ $delivery->delivery_date->format('d M Y') }}</td>
                                        <td>{{ $delivery->deliveredBy->name ?? '-' }}</td>
                                        <td>{{ $delivery->items->count() }} items</td>
                                        <td>
                                            <a href="{{ route('deliveries.print', $delivery) }}" class="btn btn-info btn-xs" target="_blank">
                                                <i class="fas fa-print"></i> Print
                                            </a>
                                            @if(!$delivery->deleted_at && !$invoice->deleted_at)
                                                <button type="button" class="btn btn-danger btn-xs delete-delivery" data-id="{{ $delivery->id }}">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">No deliveries yet</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment Tab -->
        <div class="tab-pane fade" id="payment-tab">
            <div class="card mt-3">
                <div class="card-header">
                    @if(!$invoice->deleted_at && $invoice->outstanding_balance > 0)
                        <button type="button" class="btn btn-primary btn-sm float-right" id="recordPaymentBtn">
                            <i class="fas fa-plus"></i> Record Payment
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if($invoice->payments->count() > 0)
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Payment #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Account</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_number }}</td>
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td>৳ {{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ ucfirst($payment->payment_method) }}</td>
                                        <td>{{ $payment->account->name }}</td>
                                        <td>
                                            @if(!$payment->deleted_at && !$invoice->deleted_at)
                                                <button type="button" class="btn btn-danger btn-xs delete-payment" data-id="{{ $payment->id }}">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">No payments recorded</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Totals Tab -->
        <div class="tab-pane fade" id="totals-tab">
            <div class="card mt-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right">৳ {{ number_format($invoice->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Discounts:</strong></td>
                                    <td class="text-right">৳ {{ number_format($invoice->discount_amount, 2) }}</td>
                                </tr>
                                @if($invoice->tax_amount)
                                    <tr>
                                        <td><strong>Tax:</strong></td>
                                        <td class="text-right">৳ {{ number_format($invoice->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="table-active">
                                    <td><h5>Total Amount:</h5></td>
                                    <td class="text-right"><h5>৳ {{ number_format($invoice->total_amount, 2) }}</h5></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Paid:</strong></td>
                                    <td class="text-right">৳ {{ number_format($invoice->total_paid, 2) }}</td>
                                </tr>
                                <tr class="table-warning">
                                    <td><strong>Outstanding:</strong></td>
                                    <td class="text-right"><h5 class="text-danger">৳ {{ number_format($invoice->outstanding_balance, 2) }}</h5></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($invoice->internal_notes)
                                <div class="alert alert-info">
                                    <h6>Internal Notes:</h6>
                                    {{ $invoice->internal_notes }}
                                </div>
                            @endif
                            @if($invoice->customer_notes)
                                <div class="alert alert-light border">
                                    <h6>Customer Notes:</h6>
                                    {{ $invoice->customer_notes }}
                                </div>
                            @endif
                            <p class="text-muted" style="font-size: 0.85rem;">
                                <strong>Outstanding at Creation:</strong> ৳ {{ number_format($invoice->outstanding_at_creation, 2) }}<br>
                                Created: {{ $invoice->created_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
$(function() {
    const invoiceId = {{ $invoice->id }};

    // Delete invoice
    $('#deleteInvoiceBtn').on('click', function() {
        Swal.fire({
            title: 'Delete Invoice?',
            text: 'This will soft-delete and revert all transactions',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete It!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/sales/' + invoiceId,
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function() {
                        Swal.fire('Deleted!', 'Invoice deleted successfully', 'success')
                            .then(() => window.location.href = '{{ route("sales.index") }}');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error deleting invoice', 'error');
                    }
                });
            }
        });
    });

    // Print invoice
    $('#printBtn').on('click', function() {
        window.print();
    });

    // Create delivery
    $('#createDeliveryBtn').on('click', function() {
        loadDeliveryModal();
    });

    function loadDeliveryModal() {
        $.ajax({
            url: '/deliveries/create?invoice_id=' + invoiceId,
            success: function(data) {
                showDeliveryModal(data);
            },
            error: function() {
                Swal.fire('Error', 'Error loading delivery form', 'error');
            }
        });
    }

    function showDeliveryModal(data) {
        let itemsHtml = '';
        $.each(data.items, function(i, item) {
            itemsHtml += `
                <tr>
                    <td>${item.description}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit}</td>
                    <td>
                        <input type="hidden" name="items[${i}][invoice_item_id]" value="${item.id}">
                        <input type="number" name="items[${i}][delivered_quantity]" 
                               class="form-control form-control-sm" 
                               min="0.01" step="0.01" max="${item.remaining}" 
                               value="${item.remaining}" required>
                    </td>
                </tr>
            `;
        });

        let usersHtml = '';
        $.each(data.users, function(i, user) {
            usersHtml += `<option value="${user.id}" ${user.id == {{ auth()->id() }} ? 'selected' : ''}>${user.name}</option>`;
        });

        const modal = `
            <div class="modal fade" id="deliveryModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <form id="deliveryForm">
                            <input type="hidden" name="invoice_id" value="${invoiceId}">
                            <div class="modal-header bg-success">
                                <h5 class="modal-title text-white">Create Delivery Challan</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="delivery_date">Delivery Date</label>
                                            <input type="date" id="delivery_date" name="delivery_date" 
                                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="delivery_method">Method</label>
                                            <input type="text" id="delivery_method" name="delivery_method" 
                                                   class="form-control" value="auto">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="driver_name">Driver Name</label>
                                            <input type="text" id="driver_name" name="driver_name" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="delivered_by_user_id">Delivered By</label>
                                    <select id="delivered_by_user_id" name="delivered_by_user_id" class="form-control" required>
                                        ${usersHtml}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Items to Deliver</label>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Qty</th>
                                                    <th>Unit</th>
                                                    <th>Deliver Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${itemsHtml}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="delivery_notes">Notes</label>
                                    <textarea id="delivery_notes" name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Create & Print
                                </button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modal);
        const $modal = $('#deliveryModal');
        $modal.modal('show');

        $modal.on('hidden.bs.modal', function() {
            $modal.remove();
        });

        $('#deliveryForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/deliveries',
                method: 'POST',
                data: $(this).serialize(),
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: function(response) {
                    $modal.modal('hide');
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error creating delivery', 'error');
                }
            });
        });
    }

    // Delete delivery
    $(document).on('click', '.delete-delivery', function() {
        const deliveryId = $(this).data('id');
        Swal.fire({
            title: 'Delete Delivery?',
            text: 'This will revert stock and transactions',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete It!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/deliveries/' + deliveryId,
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function() {
                        Swal.fire('Deleted!', 'Delivery deleted successfully', 'success')
                            .then(() => window.location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error deleting delivery', 'error');
                    }
                });
            }
        });
    });

    // Record payment
    $('#recordPaymentBtn').on('click', function() {
        $.ajax({
            url: '/payments/create?invoice_id=' + invoiceId,
            success: function(data) {
                showPaymentModal(data);
            },
            error: function() {
                Swal.fire('Error', 'Error loading payment form', 'error');
            }
        });
    });

    function showPaymentModal(data) {
        let accountsHtml = '';
        $.each(data.accounts, function(i, account) {
            accountsHtml += `<option value="${account.id}">${account.code} - ${account.name}</option>`;
        });

        let methodsHtml = '';
        $.each(data.payment_methods, function(i, method) {
            methodsHtml += `<option value="${method}">${method.charAt(0).toUpperCase() + method.slice(1)}</option>`;
        });

        const modal = `
            <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form id="paymentForm">
                            <input type="hidden" name="invoice_id" value="${invoiceId}">
                            <div class="modal-header bg-primary">
                                <h5 class="modal-title text-white">Record Payment</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <p><strong>Invoice:</strong> ${data.invoice.invoice_number}</p>
                                    <p><strong>Outstanding:</strong> <span class="text-danger">৳ ${parseFloat(data.invoice.outstanding_balance).toFixed(2)}</span></p>
                                </div>
                                <div class="form-group">
                                    <label for="payment_date">Payment Date</label>
                                    <input type="date" id="payment_date" name="payment_date" 
                                           class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="amount">Amount <span class="text-danger">*</span></label>
                                    <input type="number" id="amount" name="amount" class="form-control" 
                                           min="0.01" step="0.01" max="${data.invoice.outstanding_balance}" 
                                           value="${data.invoice.outstanding_balance}" required>
                                </div>
                                <div class="form-group">
                                    <label for="payment_method">Payment Method</label>
                                    <select id="payment_method" name="payment_method" class="form-control" required>
                                        ${methodsHtml}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="account_id">Cash/Bank Account</label>
                                    <select id="account_id" name="account_id" class="form-control" required>
                                        ${accountsHtml}
                                    </select>
                                </div>
                                <div id="chequeFields" style="display: none;">
                                    <div class="form-group">
                                        <label for="cheque_number">Cheque Number</label>
                                        <input type="text" id="cheque_number" name="cheque_number" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="cheque_date">Cheque Date</label>
                                        <input type="date" id="cheque_date" name="cheque_date" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="bank_name">Bank Name</label>
                                        <input type="text" id="bank_name" name="bank_name" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="payment_notes">Notes</label>
                                    <textarea id="payment_notes" name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Record Payment
                                </button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modal);
        const $modal = $('#paymentModal');
        $modal.modal('show');

        $modal.on('hidden.bs.modal', function() {
            $modal.remove();
        });

        // Show cheque fields if method is cheque
        $modal.on('change', '#payment_method', function() {
            $('#chequeFields').toggle($(this).val() === 'cheque');
        });

        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '/payments',
                method: 'POST',
                data: $(this).serialize(),
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: function(response) {
                    $modal.modal('hide');
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error recording payment', 'error');
                }
            });
        });
    }

    // Delete payment
    $(document).on('click', '.delete-payment', function() {
        const paymentId = $(this).data('id');
        Swal.fire({
            title: 'Delete Payment?',
            text: 'This will revert the transaction',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete It!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/payments/' + paymentId,
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function() {
                        Swal.fire('Deleted!', 'Payment deleted successfully', 'success')
                            .then(() => window.location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Error deleting payment', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
