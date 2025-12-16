@extends('adminlte::page')

@section('title', 'Sales Invoices')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-invoice-dollar"></i> Sales Invoices</h1>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Invoice
        </a>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Invoices</h3>
        <div class="card-tools">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-default filter-btn active" data-filter="all">All</button>
                <button type="button" class="btn btn-warning filter-btn" data-filter="pending">Pending</button>
                <button type="button" class="btn btn-info filter-btn" data-filter="partial">Partial</button>
                <button type="button" class="btn btn-success filter-btn" data-filter="delivered">Delivered</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="invoices-table" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Add. Charges</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@section('css')
<style>
    #invoices-table th, #invoices-table td {
        vertical-align: middle;
    }
    .filter-btn.active {
        font-weight: bold;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    let currentFilter = 'all';

    const table = $('#invoices-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("sales.index") }}',
            data: function(d) {
                d.delivery_status = currentFilter !== 'all' ? currentFilter : null;
            }
        },
        columns: [
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'invoice_date', name: 'invoice_date' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'customer_phone', name: 'customer_phone' },
            { data: 'total_amount', name: 'total_amount', className: 'text-right' },
            { data: 'total_paid', name: 'total_paid', className: 'text-right' },
            { data: 'outstanding_balance', name: 'outstanding_balance', className: 'text-right font-weight-bold' },
            { data: 'additional_charges', name: 'additional_charges', className: 'text-center' },
            { data: 'delivery_status_badge', name: 'delivery_status', className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>'
        }
    });

    // Filter buttons
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        table.ajax.reload();
    });

    // Delete invoice
    $(document).on('click', '.delete-invoice-btn', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Invoice?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/sales/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop