@extends('adminlte::page')
@section('title', 'Purchase Orders')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Purchase Orders</h3>
        <div class="card-tools">
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> New Purchase Order
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="statusFilter">Filter by Status</label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" id="resetFilter" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
        </div>

        <!-- DataTable -->
        <table id="po-table" class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Order Number</th>
                    <th>Vendor</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    // Helper function to get URL parameter
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }
    
    // Get initial status filter from URL
    var currentStatusFilter = getUrlParameter('status');
    
    // Update dropdown to match URL parameter
    if (currentStatusFilter) {
        $('#statusFilter').val(currentStatusFilter);
    }
    
    // Initialize DataTable
    const table = $('#po-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('purchase-orders.index') }}',
            data: function(d) {
                // Pass status filter to server
                d.status = currentStatusFilter || $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_number', name: 'order_number' },
            { data: 'vendor_name', name: 'vendor.name' },
            { data: 'order_date_formatted', name: 'order_date' },
            { data: 'status_badge', name: 'status', orderable: true, searchable: false },
            { data: 'amount_formatted', name: 'total_amount', orderable: true },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[3, 'desc']], // Order by date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-3x"></i><br>Loading...',
            emptyTable: "No purchase orders found",
            zeroRecords: "No matching purchase orders found"
        },
        drawCallback: function() {
            // Re-bind event handlers after table redraw
            bindButtons();
        }
    });

    // Status filter change handler
    $('#statusFilter').on('change', function() {
        currentStatusFilter = $(this).val();
        
        // Update URL without reload
        var newUrl = '{{ route('purchase-orders.index') }}';
        if (currentStatusFilter) {
            newUrl += '?status=' + currentStatusFilter;
        }
        window.history.pushState({path: newUrl}, '', newUrl);
        
        // Reload DataTable with new filter
        table.ajax.reload();
    });

    // Reset filter handler
    $('#resetFilter').on('click', function() {
        currentStatusFilter = '';
        $('#statusFilter').val('');
        
        // Update URL to remove query string
        window.history.pushState({path: '{{ route('purchase-orders.index') }}'}, '', '{{ route('purchase-orders.index') }}');
        
        // Reload DataTable
        table.ajax.reload();
    });

    // Function to bind all button handlers
    function bindButtons() {
        // Mark as Received button handler
        $('.receive-btn').off('click').on('click', function() {
            const orderId = $(this).data('id');
            const vendorName = $(this).data('vendor');
            const amount = $(this).data('amount');
            
            Swal.fire({
                title: 'Mark as Received?',
                html: `<div class="text-left">
                    <p><strong>Vendor:</strong> ${vendorName}</p>
                    <p><strong>Amount:</strong> ৳ ${amount}</p>
                    <hr>
                    <p class="text-info"><i class="fas fa-info-circle"></i> This will:</p>
                    <ul class="text-left">
                        <li>Update product stock quantities</li>
                        <li>Create accounting entry (Dr. Purchase / Cr. Vendor)</li>
                        <li>Update vendor's ledger balance</li>
                        <li>Log product movements</li>
                    </ul>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-circle"></i> Yes, Mark as Received',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.ajax({
                        url: `/purchase-orders/${orderId}/mark-received`,
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        }
                    }).fail(function(xhr) {
                        Swal.showValidationMessage(
                            xhr.responseJSON?.error || 'Request failed'
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: result.value.message || 'Purchase order marked as received successfully!',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    table.ajax.reload(null, false);
                }
            });
        });

        // Delete button handler
        $('.delete-btn').off('click').on('click', function() {
            const orderId = $(this).data('id');
            const row = $(this).closest('tr');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete this pending purchase order.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/purchase-orders/${orderId}`,
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
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
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Failed to delete purchase order.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire('Error!', errorMessage, 'error');
                        }
                    });
                }
            });
        });
    }

    // Initial binding
    bindButtons();
    
    // Toast notifications
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
});
</script>
@endpush

@push('css')
<style>
    .table td {
        vertical-align: middle !important;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .badge {
        font-size: 0.9em;
        padding: 0.35em 0.65em;
    }
    /* Make Swal content left-aligned */
    .swal2-html-container ul {
        text-align: left;
        list-style-position: inside;
    }
    /* Filter section styling */
    .form-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
</style>
@endpush
