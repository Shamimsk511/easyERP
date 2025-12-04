@extends('adminlte::page')

@section('title', 'Payment Vouchers')

@section('content_header')
    <h1>Payment Vouchers</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payment Voucher List</h3>
        <div class="card-tools">
            <a href="{{ route('vouchers.payment.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Create Payment Voucher
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Start Date</label>
                <input type="date" id="filter-start-date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label>End Date</label>
                <input type="date" id="filter-end-date" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label>Status</label>
                <select id="filter-status" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>Payment Method</label>
                <select id="filter-payment-method" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="cheque">Cheque</option>
                    <option value="mobile_banking">Mobile Banking</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="btn-filter" class="btn btn-info btn-sm btn-block">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        <table id="payment-vouchers-table" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="12%">Voucher Number</th>
                    <th width="10%">Date</th>
                    <th width="15%">Payee</th>
                    <th width="13%">Paid From</th>
                    <th width="13%">Paid To</th>
                    <th width="10%">Amount</th>
                    <th width="10%">Method</th>
                    <th width="8%">Status</th>
                    <th width="12%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- DataTables will populate this -->
            </tbody>
        </table>
    </div>
</div>
@stop

@section('css')
<style>
    .table td {
        vertical-align: middle;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .badge {
        font-size: 0.9em;
        padding: 0.35em 0.65em;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize DataTable with server-side processing
    const table = $('#payment-vouchers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('vouchers.payment.index') }}",
            type: 'GET',
            data: function(d) {
                d.start_date = $('#filter-start-date').val();
                d.end_date = $('#filter-end-date').val();
                d.status = $('#filter-status').val();
                d.payment_method = $('#filter-payment-method').val();
            }
        },
        columns: [
            { 
                data: 'DT_RowIndex', 
                name: 'DT_RowIndex', 
                orderable: false, 
                searchable: false 
            },
            { 
                data: 'voucher_number', 
                name: 'voucher_number' 
            },
            { 
                data: 'payment_date', 
                name: 'payment_date' 
            },
            { 
                data: 'payee', 
                name: 'payee',
                orderable: false 
            },
            { 
                data: 'paid_from', 
                name: 'paidFromAccount.name' 
            },
            { 
                data: 'paid_to', 
                name: 'paidToAccount.name' 
            },
            { 
                data: 'amount', 
                name: 'amount',
                className: 'text-right'
            },
            { 
                data: 'payment_method', 
                name: 'payment_method',
                className: 'text-center'
            },
            { 
                data: 'status', 
                name: 'status',
                className: 'text-center'
            },
            { 
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            }
        ],
        order: [[2, 'desc']], // Order by date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-3x"></i><br>Loading...',
            emptyTable: "No payment vouchers found",
            zeroRecords: "No matching payment vouchers found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function() {
            bindActionButtons();
        }
    });

    // Filter button click
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });

    // Function to bind action button handlers
    function bindActionButtons() {
        // Delete button handler
        $('.delete-btn').off('click').on('click', function() {
            const voucherId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteVoucher(voucherId);
                }
            });
        });

        // Cancel button handler
        $('.cancel-btn').off('click').on('click', function() {
            const voucherId = $(this).data('id');
            
            Swal.fire({
                title: 'Cancel this voucher?',
                text: "This will void the associated transaction.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f39c12',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    cancelVoucher(voucherId);
                }
            });
        });
    }

    // Delete voucher via AJAX
    function deleteVoucher(voucherId) {
        $.ajax({
            url: '/vouchers/payment/' + voucherId,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
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
                let errorMessage = 'Failed to delete payment voucher.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        });
    }

    // Cancel voucher via AJAX
    function cancelVoucher(voucherId) {
        $.ajax({
            url: '/vouchers/payment/' + voucherId + '/cancel',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
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
                let errorMessage = 'Failed to cancel payment voucher.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        });
    }

    // Initial binding
    bindActionButtons();
});
</script>
@stop
