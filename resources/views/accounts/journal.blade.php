@extends('adminlte::page')

@section('plugins.Datatables', true)

@section('title', 'Account Journal - ' . $account->name)

@section('content_header')
    <h1>
        <i class="fas fa-book"></i> Account Journal
        <small class="text-muted">{{ $account->code }} - {{ $account->name }}</small>
    </h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="badge badge-{{ 
                    $account->type == 'asset' ? 'primary' : 
                    ($account->type == 'liability' ? 'danger' : 
                    ($account->type == 'equity' ? 'info' : 
                    ($account->type == 'income' ? 'success' : 'warning'))) 
                }}">
                    {{ strtoupper($account->type) }}
                </span>
                {{ $account->code }} - {{ $account->name }}
            </h3>
            <div class="card-tools">
                <a href="{{ route('transactions.create', ['account_id' => $account->id]) }}" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Transaction
                </a>
                <a href="{{ route('accounts.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Accounts
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Current Balance Widget -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="info-box bg-{{ $balance >= 0 ? 'success' : 'danger' }}">
                        <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Current Balance</span>
                            <span class="info-box-number">{{ number_format(abs($balance), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" id="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" id="dateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label>Transaction Type</label>
                    <select id="transactionType" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="debit">Debit Only</option>
                        <option value="credit">Credit Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Other Account</label>
                    <select id="otherAccount" class="form-control form-control-sm">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Journal Entries Table -->
            <div class="table-responsive">
                <table id="journalTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Other Account</th>
                            <th>Memo</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <th colspan="5" class="text-right">Totals:</th>
                            <th class="text-right" id="totalDebit">0.00</th>
                            <th class="text-right" id="totalCredit">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .table td, .table th {
            vertical-align: middle;
        }
        
        .info-box-number {
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            console.log('Initializing journal table for account {{ $account->id }}');
            
            var table = $('#journalTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('accounts.journal', $account) }}',
                    type: 'GET',
                    data: function(d) {
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                        d.transaction_type = $('#transactionType').val();
                        d.other_account_id = $('#otherAccount').val();
                        console.log('Request data:', d);
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable Error:', error, thrown);
                        console.error('Response:', xhr.responseText);
                        
                        // Show user-friendly error
                        if (xhr.status === 404) {
                            alert('Error: Route not found. Please check your routes configuration.');
                        } else if (xhr.status === 500) {
                            alert('Server error occurred. Check browser console and Laravel logs.');
                        } else {
                            alert('Error loading data: ' + error);
                        }
                    }
                },
                columns: [
                    { data: 'date', name: 't.date' },
                    { data: 'reference', name: 't.reference' },
                    { data: 'description', name: 't.description' },
                    { data: 'other_account', name: 'other_account', orderable: false },
                    { data: 'memo', name: 'je.memo' },
                    { data: 'debit', name: 'debit', className: 'text-right', orderable: false, searchable: false },
                    { data: 'credit', name: 'credit', className: 'text-right', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    
                    var totalDebit = 0;
                    var totalCredit = 0;
                    
                    // Loop through all rows on current page
                    api.rows({page: 'current'}).every(function() {
                        var rowData = this.data();
                        if (rowData.type === 'debit') {
                            totalDebit += parseFloat(rowData.amount || 0);
                        } else if (rowData.type === 'credit') {
                            totalCredit += parseFloat(rowData.amount || 0);
                        }
                    });
                    
                    $('#totalDebit').html('<span class="text-danger">' + totalDebit.toFixed(2) + '</span>');
                    $('#totalCredit').html('<span class="text-success">' + totalCredit.toFixed(2) + '</span>');
                },
                language: {
                    processing: '<i class="fas fa-spinner fa-spin fa-3x"></i>',
                    emptyTable: 'No journal entries found for this account',
                    zeroRecords: 'No matching entries found',
                    loadingRecords: 'Loading...',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)'
                },
                drawCallback: function(settings) {
                    console.log('Table drawn with', settings.aoData.length, 'rows');
                }
            });
            
            // Reload table on filter change
            $('#dateFrom, #dateTo, #transactionType, #otherAccount').on('change', function() {
                console.log('Filter changed, reloading...');
                table.ajax.reload();
            });
            
            @if(session('success'))
                toastr.success('{{ session('success') }}');
            @endif
            
            @if(session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@stop
