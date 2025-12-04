@extends('adminlte::page')

@section('title', 'Customer Groups')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Customer Groups</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('customer-groups.create') }}" class="btn btn-primary float-right">
                <i class="fas fa-plus"></i> Add New Group
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Customer Groups List</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Filter by Status</label>
                    <select id="filterStatus" class="form-control select2">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label><br>
                    <button type="button" id="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </button>
                </div>
            </div>

            <table id="groupsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Description</th>
                        <th>Total Customers</th>
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

            // DataTable
            var table = $('#groupsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("customer-groups.data") }}',
                    data: function(d) {
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'description', name: 'description' },
                    { data: 'customers_count', name: 'customers_count' },
                    { data: 'status', name: 'status' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'asc']],
                pageLength: 25,
                language: {
                    processing: '<i class="fas fa-spinner fa-spin fa-3x"></i>'
                }
            });

            // Filter events
            $('#filterStatus').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#filterStatus').val('').trigger('change');
                table.ajax.reload();
            });

            // Delete group
            $(document).on('click', '.btn-delete', function() {
                var groupId = $(this).data('group-id');
                var groupName = $(this).data('group-name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Delete group: " + groupName + "?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/customer-groups/' + groupId,
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
