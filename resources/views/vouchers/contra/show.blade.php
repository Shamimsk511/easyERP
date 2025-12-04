@extends('adminlte::page')

@section('title', 'Contra Voucher Details')

@section('content_header')
    <h1>Contra Voucher: {{ $contraVoucher->voucher_number }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Voucher Details -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Voucher Information</h3>
                <div class="card-tools">
                    @if($contraVoucher->status === 'draft')
                        <span class="badge badge-secondary">Draft</span>
                    @elseif($contraVoucher->status === 'posted')
                        <span class="badge badge-success">Posted</span>
                    @else
                        <span class="badge badge-danger">Cancelled</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%">Voucher Number:</th>
                                <td><strong class="text-primary">{{ $contraVoucher->voucher_number }}</strong></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>{{ $contraVoucher->contra_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Transfer Method:</th>
                                <td>
                                    @php
                                        $badges = [
                                            'cash' => 'badge-success',
                                            'bank_transfer' => 'badge-primary',
                                            'cheque' => 'badge-info',
                                            'online' => 'badge-warning',
                                        ];
                                        $badge = $badges[$contraVoucher->transfer_method] ?? 'badge-secondary';
                                    @endphp
                                    <span class="badge {{ $badge }}">
                                        {{ ucfirst(str_replace('_', ' ', $contraVoucher->transfer_method)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td><h4 class="text-success mb-0"><strong>৳ {{ number_format($contraVoucher->amount, 2) }}</strong></h4></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            @if($contraVoucher->cheque_number)
                            <tr>
                                <th width="40%">Cheque Number:</th>
                                <td>{{ $contraVoucher->cheque_number }}</td>
                            </tr>
                            @endif
                            @if($contraVoucher->cheque_date)
                            <tr>
                                <th>Cheque Date:</th>
                                <td>{{ $contraVoucher->cheque_date->format('d M Y') }}</td>
                            </tr>
                            @endif
                            @if($contraVoucher->bank_name)
                            <tr>
                                <th>Bank Name:</th>
                                <td>{{ $contraVoucher->bank_name }}</td>
                            </tr>
                            @endif
                            @if($contraVoucher->reference_number)
                            <tr>
                                <th>Reference Number:</th>
                                <td>{{ $contraVoucher->reference_number }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $contraVoucher->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                <h5><i class="fas fa-info-circle"></i> Description</h5>
                <p class="text-muted">{{ $contraVoucher->description }}</p>

                @if($contraVoucher->notes)
                    <h5><i class="fas fa-sticky-note"></i> Notes</h5>
                    <p class="text-muted">{{ $contraVoucher->notes }}</p>
                @endif
            </div>
        </div>

        <!-- Transfer Flow -->
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-arrows-alt-h"></i> Transfer Flow</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-5">
                        <div class="callout callout-danger">
                            <h5><i class="fas fa-minus-circle"></i> From Account</h5>
                            <h4><strong>{{ $contraVoucher->fromAccount->name }}</strong></h4>
                            <p><small class="text-muted">{{ $contraVoucher->fromAccount->code }}</small></p>
                            <p><span class="badge badge-danger">- ৳ {{ number_format($contraVoucher->amount, 2) }}</span></p>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-right fa-3x text-primary"></i>
                    </div>
                    <div class="col-md-5">
                        <div class="callout callout-success">
                            <h5><i class="fas fa-plus-circle"></i> To Account</h5>
                            <h4><strong>{{ $contraVoucher->toAccount->name }}</strong></h4>
                            <p><small class="text-muted">{{ $contraVoucher->toAccount->code }}</small></p>
                            <p><span class="badge badge-success">+ ৳ {{ number_format($contraVoucher->amount, 2) }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounting Entries -->
        @if($contraVoucher->transaction)
        <div class="card">
            <div class="card-header bg-secondary">
                <h3 class="card-title"><i class="fas fa-book"></i> Accounting Entries</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Account</th>
                            <th>Code</th>
                            <th class="text-right">Debit (৳)</th>
                            <th class="text-right">Credit (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalDebit = 0; $totalCredit = 0; @endphp
                        @foreach($contraVoucher->transaction->entries as $entry)
                        <tr>
                            <td>
                                {{ $entry->account->name }}
                                @if($entry->memo)
                                    <br><small class="text-muted">{{ $entry->memo }}</small>
                                @endif
                            </td>
                            <td>{{ $entry->account->code }}</td>
                            <td class="text-right">
                                @if($entry->type === 'debit')
                                    <strong>{{ number_format($entry->amount, 2) }}</strong>
                                    @php $totalDebit += $entry->amount; @endphp
                                @else - @endif
                            </td>
                            <td class="text-right">
                                @if($entry->type === 'credit')
                                    <strong>{{ number_format($entry->amount, 2) }}</strong>
                                    @php $totalCredit += $entry->amount; @endphp
                                @else - @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="2" class="text-right">Total:</th>
                            <th class="text-right">{{ number_format($totalDebit, 2) }}</th>
                            <th class="text-right">{{ number_format($totalCredit, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
                
                @if($totalDebit == $totalCredit)
                    <div class="alert alert-success mt-2 mb-0">
                        <i class="fas fa-check-circle"></i> Entries are balanced
                    </div>
                @else
                    <div class="alert alert-danger mt-2 mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Warning: Entries not balanced!
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Actions Sidebar -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="card-body">
                @if($contraVoucher->can_edit)
                    <a href="{{ route('vouchers.contra.edit', $contraVoucher->id) }}" class="btn btn-warning btn-block">
                        <i class="fas fa-edit"></i> Edit Voucher
                    </a>
                @endif

                @if($contraVoucher->can_cancel)
                    <button type="button" class="btn btn-warning btn-block" id="cancel-btn">
                        <i class="fas fa-ban"></i> Cancel Voucher
                    </button>
                @endif

                @if($contraVoucher->status === 'draft')
                    <button type="button" class="btn btn-danger btn-block" id="delete-btn">
                        <i class="fas fa-trash"></i> Delete Voucher
                    </button>
                @endif

                <a href="{{ route('vouchers.contra.index') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-list"></i> Back to List
                </a>

                <hr>

                <button type="button" class="btn btn-info btn-block" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Voucher
                </button>

                <a href="{{ route('vouchers.contra.create') }}" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> New Contra Voucher
                </a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header bg-secondary">
                <h3 class="card-title">Quick Links</h3>
            </div>
            <div class="card-body p-2">
                <a href="#" class="btn btn-sm btn-outline-danger btn-block">
                    <i class="fas fa-book"></i> View From Account
                </a>
                <a href="#" class="btn btn-sm btn-outline-success btn-block">
                    <i class="fas fa-book"></i> View To Account
                </a>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
@media print {
    .btn, .breadcrumb, .content-header, .col-md-4 { display: none !important; }
    .col-md-8 { width: 100% !important; }
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#cancel-btn').click(function() {
        Swal.fire({
            title: 'Cancel Voucher?',
            text: "This will void the transaction entries!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("vouchers.contra.cancel", $contraVoucher->id) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Cancelled!', response.message, 'success').then(() => location.reload());
                        }
                    }
                });
            }
        });
    });

    $('#delete-btn').click(function() {
        Swal.fire({
            title: 'Delete Voucher?',
            text: "This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("vouchers.contra.destroy", $contraVoucher->id) }}',
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                window.location.href = '{{ route("vouchers.contra.index") }}';
                            });
                        }
                    }
                });
            }
        });
    });
});
</script>
@stop
