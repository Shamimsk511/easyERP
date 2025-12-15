@extends('adminlte::page')
@section('title', 'Customers')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Customers</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Customer
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Customer List</h3>
    </div>
    <div class="card-body">

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterGroup">Customer Group</label>
                    <select id="filterGroup" class="form-control select2" style="width: 100%;">
                        <option value="">All Groups</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterOverdue">Overdue</label>
                    <select id="filterOverdue" class="form-control">
                        <option value="">All</option>
                        <option value="1">Overdue Only</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button id="resetFilters" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>

        <table id="customersTable" class="table table-bordered table-hover table-sm">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Extend Due Date Modal --}}
<div class="modal fade" id="extendDueModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="extendDueForm">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Extend Due Date</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="extend_customer_id">
                    <p>Customer: <strong id="extend_customer_name"></strong></p>
                    <p>Current Due: <strong id="extend_current_due"></strong></p>
                    <div class="form-group">
                        <label for="new_due_date">New Due Date <span class="text-danger">*</span></label>
                        <input type="date" id="new_due_date" name="new_due_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="extension_reason">Reason</label>
                        <textarea id="extension_reason" name="reason" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Extend Due Date</button>
                </div>
            </form>
        </div>
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
    // Initialize Select2
    $('.select2').select2({ allowClear: true, placeholder: 'Select...' });

    // DataTable
    const table = $('#customersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("customers.data") }}',
            data: function(d) {
                d.group_id = $('#filterGroup').val();
                d.status = $('#filterStatus').val();
                d.overdue = $('#filterOverdue').val();
            }
        },
        columns: [
            { data: 'customer_code', name: 'customer_code' },
            { data: 'name', name: 'name' },
            { data: 'phone', name: 'phone' },
            { data: 'address', name: 'address', orderable: false },
            { data: 'current_balance', name: 'current_balance', className: 'text-right' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: { processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>' }
    });

    // Filters
    $('#filterGroup, #filterStatus, #filterOverdue').on('change', () => table.ajax.reload());
    $('#resetFilters').on('click', function() {
        $('#filterGroup, #filterStatus, #filterOverdue').val('').trigger('change');
        table.ajax.reload();
    });

    // Delete customer
    $(document).on('click', '.delete-customer', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Customer?',
            html: `Are you sure you want to delete <strong>${name}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/customers/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(response) {
                        Swal.fire('Deleted!', response.message || 'Customer deleted.', 'success');
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete', 'error');
                    }
                });
            }
        });
    });

    // Extend due date modal
    $(document).on('click', '.extend-due', function() {
        $('#extend_customer_id').val($(this).data('id'));
        $('#extend_customer_name').text($(this).data('name'));
        $('#extend_current_due').text($(this).data('due') || 'Not set');
        $('#new_due_date').val('');
        $('#extension_reason').val('');
        $('#extendDueModal').modal('show');
    });

    // Submit extend due form
    $('#extendDueForm').on('submit', function(e) {
        e.preventDefault();
        const customerId = $('#extend_customer_id').val();
        
        $.ajax({
            url: '/customers/' + customerId + '/extend-due-date',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                new_due_date: $('#new_due_date').val(),
                reason: $('#extension_reason').val()
            },
            success: function(response) {
                $('#extendDueModal').modal('hide');
                Swal.fire('Success', response.message || 'Due date extended.', 'success');
                table.ajax.reload();
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to extend', 'error');
            }
        });
    });
});
</script>
@endpush