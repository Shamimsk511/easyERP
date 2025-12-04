@extends('adminlte::page')

@section('title', 'Vendors')

@section('content_header')
    <h1>Vendors</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Vendor List</h3>
        <div class="card-tools">
            <a href="{{ route('vendors.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Vendor
            </a>
        </div>
    </div>
    <div class="card-body">
        <table id="vendors-table" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th>Vendor Name</th>
                    <th>Ledger Account</th>
                    <th width="12%">Opening Balance</th>
                    <th width="12%">Current Balance</th>
                    <th width="15%">Actions</th>
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
    const table = $('#vendors-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('vendors.index') }}",
            type: 'GET'
        },
        columns: [
            { 
                data: 'DT_RowIndex', 
                name: 'DT_RowIndex', 
                orderable: false, 
                searchable: false 
            },
            { 
                data: 'name', 
                name: 'name' 
            },
            { 
                data: 'ledger_account', 
                name: 'ledger_account',
                orderable: false 
            },
            { 
                data: 'opening_balance', 
                name: 'opening_balance',
                orderable: true,
                className: 'text-center'
            },
            { 
                data: 'current_balance', 
                name: 'current_balance',
                orderable: false,
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
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-3x"></i><br>Loading...',
            emptyTable: "No vendors found",
            zeroRecords: "No matching vendors found"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function() {
            // Re-bind delete button handlers after table redraw
            bindDeleteButtons();
        }
    });

    // Function to bind delete button handlers
    function bindDeleteButtons() {
        $('.delete-btn').off('click').on('click', function() {
            const vendorId = $(this).data('id');
            const row = $(this).closest('tr');
            
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
                    deleteVendor(vendorId, row);
                }
            });
        });
    }

    // Delete vendor via AJAX
    function deleteVendor(vendorId, row) {
        $.ajax({
            url: '/vendors/' + vendorId,
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
                    
                    // Reload DataTable
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
                let errorMessage = 'Failed to delete vendor.';
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
    bindDeleteButtons();
});
</script>
@stop
