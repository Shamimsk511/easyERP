@extends('adminlte::page')

@section('title', 'Journal Vouchers')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Journal Vouchers</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('vouchers.journal.create') }}" class="btn btn-primary btn-sm float-right">
                <i class="fas fa-plus"></i> New Journal Voucher
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manage Journal Vouchers</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Start Date</label>
                <input type="date" id="filter_start_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label>End Date</label>
                <input type="date" id="filter_end_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label>Status</label>
                <select id="filter_status" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-sm btn-primary btn-block" onclick="$('#journal-vouchers-table').DataTable().draw();">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </div>

        <!-- DataTable -->
        <div class="table-responsive">
            <table id="journal-vouchers-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Voucher No.</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Debit (৳)</th>
                        <th>Credit (৳)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#journal-vouchers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('vouchers.journal.index') }}",
            data: function(d) {
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'voucher_number', name: 'voucher_number' },
            { data: 'journal_date', name: 'journal_date' },
            { data: 'description', name: 'description' },
            { data: 'debit', name: 'debit', className: 'text-right' },
            { data: 'credit', name: 'credit', className: 'text-right' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Cancel voucher
    $(document).on('click', '.cancel-btn', function() {
        var id = $(this).data('id');
        
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
                    url: '/vouchers/journal/' + id + '/cancel',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Cancelled!', response.message, 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        });
    });

    // Delete voucher
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        
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
                    url: '/vouchers/journal/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        });
    });
});
</script>
@stop
