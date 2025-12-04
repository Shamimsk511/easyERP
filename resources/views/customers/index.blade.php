@extends('adminlte::page')

@section('title', 'Customers')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Customers (Sundry Debtors)</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('customers.create') }}" class="btn btn-primary float-right">
                <i class="fas fa-plus"></i> Add New Customer
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
                    <label>Filter by Group</label>
                    <select id="filterGroup" class="form-control select2">
                        <option value="">All Groups</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Filter by Status</label>
                    <select id="filterStatus" class="form-control select2">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Show Overdue Only</label>
                    <select id="filterOverdue" class="form-control select2">
                        <option value="">All Customers</option>
                        <option value="1">Overdue Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label><br>
                    <button type="button" id="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </button>
                </div>
            </div>

            <table id="customersTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Current Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Extend Due Date Modal -->
    <div class="modal fade" id="extendDueDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Extend Due Date</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="extendDueDateForm">
                    <div class="modal-body">
                        <input type="hidden" id="extend_customer_id">
                        <div class="form-group">
                            <label>Current Due Date</label>
                            <input type="text" id="current_due_date" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Extended Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="extended_due_date" id="extended_due_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Extend Due Date</button>
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
            line-height: 38px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .select2-container--open .select2-dropdown {
            border: 1px solid #ced4da;
        }
        .select2-results__option {
            border-bottom: 1px solid #f0f0f0;
            padding: 8px;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--open .select2-selection--single {
            outline: none;
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ced4da;
            padding: 6px;
        }
        .select2-search--dropdown .select2-search__field:focus {
            outline: none;
            border-color: #80bdff;
            caret-color: auto;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // DataTable with proper HTML rendering
            var table = $('#customersTable').DataTable({
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
                    { 
                        data: 'customer_code', 
                        name: 'customer_code',
                        className: 'align-middle'
                    },
                    { 
                        data: 'name', 
                        name: 'name',
                        className: 'align-middle'
                    },
                    { 
                        data: 'phone', 
                        name: 'phone',
                        className: 'align-middle'
                    },
                    { 
                        data: 'address', 
                        name: 'address', 
                        orderable: false,
                        className: 'align-middle'
                    },
                    { 
                        data: 'current_balance', 
                        name: 'current_balance',
                        className: 'align-middle'
                    },
                    { 
                        data: 'actions', 
                        name: 'actions', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center align-middle'
                    }
                ],
                columnDefs: [{
                    targets: [1, 4, 5], // name, current_balance, actions columns
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).html(cellData); // Force HTML rendering
                    }
                }],
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    processing: '<i class="fas fa-spinner fa-spin fa-3x"></i>'
                }
            });

            // Filter events
            $('#filterGroup, #filterStatus, #filterOverdue').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#filterGroup, #filterStatus, #filterOverdue').val('').trigger('change');
                table.ajax.reload();
            });

            // Extend due date
            $(document).on('click', '.btn-extend-due', function() {
                var customerId = $(this).data('customer-id');
                var currentDueDate = $(this).data('current-due-date');
                
                $('#extend_customer_id').val(customerId);
                $('#current_due_date').val(currentDueDate);
                $('#extended_due_date').val('');
                $('#extendDueDateModal').modal('show');
            });

            // Extend due date form submission
            $('#extendDueDateForm').on('submit', function(e) {
                e.preventDefault();
                
                var customerId = $('#extend_customer_id').val();
                var formData = {
                    extended_due_date: $('#extended_due_date').val(),
                    reason: $('textarea[name="reason"]').val(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: '/customers/' + customerId + '/extend-due-date',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#extendDueDateModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 2000
                        });
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON.message || 'An error occurred'
                        });
                    }
                });
            });

            // Deactivate customer (for customers with transactions)
            $(document).on('click', '.btn-deactivate', function() {
                var customerId = $(this).data('customer-id');
                var customerName = $(this).data('customer-name');

                Swal.fire({
                    title: 'Deactivate Customer?',
                    text: "Customer '" + customerName + "' has existing transactions and cannot be deleted. Do you want to deactivate instead?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f39c12',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, deactivate it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/customers/' + customerId + '/deactivate',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deactivated!',
                                    text: response.message,
                                    timer: 2000
                                });
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON.message || 'An error occurred'
                                });
                            }
                        });
                    }
                });
            });

            // Delete customer (only for customers without transactions)
            $(document).on('click', '.btn-delete', function() {
                var customerId = $(this).data('customer-id');
                var customerName = $(this).data('customer-name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Delete customer: " + customerName + "?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/customers/' + customerId,
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
                                    title: 'Error',
                                    text: xhr.responseJSON.message || 'An error occurred'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@stop

