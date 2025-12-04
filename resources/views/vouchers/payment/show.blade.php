@extends('adminlte::page')

@section('title', 'Payment Voucher Details')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Payment Voucher Details</h1>
        <div>
            @if($paymentVoucher->canEdit())
                <a href="{{ route('vouchers.payment.edit', $paymentVoucher->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('vouchers.payment.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Main Details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <strong>{{ $paymentVoucher->voucher_number }}</strong>
                    @if($paymentVoucher->status === 'posted')
                        <span class="badge badge-success ml-2">Posted</span>
                    @elseif($paymentVoucher->status === 'draft')
                        <span class="badge badge-secondary ml-2">Draft</span>
                    @elseif($paymentVoucher->status === 'cancelled')
                        <span class="badge badge-danger ml-2">Cancelled</span>
                    @endif
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Payment Date:</th>
                                <td>{{ $paymentVoucher->payment_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Payment Method:</th>
                                <td>
                                    @php
                                        $methodBadges = [
                                            'cash' => 'badge-success',
                                            'bank' => 'badge-primary',
                                            'cheque' => 'badge-info',
                                            'mobile_banking' => 'badge-warning',
                                        ];
                                        $badge = $methodBadges[$paymentVoucher->payment_method] ?? 'badge-secondary';
                                    @endphp
                                    <span class="badge {{ $badge }}">
                                        {{ ucfirst(str_replace('_', ' ', $paymentVoucher->payment_method)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td><strong class="text-primary" style="font-size: 1.2em;">৳ {{ number_format($paymentVoucher->amount, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if($paymentVoucher->payment_method === 'cheque')
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Cheque Number:</th>
                                    <td>{{ $paymentVoucher->cheque_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Cheque Date:</th>
                                    <td>{{ $paymentVoucher->cheque_date ? $paymentVoucher->cheque_date->format('d M Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Name:</th>
                                    <td>{{ $paymentVoucher->bank_name ?? '-' }}</td>
                                </tr>
                            </table>
                        @endif
                    </div>
                </div>

                <hr>

                <!-- Payee Information -->
                @if($paymentVoucher->payee_type && $payeeDetails)
                    <h5>Payee Information</h5>
                    <div class="alert alert-info">
                        <strong>{{ ucfirst($paymentVoucher->payee_type) }}:</strong> {{ $payeeDetails->name }}
                        @if($paymentVoucher->payee_type === 'vendor')
                            <a href="{{ route('vendors.show', $payeeDetails->id) }}" class="btn btn-sm btn-outline-primary float-right">
                                <i class="fas fa-eye"></i> View Vendor
                            </a>
                        @elseif($paymentVoucher->payee_type === 'customer')
                            <a href="{{ route('customers.show', $payeeDetails->id) }}" class="btn btn-sm btn-outline-primary float-right">
                                <i class="fas fa-eye"></i> View Customer
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Account Information -->
                <h5>Account Details</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="callout callout-danger">
                            <h6><i class="fas fa-minus-circle"></i> Paid From (Credit)</h6>
                            <p class="mb-0">
                                <strong>{{ $paymentVoucher->paidFromAccount->code }}</strong> - 
                                {{ $paymentVoucher->paidFromAccount->name }}
                            </p>
                            <small class="text-muted">{{ ucfirst($paymentVoucher->paidFromAccount->type) }} Account</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="callout callout-success">
                            <h6><i class="fas fa-plus-circle"></i> Paid To (Debit)</h6>
                            <p class="mb-0">
                                <strong>{{ $paymentVoucher->paidToAccount->code }}</strong> - 
                                {{ $paymentVoucher->paidToAccount->name }}
                            </p>
                            <small class="text-muted">{{ ucfirst($paymentVoucher->paidToAccount->type) }} Account</small>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <h5>Description</h5>
                <p class="border p-3 bg-light">{{ $paymentVoucher->description }}</p>

                @if($paymentVoucher->notes)
                    <h5>Notes</h5>
                    <p class="border p-3 bg-light">{{ $paymentVoucher->notes }}</p>
                @endif
            </div>
        </div>

        <!-- Transaction Entries -->
        @if($paymentVoucher->transaction)
        <div class="card">
            <div class="card-header bg-secondary">
                <h3 class="card-title">Double-Entry Transaction</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th width="10%">Account Code</th>
                            <th>Account Name</th>
                            <th width="15%" class="text-right">Debit (Dr.)</th>
                            <th width="15%" class="text-right">Credit (Cr.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentVoucher->transaction->entries as $entry)
                        <tr>
                            <td>
                                <a href="{{ route('accounts.show', $entry->account->id) }}">
                                    <strong>{{ $entry->account->code }}</strong>
                                </a>
                            </td>
                            <td>{{ $entry->account->name }}</td>
                            <td class="text-right">
                                @if($entry->type === 'debit')
                                    <strong class="text-danger">৳ {{ number_format($entry->amount, 2) }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">
                                @if($entry->type === 'credit')
                                    <strong class="text-success">৳ {{ number_format($entry->amount, 2) }}</strong>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-weight-bold">
                        <tr>
                            <td colspan="2" class="text-right">Total:</td>
                            <td class="text-right text-danger">৳ {{ number_format($paymentVoucher->amount, 2) }}</td>
                            <td class="text-right text-success">৳ {{ number_format($paymentVoucher->amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle"></i> Transaction is balanced (Debit = Credit)
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Actions Card -->
<div class="card">
    <div class="card-header bg-primary">
        <h3 class="card-title">Actions</h3>
    </div>
    <div class="card-body">
        @if($paymentVoucher->canEdit())
            <a href="{{ route('vouchers.payment.edit', $paymentVoucher->id) }}" class="btn btn-primary btn-block">
                <i class="fas fa-edit"></i> Edit Voucher
            </a>
        @endif
        
        @if($paymentVoucher->canCancel())
            <button type="button" class="btn btn-warning btn-block" id="btn-cancel-voucher">
                <i class="fas fa-ban"></i> Cancel Voucher
            </button>
        @endif
        
        @if($paymentVoucher->status === 'draft')
            <button type="button" class="btn btn-danger btn-block" id="btn-delete-voucher">
                <i class="fas fa-trash"></i> Delete Voucher
            </button>
        @endif
        
        <hr>
        
        <!-- Print Buttons -->
        <a href="{{ route('vouchers.payment.print', $paymentVoucher->id) }}" 
           class="btn btn-info btn-block" target="_blank">
            <i class="fas fa-print"></i> Print Voucher
        </a>
        
        <button type="button" class="btn btn-secondary btn-block" onclick="window.print()">
            <i class="fas fa-print"></i> Quick Print
        </button>
    </div>
</div>


        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Voucher Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Created:</th>
                        <td>{{ $paymentVoucher->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @if($paymentVoucher->updated_at != $paymentVoucher->created_at)
                    <tr>
                        <th>Updated:</th>
                        <td>{{ $paymentVoucher->updated_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Status:</th>
                        <td>
                            @if($paymentVoucher->status === 'posted')
                                <span class="badge badge-success">Posted</span>
                            @elseif($paymentVoucher->status === 'draft')
                                <span class="badge badge-secondary">Draft</span>
                            @elseif($paymentVoucher->status === 'cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @endif
                        </td>
                    </tr>
                    @if($paymentVoucher->transaction)
                    <tr>
                        <th>Transaction:</th>
                        <td>
                            <a href="{{ route('transactions.show', $paymentVoucher->transaction->id) }}">
                                {{ $paymentVoucher->transaction->reference }}
                            </a>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .callout {
        border-left: 5px solid;
        padding: 15px;
        margin-bottom: 20px;
    }
    .callout-danger {
        border-color: #dc3545;
        background-color: #f8d7da;
    }
    .callout-success {
        border-color: #28a745;
        background-color: #d4edda;
    }
    @media print {
        .btn, .card-header, .breadcrumb, .content-header {
            display: none !important;
        }
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Cancel voucher
    $('#btn-cancel-voucher').on('click', function() {
        Swal.fire({
            title: 'Cancel this voucher?',
            text: "This will void the associated transaction.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('vouchers.payment.cancel', $paymentVoucher->id) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to cancel voucher'
                        });
                    }
                });
            }
        });
    });

    // Delete voucher
    $('#btn-delete-voucher').on('click', function() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('vouchers.payment.destroy', $paymentVoucher->id) }}',
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = '{{ route('vouchers.payment.index') }}';
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to delete voucher'
                        });
                    }
                });
            }
        });
    });
});
</script>
@stop
