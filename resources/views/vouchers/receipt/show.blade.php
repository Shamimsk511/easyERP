@extends('adminlte::page')

@section('title', 'Receipt Voucher - ' . $receiptVoucher->voucher_number)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Receipt Voucher Details</h1>
        <div>
            <a href="{{ route('vouchers.receipt.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            @if($receiptVoucher->canEdit())
                <a href="{{ route('vouchers.receipt.edit', $receiptVoucher->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            @if($receiptVoucher->canCancel())
                <button type="button" class="btn btn-warning" id="cancelBtn">
                    <i class="fas fa-ban"></i> Cancel Voucher
                </button>
            @endif
            @if($receiptVoucher->status === 'draft')
                <button type="button" class="btn btn-danger" id="deleteBtn">
                    <i class="fas fa-trash"></i> Delete
                </button>
            @endif
            <button type="button" class="btn btn-info" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <!-- Left Column - Voucher Details -->
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice"></i> Voucher Information
                    </h3>
                    <div class="card-tools">
                        @if($receiptVoucher->status === 'posted')
                            <span class="badge badge-success badge-lg">Posted</span>
                        @elseif($receiptVoucher->status === 'draft')
                            <span class="badge badge-secondary badge-lg">Draft</span>
                        @else
                            <span class="badge badge-danger badge-lg">Cancelled</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Voucher Number:</th>
                                    <td><strong class="text-primary">{{ $receiptVoucher->voucher_number }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Receipt Date:</th>
                                    <td>{{ $receiptVoucher->receipt_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Payment Method:</th>
                                    <td>
                                        @php
                                            $badges = [
                                                'cash' => 'badge-success',
                                                'bank' => 'badge-primary',
                                                'cheque' => 'badge-info',
                                                'mobile_banking' => 'badge-warning',
                                            ];
                                            $badge = $badges[$receiptVoucher->payment_method] ?? 'badge-secondary';
                                        @endphp
                                        <span class="badge {{ $badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $receiptVoucher->payment_method)) }}
                                        </span>
                                    </td>
                                </tr>
                                @if($receiptVoucher->payment_method === 'cheque' && $receiptVoucher->cheque_number)
                                    <tr>
                                        <th>Cheque Number:</th>
                                        <td>{{ $receiptVoucher->cheque_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Cheque Date:</th>
                                        <td>{{ $receiptVoucher->cheque_date ? $receiptVoucher->cheque_date->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Bank Name:</th>
                                        <td>{{ $receiptVoucher->bank_name ?? 'N/A' }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Amount Received:</th>
                                    <td>
                                        <h3 class="text-success mb-0">
                                            {{ number_format($receiptVoucher->amount, 2) }}
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Received In:</th>
                                    <td>
                                        <span class="badge badge-success">
                                            {{ $receiptVoucher->receivedInAccount->code }}
                                        </span>
                                        {{ $receiptVoucher->receivedInAccount->name }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($receiptVoucher->status === 'posted')
                                            <span class="badge badge-success">Posted</span>
                                        @elseif($receiptVoucher->status === 'draft')
                                            <span class="badge badge-secondary">Draft</span>
                                        @else
                                            <span class="badge badge-danger">Cancelled</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fas fa-align-left"></i> Description</h5>
                            <p class="text-muted">{{ $receiptVoucher->description }}</p>
                        </div>
                    </div>

                    @if($receiptVoucher->notes)
                        <div class="row">
                            <div class="col-md-12">
                                <h5><i class="fas fa-sticky-note"></i> Notes</h5>
                                <p class="text-muted">{{ $receiptVoucher->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Accounting Entries -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book"></i> Accounting Entries (Double Entry)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalDebit = 0;
                                $totalCredit = 0;
                            @endphp
                            @foreach($receiptVoucher->transaction->entries as $entry)
                                <tr>
                                    <td><span class="badge badge-secondary">{{ $entry->account->code }}</span></td>
                                    <td>{{ $entry->account->name }}</td>
                                    <td class="text-right">
                                        @if($entry->type === 'debit')
                                            <strong class="text-danger">{{ number_format($entry->amount, 2) }}</strong>
                                            @php $totalDebit += $entry->amount; @endphp
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($entry->type === 'credit')
                                            <strong class="text-success">{{ number_format($entry->amount, 2) }}</strong>
                                            @php $totalCredit += $entry->amount; @endphp
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="2" class="text-right">Total:</th>
                                <th class="text-right text-danger">{{ number_format($totalDebit, 2) }}</th>
                                <th class="text-right text-success">{{ number_format($totalCredit, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-center">
                                    @if($totalDebit == $totalCredit)
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Balanced</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Not Balanced</span>
                                    @endif
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column - Customer Details -->
        <div class="col-md-4">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Customer Information</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="profile-user-img img-fluid img-circle bg-success d-inline-flex align-items-center justify-content-center" 
                             style="width: 100px; height: 100px; font-size: 48px; color: white;">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="profile-username text-center mt-2">{{ $receiptVoucher->customer->name }}</h3>
                        <p class="text-muted text-center">
                            <span class="badge badge-info">{{ $receiptVoucher->customer->customer_code }}</span>
                        </p>
                    </div>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b><i class="fas fa-phone mr-1"></i> Phone</b>
                            <a class="float-right">{{ $receiptVoucher->customer->phone }}</a>
                        </li>
                        @if($receiptVoucher->customer->email)
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                <a class="float-right">{{ $receiptVoucher->customer->email }}</a>
                            </li>
                        @endif
                        @if($receiptVoucher->customer->address)
                            <li class="list-group-item">
                                <b><i class="fas fa-map-marker-alt mr-1"></i> Address</b>
                                <p class="mb-0 mt-1">{{ $receiptVoucher->customer->address }}</p>
                            </li>
                        @endif
                        <li class="list-group-item">
                            <b><i class="fas fa-book mr-1"></i> Ledger Account</b>
                            <a class="float-right">
                                <span class="badge badge-secondary">{{ $receiptVoucher->customerAccount->code }}</span>
                                {{ $receiptVoucher->customerAccount->name }}
                            </a>
                        </li>
                    </ul>

                    <a href="{{ route('customers.show', $receiptVoucher->customer->id) }}" class="btn btn-primary btn-block">
                        <i class="fas fa-eye"></i> View Full Details
                    </a>
                    <a href="{{ route('customers.ledger', $receiptVoucher->customer->id) }}" class="btn btn-success btn-block">
                        <i class="fas fa-book"></i> View Ledger
                    </a>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> System Information</h3>
                </div>
                <div class="card-body">
                    <p><strong>Transaction ID:</strong><br>
                        <span class="badge badge-info">{{ $receiptVoucher->transaction_id }}</span>
                    </p>
                    <p><strong>Created At:</strong><br>
                        {{ $receiptVoucher->created_at->format('d M Y, h:i A') }}
                    </p>
                    @if($receiptVoucher->updated_at != $receiptVoucher->created_at)
                        <p><strong>Last Updated:</strong><br>
                            {{ $receiptVoucher->updated_at->format('d M Y, h:i A') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
.profile-user-img {
    border: 3px solid #adb5bd;
    margin: 0 auto;
    padding: 3px;
}

@media print {
    .btn, .card-tools, .card-header .badge {
        display: none !important;
    }
    .content-header {
        display: none !important;
    }
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Cancel voucher
    $('#cancelBtn').on('click', function() {
        Swal.fire({
            title: 'Cancel Receipt Voucher?',
            text: "This will void the transaction. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('vouchers.receipt.cancel', $receiptVoucher->id) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });

    // Delete voucher
    $('#deleteBtn').on('click', function() {
        Swal.fire({
            title: 'Delete Receipt Voucher?',
            text: "This action cannot be undone!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('vouchers.receipt.destroy', $receiptVoucher->id) }}',
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '{{ route('vouchers.receipt.index') }}';
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });
});
</script>
@stop
