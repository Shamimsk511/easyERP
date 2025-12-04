@extends('adminlte::page')

@section('title', 'Journal Voucher Details')

@section('content_header')
    <h1>Journal Voucher: {{ $journalVoucher->voucher_number }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Voucher Details -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-file-alt"></i> Voucher Information</h3>
                <div class="card-tools">
                    @if($journalVoucher->status === 'draft')
                        <span class="badge badge-secondary">Draft</span>
                    @elseif($journalVoucher->status === 'posted')
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
                                <td><strong class="text-primary">{{ $journalVoucher->voucher_number }}</strong></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>{{ $journalVoucher->journal_date->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>
                                    @if($journalVoucher->createdBy)
                                        <i class="fas fa-user"></i> {{ $journalVoucher->createdBy->name }}
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $journalVoucher->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-primary"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Balance Status</span>
                                @if($journalVoucher->is_balanced)
                                    <span class="info-box-number text-success">
                                        <i class="fas fa-check-circle"></i> Balanced
                                    </span>
                                @else
                                    <span class="info-box-number text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Not Balanced
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <h5><i class="fas fa-info-circle"></i> Description</h5>
                <p class="text-muted">{{ $journalVoucher->description }}</p>

                @if($journalVoucher->notes)
                    <h5><i class="fas fa-sticky-note"></i> Notes</h5>
                    <p class="text-muted">{{ $journalVoucher->notes }}</p>
                @endif
            </div>
        </div>

        <!-- Journal Entries -->
        @if($journalVoucher->transaction)
        <div class="card">
            <div class="card-header bg-secondary">
                <h3 class="card-title"><i class="fas fa-book"></i> Journal Entries</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="15%">Account Code</th>
                                <th width="35%">Account Name</th>
                                <th width="20%">Debit (৳)</th>
                                <th width="20%">Credit (৳)</th>
                                <th width="5%">Memo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalDebit = 0; $totalCredit = 0; @endphp
                            @foreach($journalVoucher->transaction->entries as $index => $entry)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td><strong>{{ $entry->account->code }}</strong></td>
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
                                <td>
                                    @if($entry->memo)
                                        <small class="text-muted">{{ $entry->memo }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="3" class="text-right">Total:</th>
                                <th class="text-right text-danger">{{ number_format($totalDebit, 2) }}</th>
                                <th class="text-right text-success">{{ number_format($totalCredit, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-footer">
                    @if(abs($totalDebit - $totalCredit) < 0.01)
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> Entries are balanced
                            <span class="float-right">
                                <strong>Total Amount: ৳ {{ number_format($totalDebit, 2) }}</strong>
                            </span>
                        </div>
                    @else
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Warning: Entries are not balanced!
                            <span class="float-right">
                                <strong>Difference: ৳ {{ number_format(abs($totalDebit - $totalCredit), 2) }}</strong>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Affected Accounts Summary -->
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Affected Accounts Summary</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-danger"><i class="fas fa-minus-circle"></i> Debited Accounts</h5>
                        <ul class="list-group">
                            @foreach($journalVoucher->transaction->entries->where('type', 'debit') as $entry)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $entry->account->name }}
                                <span class="badge badge-danger badge-pill">
                                    ৳ {{ number_format($entry->amount, 2) }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-success"><i class="fas fa-plus-circle"></i> Credited Accounts</h5>
                        <ul class="list-group">
                            @foreach($journalVoucher->transaction->entries->where('type', 'credit') as $entry)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $entry->account->name }}
                                <span class="badge badge-success badge-pill">
                                    ৳ {{ number_format($entry->amount, 2) }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-dark">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="card-body">
                @if($journalVoucher->can_edit)
                    <a href="{{ route('vouchers.journal.edit', $journalVoucher->id) }}" class="btn btn-warning btn-block">
                        <i class="fas fa-edit"></i> Edit Voucher
                    </a>
                @endif

                @if($journalVoucher->can_cancel)
                    <button type="button" class="btn btn-warning btn-block" id="cancel-btn">
                        <i class="fas fa-ban"></i> Cancel Voucher
                    </button>
                @endif

                @if($journalVoucher->status === 'draft')
                    <button type="button" class="btn btn-danger btn-block" id="delete-btn">
                        <i class="fas fa-trash"></i> Delete Voucher
                    </button>
                @endif

                <a href="{{ route('vouchers.journal.index') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-list"></i> Back to List
                </a>

                <hr>

                <button type="button" class="btn btn-info btn-block" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Voucher
                </button>

                <a href="{{ route('vouchers.journal.create') }}" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> New Journal Voucher
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title">Statistics</h3>
            </div>
            <div class="card-body">
                <div class="info-box bg-light mb-3">
                    <span class="info-box-icon bg-danger"><i class="fas fa-arrow-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Debit</span>
                        <span class="info-box-number">৳ {{ number_format($journalVoucher->total_debit, 2) }}</span>
                    </div>
                </div>

                <div class="info-box bg-light mb-3">
                    <span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Credit</span>
                        <span class="info-box-number">৳ {{ number_format($journalVoucher->total_credit, 2) }}</span>
                    </div>
                </div>

                <div class="info-box bg-light">
                    <span class="info-box-icon bg-primary"><i class="fas fa-list"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Entries</span>
                        <span class="info-box-number">{{ $journalVoucher->transaction->entries->count() }}</span>
                    </div>
                </div>
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
                    url: '{{ route("vouchers.journal.cancel", $journalVoucher->id) }}',
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
                    url: '{{ route("vouchers.journal.destroy", $journalVoucher->id) }}',
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                window.location.href = '{{ route("vouchers.journal.index") }}';
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
