@extends('adminlte::page')

@section('title', 'Customer Ledger - ' . $customer->name)

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Customer Ledger</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('customers.index') }}" class="btn btn-secondary float-right">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $customer->name }} - Ledger Account</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Customer Code:</strong><br>
                    {{ $customer->customer_code }}
                </div>
                <div class="col-md-3">
                    <strong>Phone:</strong><br>
                    {{ $customer->phone }}
                </div>
                <div class="col-md-3">
                    <strong>Current Balance:</strong><br>
                    <span class="{{ $customer->current_balance >= 0 ? 'text-danger' : 'text-success' }}">
                        ৳ {{ number_format(abs($customer->current_balance), 2) }} {{ $customer->current_balance >= 0 ? 'Dr' : 'Cr' }}
                    </span>
                </div>
                <div class="col-md-3">
                    <strong>Credit Limit:</strong><br>
                    ৳ {{ number_format($customer->credit_limit, 2) }}
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>From Date</label>
                    <input type="date" id="startDate" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>To Date</label>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label><br>
                    <button type="button" id="filterDate" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button type="button" id="resetDate" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>

            <table id="ledgerTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Voucher Type</th>
                        <th>Voucher No.</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                        <th>Narration</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            var table = $('#ledgerTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("customers.ledger.data", $customer->id) }}',
                    data: function(d) {
                        d.start_date = $('#startDate').val();
                        d.end_date = $('#endDate').val();
                    }
                },
                columns: [
                    { data: 'transaction_date', name: 'transaction_date' },
                    { data: 'voucher_type', name: 'voucher_type' },
                    { data: 'voucher_number', name: 'voucher_number' },
                    { data: 'debit', name: 'debit' },
                    { data: 'credit', name: 'credit' },
                    { data: 'balance', name: 'balance' },
                    { data: 'narration', name: 'narration' },
                    { data: 'due_date', name: 'due_date' }
                ],
                order: [[0, 'desc']],
                pageLength: 25
            });

            $('#filterDate').on('click', function() {
                table.ajax.reload();
            });

            $('#resetDate').on('click', function() {
                $('#startDate, #endDate').val('');
                table.ajax.reload();
            });
        });
    </script>
@stop
