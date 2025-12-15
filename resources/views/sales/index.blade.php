@extends('adminlte::page')
@section('title', 'Sales / Invoices')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Sales / Invoices</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('sales.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Invoice
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoice List</h3>
    </div>
    <div class="card-body">

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDelivery">Delivery Status</label>
                    <select id="filterDelivery" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDeleted">Show Deleted</label>
                    <select id="filterDeleted" class="form-control">
                        <option value="no">Active Only</option>
                        <option value="yes">Deleted Only</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <label>&nbsp;</label>
                <button id="resetFilters" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Reset Filters
                </button>
            </div>
        </div>

        <table id="invoicesTable" class="table table-bordered table-hover table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Outstanding</th>
                    <th>Delivery</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@section('css')
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@stop

@push('js')
<script>
$(document).ready(function() {
    // DataTable initialization
    const table = $('#invoicesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("sales.data") }}',
            data: function(d) {
                d.delivery_status = $('#filterDelivery').val();
                d.show_deleted = $('#filterDeleted').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'invoice_date', name: 'invoice_date' },
            { data: 'total_amount', name: 'total_amount', className: 'text-right' },
            { data: 'total_paid', name: 'total_paid', className: 'text-right' },
            { data: 'outstanding_balance', name: 'outstanding_balance', className: 'text-right' },
            { data: 'delivery_status_badge', name: 'delivery_status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>'
        }
    });

    // Filter events
    $('#filterDelivery, #filterDeleted').on('change', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#filterDelivery, #filterDeleted').val('');
        table.ajax.reload();
    });

    // Delete invoice handler
    $(document).on('click', '.delete-invoice', function() {
        const invoiceId = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Invoice?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/sales/' + invoiceId,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
@endpush