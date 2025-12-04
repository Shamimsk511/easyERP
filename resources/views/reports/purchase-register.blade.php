@extends('adminlte::page')

@section('title', 'Purchase Register')

@section('content_header')
    <h1><i class="fas fa-file-invoice"></i> Purchase Register</h1>
@stop

@section('content')
    <!-- Summary Cards -->
    <div class="row" id="summary-cards">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="total-orders">0</h3>
                    <p>Total Orders</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="pending-orders">0</h3>
                    <p>Pending</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="received-orders">0</h3>
                    <p>Received</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3 id="total-amount">৳0</h3>
                    <p>Total Amount</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3 id="pending-amount">৳0</h3>
                    <p>Pending Amount</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3 id="received-amount">৳0</h3>
                    <p>Received Amount</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Report Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-table"></i> Purchase Register Report</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-success btn-sm" id="export-btn" disabled>
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="print-btn">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filter Section -->
            <div class="card card-outline card-primary collapsed-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filter Options</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <form id="filter-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="vendor_id">Vendor</label>
                                    <select class="form-control select2" id="vendor_id" name="vendor_id" style="width: 100%;">
                                        <option value="">-- All Vendors --</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_id">Product</label>
                                    <select class="form-control select2" id="product_id" name="product_id" style="width: 100%;">
                                        <option value="">-- All Products --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name }}
                                                @if($product->code)
                                                    ({{ $product->code }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">-- All Status --</option>
                                        <option value="pending">Pending</option>
                                        <option value="received">Received</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-primary" id="apply-filter">
                                        <i class="fas fa-search"></i> Apply Filter
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="reset-filter">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                    <button type="button" class="btn btn-info" id="quick-today">
                                        <i class="fas fa-calendar-day"></i> Today
                                    </button>
                                    <button type="button" class="btn btn-info" id="quick-this-month">
                                        <i class="fas fa-calendar"></i> This Month
                                    </button>
                                    <button type="button" class="btn btn-info" id="quick-last-month">
                                        <i class="fas fa-calendar-alt"></i> Last Month
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive mt-3">
                <table id="purchase-register-table" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Total Qty</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="5" class="text-right"><strong>Grand Total:</strong></th>
                            <th id="footer-total-qty">0.00</th>
                            <th id="footer-total-amount">৳ 0.00</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Items Modal -->
    <div class="modal fade" id="items-modal" tabindex="-1" role="dialog" aria-labelledby="itemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="itemsModalLabel">
                        <i class="fas fa-list"></i> Purchase Order Items - <span id="modal-order-number"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="items-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    
    <style>
        .table td, .table th {
            vertical-align: middle;
        }
        
        .small-box {
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        
        .small-box h3 {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        #summary-cards {
            margin-bottom: 20px;
        }
        
        .btn-group .btn {
            margin: 0 2px;
        }
        
        .badge {
            font-size: 0.9em;
            padding: 0.35em 0.65em;
        }
        
        .card-header {
            background-color: #f8f9fa;
        }
        
        tfoot th {
            font-size: 1.1em;
        }
        
        .select2-container--bootstrap4 .select2-selection {
            border-color: #ced4da;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            allowClear: true
        });
        
        // Initialize DataTable
        var table = $('#purchase-register-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('reports.purchase-register') }}',
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.vendor_id = $('#vendor_id').val();
                    d.product_id = $('#product_id').val();
                    d.status = $('#status').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'order_number', name: 'order_number' },
                { data: 'order_date_formatted', name: 'order_date' },
                { data: 'vendor_name', name: 'vendor.name' },
                { data: 'items_count', name: 'items_count', orderable: false, searchable: false },
                { data: 'total_quantity', name: 'total_quantity', orderable: false, searchable: false },
                { data: 'total_amount_formatted', name: 'total_amount' },
                { data: 'status_badge', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[2, 'desc']], // Order by date descending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="fas fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            drawCallback: function(settings) {
                // Update footer totals
                var api = this.api();
                var json = api.ajax.json();
                
                if (json) {
                    // Calculate totals from current page
                    var totalQty = 0;
                    var totalAmount = 0;
                    
                    api.rows({page: 'current'}).every(function() {
                        var data = this.data();
                        // Extract numeric values from formatted strings
                        var qty = parseFloat(data.total_quantity.replace(/[^0-9.-]+/g, "")) || 0;
                        var amount = parseFloat(data.total_amount_formatted.replace(/[^0-9.-]+/g, "")) || 0;
                        totalQty += qty;
                        totalAmount += amount;
                    });
                    
                    $('#footer-total-qty').html('<strong>' + totalQty.toFixed(2) + '</strong>');
                    $('#footer-total-amount').html('<strong class="text-success">৳ ' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>');
                }
            }
        });
        
        // Load summary data
        function loadSummary() {
            $.ajax({
                url: '{{ route('reports.purchase-register.summary') }}',
                type: 'GET',
                data: {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    vendor_id: $('#vendor_id').val(),
                    product_id: $('#product_id').val(),
                    status: $('#status').val()
                },
                success: function(data) {
                    $('#total-orders').text(data.total_orders);
                    $('#pending-orders').text(data.pending_orders);
                    $('#received-orders').text(data.received_orders);
                    $('#total-amount').text('৳' + Number(data.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#pending-amount').text('৳' + Number(data.pending_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $('#received-amount').text('৳' + Number(data.received_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
                }
            });
        }
        
        // Initial summary load
        loadSummary();
        
        // Apply filter
        $('#apply-filter').on('click', function() {
            table.ajax.reload();
            loadSummary();
        });
        
        // Reset filter
        $('#reset-filter').on('click', function() {
            $('#filter-form')[0].reset();
            $('.select2').val(null).trigger('change');
            table.ajax.reload();
            loadSummary();
        });
        
        // Quick filter buttons
        $('#quick-today').on('click', function() {
            var today = new Date().toISOString().split('T')[0];
            $('#start_date').val(today);
            $('#end_date').val(today);
            table.ajax.reload();
            loadSummary();
        });
        
        $('#quick-this-month').on('click', function() {
            var date = new Date();
            var firstDay = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
            var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
            $('#start_date').val(firstDay);
            $('#end_date').val(lastDay);
            table.ajax.reload();
            loadSummary();
        });
        
        $('#quick-last-month').on('click', function() {
            var date = new Date();
            var firstDay = new Date(date.getFullYear(), date.getMonth() - 1, 1).toISOString().split('T')[0];
            var lastDay = new Date(date.getFullYear(), date.getMonth(), 0).toISOString().split('T')[0];
            $('#start_date').val(firstDay);
            $('#end_date').val(lastDay);
            table.ajax.reload();
            loadSummary();
        });
        
        // View items modal
        $(document).on('click', '.view-items-btn', function() {
            var orderId = $(this).data('id');
            var orderNumber = $(this).data('order');
            
            $('#modal-order-number').text(orderNumber);
            
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#items-table')) {
                $('#items-table').DataTable().destroy();
            }
            
            // Initialize items DataTable
            $('#items-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('reports.purchase-register.items', ':id') }}'.replace(':id', orderId),
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'product_code', name: 'product.code' },
                    { data: 'product_name', name: 'product.name' },
                    { data: 'quantity_formatted', name: 'quantity' },
                    { data: 'rate_formatted', name: 'rate' },
                    { data: 'amount_formatted', name: 'amount' }
                ],
                order: [[2, 'asc']],
                pageLength: 10,
                searching: false,
                lengthChange: false,
                info: false
            });
            
            $('#items-modal').modal('show');
        });
        
        // Print report
        $('#print-btn').on('click', function() {
            window.print();
        });
        
        // Export (placeholder)
        $('#export-btn').on('click', function() {
            toastr.info('Export feature coming soon!');
        });
        
        // Toast notifications
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    });
    </script>
@stop
