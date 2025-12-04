@extends('adminlte::page')

@section('title', 'Vendor-wise Purchase Report')

@section('content_header')
    <h1><i class="fas fa-users"></i> Vendor-wise Purchase Report</h1>
@stop

@section('content')
    <!-- Summary Cards -->
    <div class="row" id="summary-cards">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="total-vendors">0</h3>
                    <p>Total Vendors</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
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
            <div class="small-box bg-secondary">
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
            <h3 class="card-title"><i class="fas fa-table"></i> Vendor-wise Purchase Analysis</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-success btn-sm" id="export-excel-btn">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="export-pdf-btn">
                    <i class="fas fa-file-pdf"></i> Export to PDF
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
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="">-- All Status --</option>
                                        <option value="pending">Pending</option>
                                        <option value="received">Received</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
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
                                    <button type="button" class="btn btn-info" id="quick-this-year">
                                        <i class="fas fa-calendar-check"></i> This Year
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive mt-3">
                <table id="vendor-purchase-table" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vendor Code</th>
                            <th>Vendor Name</th>
                            <th>Total Orders</th>
                            <th>Pending</th>
                            <th>Received</th>
                            <th>Total Amount</th>
                            <th>Pending Amount</th>
                            <th>Received Amount</th>
                            <th>Current Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="6" class="text-right"><strong>Grand Total:</strong></th>
                            <th id="footer-total-amount">৳ 0.00</th>
                            <th id="footer-pending-amount">৳ 0.00</th>
                            <th id="footer-received-amount">৳ 0.00</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Orders Modal -->
    <div class="modal fade" id="orders-modal" tabindex="-1" role="dialog" aria-labelledby="ordersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="ordersModalLabel">
                        <i class="fas fa-shopping-cart"></i> Purchase Orders - <span id="modal-vendor-name"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="orders-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order Number</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="4" class="text-right"><strong>Total:</strong></th>
                                <th id="modal-footer-total">৳ 0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
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
            font-size: 1.05em;
            background-color: #f8f9fa;
        }
        
        .select2-container--bootstrap4 .select2-selection {
            border-color: #ced4da;
        }
        
        @media print {
            .card-tools, .btn-group, .filter-section, #summary-cards {
                display: none !important;
            }
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
        
        // Store current filter params for modal
        var currentFilters = {
            start_date: '',
            end_date: '',
            status: ''
        };
        
        // Initialize DataTable
        var table = $('#vendor-purchase-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('reports.vendor-wise-purchase') }}',
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.vendor_id = $('#vendor_id').val();
                    d.status = $('#status').val();
                    
                    // Store for modal
                    currentFilters.start_date = d.start_date;
                    currentFilters.end_date = d.end_date;
                    currentFilters.status = d.status;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'vendor_code', name: 'vendor_code' },
                { data: 'vendor_name', name: 'name' },
                { data: 'total_orders', name: 'total_orders', orderable: false, searchable: false },
                { data: 'pending_orders', name: 'pending_orders', orderable: false, searchable: false },
                { data: 'received_orders', name: 'received_orders', orderable: false, searchable: false },
                { data: 'total_amount_formatted', name: 'total_amount', orderable: false, searchable: false },
                { data: 'pending_amount_formatted', name: 'pending_amount', orderable: false, searchable: false },
                { data: 'received_amount_formatted', name: 'received_amount', orderable: false, searchable: false },
                { data: 'current_balance', name: 'current_balance', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[6, 'desc']], // Order by total amount descending
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
                    var totalAmount = 0;
                    var pendingAmount = 0;
                    var receivedAmount = 0;
                    
                    api.rows({page: 'current'}).every(function() {
                        var data = this.data();
                        totalAmount += parseFloat(data.total_amount_formatted.replace(/[^0-9.-]+/g, "")) || 0;
                        pendingAmount += parseFloat(data.pending_amount_formatted.replace(/[^0-9.-]+/g, "")) || 0;
                        receivedAmount += parseFloat(data.received_amount_formatted.replace(/[^0-9.-]+/g, "")) || 0;
                    });
                    
                    $('#footer-total-amount').html('<strong class="text-success">৳ ' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>');
                    $('#footer-pending-amount').html('<strong class="text-warning">৳ ' + pendingAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>');
                    $('#footer-received-amount').html('<strong class="text-success">৳ ' + receivedAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>');
                }
            }
        });
        
        // Load summary data
        function loadSummary() {
            $.ajax({
                url: '{{ route('reports.vendor-wise-purchase.summary') }}',
                type: 'GET',
                data: {
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    vendor_id: $('#vendor_id').val(),
                    status: $('#status').val()
                },
                success: function(data) {
                    $('#total-vendors').text(data.total_vendors);
                    $('#total-orders').text(data.total_orders);
                    $('#pending-orders').text(data.pending_orders);
                    $('#received-orders').text(data.received_orders);
                    $('#total-amount').text('৳' + Number(data.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2}));
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
        
        $('#quick-this-year').on('click', function() {
            var date = new Date();
            var firstDay = new Date(date.getFullYear(), 0, 1).toISOString().split('T')[0];
            var lastDay = new Date(date.getFullYear(), 11, 31).toISOString().split('T')[0];
            $('#start_date').val(firstDay);
            $('#end_date').val(lastDay);
            table.ajax.reload();
            loadSummary();
        });
        
        // View orders modal
        $(document).on('click', '.view-orders-btn', function() {
            var vendorId = $(this).data('id');
            var vendorName = $(this).data('vendor');
            
            $('#modal-vendor-name').text(vendorName);
            
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#orders-table')) {
                $('#orders-table').DataTable().destroy();
            }
            
            // Initialize orders DataTable
            var ordersTable = $('#orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('reports.vendor-wise-purchase.orders', ':id') }}'.replace(':id', vendorId),
                    data: function(d) {
                        d.start_date = currentFilters.start_date;
                        d.end_date = currentFilters.end_date;
                        d.status = currentFilters.status;
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'order_number', name: 'order_number' },
                    { data: 'order_date_formatted', name: 'order_date' },
                    { data: 'items_count', name: 'items_count', orderable: false, searchable: false },
                    { data: 'total_amount_formatted', name: 'total_amount' },
                    { data: 'status_badge', name: 'status' }
                ],
                order: [[2, 'desc']],
                pageLength: 10,
                searching: false,
                lengthChange: false,
                drawCallback: function() {
                    var api = this.api();
                    var total = 0;
                    
                    api.rows({page: 'current'}).every(function() {
                        var data = this.data();
                        total += parseFloat(data.total_amount_formatted.replace(/[^0-9.-]+/g, "")) || 0;
                    });
                    
                    $('#modal-footer-total').html('<strong class="text-success">৳ ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>');
                }
            });
            
            $('#orders-modal').modal('show');
        });
        
        // Print report
        $('#print-btn').on('click', function() {
            window.print();
        });
        
        // Export Excel (placeholder)
        $('#export-excel-btn').on('click', function() {
            toastr.info('Excel export will be available after installing Laravel Excel package');
        });
        
        // Export PDF (placeholder)
        $('#export-pdf-btn').on('click', function() {
            toastr.info('PDF export will be available after installing DomPDF package');
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
