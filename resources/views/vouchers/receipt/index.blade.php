@extends('adminlte::page')

@section('title', 'Receipt Vouchers')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Receipt Vouchers</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('vouchers.receipt.create') }}" class="btn btn-primary float-right">
                <i class="fas fa-plus"></i> Create Receipt Voucher
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Receipt Voucher List</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-2">
                    <label>From Date</label>
                    <input type="date" id="filterStartDate" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>To Date</label>
                    <input type="date" id="filterEndDate" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Customer</label>
                    <select id="filterCustomer" class="form-control select2">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Payment Method</label>
                    <select id="filterPaymentMethod" class="form-control select2">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="cheque">Cheque</option>
                        <option value="mobile_banking">Mobile Banking</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control select2">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label><br>
                    <button type="button" id="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>

            <table id="receiptsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Voucher No.</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Received In</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
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
.select2-container .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Initialize DataTable
    var table = $('#receiptsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('vouchers.receipt.index') }}',
            data: function(d) {
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
                d.customer_id = $('#filterCustomer').val();
                d.payment_method = $('#filterPaymentMethod').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'voucher_number', name: 'voucher_number' },
            { data: 'receipt_date', name: 'receipt_date' },
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'received_in', name: 'received_in', orderable: false },
            { data: 'amount', name: 'amount', className: 'text-right' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-3x"></i>'
        }
    });

    // Filter events
    $('#filterStartDate, #filterEndDate, #filterCustomer, #filterPaymentMethod, #filterStatus').on('change', function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#filterStartDate, #filterEndDate').val('');
        $('#filterCustomer, #filterPaymentMethod, #filterStatus').val('').trigger('change');
        table.ajax.reload();
    });

    // Cancel voucher
    $(document).on('click', '.cancel-btn', function() {
        var voucherId = $(this).data('id');
        
        Swal.fire({
            title: 'Cancel Receipt Voucher?',
            text: "This will void the transaction. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/vouchers/receipt/' + voucherId + '/cancel',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: response.message,
                            timer: 2000
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });

    // Delete voucher
    $(document).on('click', '.delete-btn', function() {
        var voucherId = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Receipt Voucher?',
            text: "This action cannot be undone!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/vouchers/receipt/' + voucherId,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'An error occurred'
                        });
                    }
                });
            }
        });
    });
});
</script>
@stop
