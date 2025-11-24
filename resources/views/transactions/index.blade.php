@extends('adminlte::page')

@section('title', 'Transactions')

@section('content_header')
    <h1>Journal Transactions</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Transactions</h3>
            <div class="card-tools">
                <a href="{{ route('transactions.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Transaction
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('transactions.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" placeholder="From Date" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" placeholder="To Date" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr id="transaction-row-{{ $transaction->id }}">
                                <td>{{ $transaction->date->format('d M Y') }}</td>
                                <td>{{ $transaction->reference ?? '-' }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td class="text-right">{{ number_format($transaction->getTotalDebits(), 2) }}</td>
                                <td>
                                    @if($transaction->status == 'posted')
                                        <span class="badge badge-success">Posted</span>
                                    @elseif($transaction->status == 'draft')
                                        <span class="badge badge-warning">Draft</span>
                                    @else
                                        <span class="badge badge-danger">Voided</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-info btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($transaction->status == 'draft')
                                        <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    
                                    <!-- Delete button with AJAX -->
                                    <button type="button" 
                                            class="btn btn-danger btn-sm delete-transaction" 
                                            data-id="{{ $transaction->id }}" 
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    
                                    @if($transaction->status == 'posted')
                                        <form action="{{ route('transactions.void', $transaction) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm void-transaction" onclick="return confirm('Void this transaction?')" title="Void">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer clearfix">
            {{ $transactions->links() }}
        </div>
    </div>
@stop

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('js')
<script>
    $(document).ready(function() {
        // Delete transaction with SweetAlert2
        $(document).on('click', '.delete-transaction', function(e) {
            e.preventDefault();
            var transactionId = $(this).data('id');
            var $row = $('#transaction-row-' + transactionId);
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the transaction and all related entries!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: '{{ url('transactions') }}/' + transactionId,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                
                                // Remove the row from table with animation
                                $row.fadeOut(400, function() {
                                    $(this).remove();
                                    
                                    // Check if table is now empty
                                    if ($('#transactionsTable tbody tr').length === 0) {
                                        $('#transactionsTable tbody').html(
                                            '<tr><td colspan="6" class="text-center">No transactions found.</td></tr>'
                                        );
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Error:', xhr.responseText);
                            
                            var message = 'An error occurred while deleting the transaction.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: message
                            });
                        }
                    });
                }
            });
        });
        
        // Show success/error messages from session
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        @endif
        
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session('error') }}'
            });
        @endif
    });
</script>
@stop
