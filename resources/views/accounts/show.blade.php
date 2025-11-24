@extends('adminlte::page')

@section('title', 'Account Details')

@section('content_header')
    <h1>Account Details</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $account->code }} - {{ $account->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('accounts.edit', $account) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Account Code</th>
                            <td>{{ $account->code }}</td>
                        </tr>
                        <tr>
                            <th>Account Name</th>
                            <td>{{ $account->name }}</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>
                                <span class="badge badge-{{ $account->type == 'asset' ? 'primary' : ($account->type == 'liability' ? 'danger' : ($account->type == 'equity' ? 'info' : ($account->type == 'income' ? 'success' : 'warning'))) }}">
                                    {{ ucfirst($account->type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Parent Account</th>
                            <td>{{ $account->parentAccount ? $account->parentAccount->code . ' - ' . $account->parentAccount->name : 'None' }}</td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>{{ $account->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Opening Balance</th>
                            <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Opening Balance Date</th>
                            <td>{{ $account->opening_balance_date?->format('d M Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($account->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

<!-- All Transactions - DataTable -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Transactions</h3>
    </div>
    
    <div class="card-body">
        <!-- Filter Form -->
        <form id="filterForm" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label>Date From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label>Date To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label>Type</label>
                    <select name="transaction_type" id="transactionType" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="debit">Debit</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Other Account</label>
                    <select name="other_account_id" id="otherAccountId" class="form-control form-control-sm">
                        <option value="">All Accounts</option>
                        @foreach(\App\Models\Account::where('is_active', true)->where('id', '!=', $account->id)->orderBy('code')->get() as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label>&nbsp;</label>
                    <button type="button" id="resetFilter" class="btn btn-secondary btn-sm btn-block" title="Reset Filters">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
            </div>
        </form>

        <table id="transactionsTable" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Other Account</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- DataTables will populate this via AJAX -->
            </tbody>
        </table>
    </div>
</div>

        </div>

        <div class="col-md-4">
            <!-- Current Balance Widget -->
            <div class="small-box bg-{{ $account->isDebitAccount() ? 'info' : 'success' }}">
                <div class="inner">
                    <h3>{{ number_format($currentBalance, 2) }}</h3>
                    <p>Current Balance</p>
                </div>
                <div class="icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <!-- Child Accounts -->
            @if($account->childAccounts->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sub-Accounts</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($account->childAccounts as $child)
                                <li class="list-group-item">
                                    <a href="{{ route('accounts.show', $child) }}">
                                        {{ $child->code }} - {{ $child->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    {{-- DataTables CSS is already included in AdminLTE config --}}
@stop

@section('js')
<script>
    $(document).ready(function() {
        var table = $('#transactionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('accounts.transactions', $account) }}',
                data: function (d) {
                    d.date_from = $('#dateFrom').val();
                    d.date_to = $('#dateTo').val();
                    d.transaction_type = $('#transactionType').val();
                    d.other_account_id = $('#otherAccountId').val();
                }
            },
            columns: [
                { data: 'date', name: 't.date' },
                { data: 'reference', name: 't.reference' },
                { data: 'description', name: 't.description' },
                { data: 'other_account', name: 'other_account', orderable: false, searchable: false },
                { data: 'debit', name: 'debit', orderable: false, searchable: false, className: 'text-right' },
                { data: 'credit', name: 'credit', orderable: false, searchable: false, className: 'text-right' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="fas fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
            }
        });

        // Reload table when filters change
        $('#dateFrom, #dateTo, #transactionType, #otherAccountId').on('change', function() {
            table.draw();
        });

        // Reset filters
        $('#resetFilter').on('click', function() {
            $('#dateFrom').val('');
            $('#dateTo').val('');
            $('#transactionType').val('');
            $('#otherAccountId').val('');
            table.draw();
        });

        // Delete transaction handler with SweetAlert2
        $(document).on('click', '.delete-transaction', function() {
            var transactionId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the transaction and all related entries!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/transactions/' + transactionId,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
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
                                
                                // Reload DataTable without resetting pagination
                                table.ajax.reload(null, false);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr) {
                            var message = xhr.responseJSON && xhr.responseJSON.message 
                                ? xhr.responseJSON.message 
                                : 'Error deleting transaction';
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: message,
                                timer: 3000
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@stop
