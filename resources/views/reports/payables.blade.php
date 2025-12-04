@extends('adminlte::page')

@section('title', 'Payables Report')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-money-check-alt text-danger"></i> Payables Report
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Reports</li>
                    <li class="breadcrumb-item active">Payables</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Filters Card -->
        <div class="card card-primary collapsed-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter"></i> Filters & Options
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">
                                    <i class="fas fa-calendar-alt"></i> Start Date
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ $startDate }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">
                                    <i class="fas fa-calendar-alt"></i> End Date
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ $endDate }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="account_id">
                                    <i class="fas fa-book"></i> Liability Account
                                </label>
                                <select class="form-control select2" id="account_id" name="account_id" 
                                        style="width: 100%;">
                                    <option value="">All Liability Accounts</option>
                                    @foreach($liabilityAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vendor_id">
                                    <i class="fas fa-user-tie"></i> Vendor
                                </label>
                                <select class="form-control select2" id="vendor_id" name="vendor_id" 
                                        style="width: 100%;">
                                    <option value="">All Vendors</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">
                                            {{ $vendor->name }} @if($vendor->phone)({{ $vendor->phone }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" class="custom-control-input" id="show_zero_balance" 
                                           name="show_zero_balance">
                                    <label class="custom-control-label" for="show_zero_balance">
                                        Show Zero Balance Accounts
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9 text-right mt-4">
                            <button type="button" class="btn btn-primary" id="applyFilters">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetFilters">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button type="button" class="btn btn-success" id="exportCsv">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-info" id="printReport">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row" id="summaryCards">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="totalPayable">৳ 0.00</h3>
                        <p>Total Payables</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="totalAdvance">৳ 0.00</h3>
                        <p>Total Advance Paid</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="totalAccounts">0</h3>
                        <p>Liability Accounts</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="totalVendors">0</h3>
                        <p>Vendors with Balance</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payables Table -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> Payables Details
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light" id="recordCount">0 records</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="payablesTable" class="table table-bordered table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Code</th>
                                <th width="15%">Account Name</th>
                                <th width="10%">Opening</th>
                                <th width="10%">Debits</th>
                                <th width="10%">Credits</th>
                                <th width="12%">Current Balance</th>
                                <th width="8%">Txns</th>
                                <th width="10%">Vendor</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr class="font-weight-bold">
                                <th colspan="4" class="text-right">TOTAL:</th>
                                <th id="footerDebits" class="text-danger">৳ 0.00</th>
                                <th id="footerCredits" class="text-success">৳ 0.00</th>
                                <th id="footerBalance" class="text-danger">৳ 0.00</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Click on the eye icon to view transaction details. Click on the money icon to create a payment voucher.
                </small>
            </div>
        </div>
    </div>
</section>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-list"></i> Transaction Details: <span id="modalAccountName"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <strong><i class="fas fa-info-circle"></i> Account Summary:</strong>
                            <span id="modalSummary" class="ml-2"></span>
                        </div>
                        <div class="col-md-6 text-right">
                            <strong><i class="fas fa-calendar"></i> Period:</strong>
                            <span id="modalPeriod" class="ml-2"></span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="transactionDetailsTable" class="table table-bordered table-sm table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="10%">Date</th>
                                <th width="12%">Reference</th>
                                <th width="10%">Type</th>
                                <th width="30%">Description</th>
                                <th width="12%" class="text-right">Debit</th>
                                <th width="12%" class="text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot class="bg-light">
                            <tr class="font-weight-bold">
                                <th colspan="4" class="text-right">TOTAL:</th>
                                <th class="text-right text-danger" id="modalTotalDebits">৳ 0.00</th>
                                <th class="text-right text-success" id="modalTotalCredits">৳ 0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <a href="#" id="makePaymentBtn" class="btn btn-success">
                    <i class="fas fa-money-bill-wave"></i> Make Payment
                </a>
                <a href="#" id="viewLedgerBtn" class="btn btn-primary" target="_blank">
                    <i class="fas fa-book"></i> View Ledger
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
    .table th {
        white-space: nowrap;
        font-size: 0.9rem;
    }
    .table td {
        vertical-align: middle;
    }
    .small-box h3 {
        font-size: 1.8rem;
        font-weight: bold;
    }
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-container--bootstrap4.select2-container--focus .select2-selection {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }
    .modal-xl {
        max-width: 95%;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 with proper focus handling
    $('.select2').select2({
        theme: 'bootstrap4',
        allowClear: true,
        width: '100%'
    });
    
    // Auto-focus search field when Select2 opens
    $(document).on('select2:open', () => {
        document.querySelector('.select2-search__field').focus();
    });
    
    let currentAccountId = null;
    
    // Initialize DataTable
    let payablesTable = $('#payablesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("reports.payables.data") }}',
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.account_id = $('#account_id').val();
                d.vendor_id = $('#vendor_id').val();
                d.show_zero_balance = $('#show_zero_balance').is(':checked');
            },
            dataSrc: function(json) {
                updateSummaryCards(json.data);
                updateFooterTotals(json.data);
                $('#recordCount').text(json.recordsFiltered + ' records');
                return json.data;
            },
            error: function(xhr, error, thrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Data',
                    text: 'Unable to load payables data. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'account_code', name: 'code' },
            { data: 'account_name', name: 'name' },
            { data: 'opening_balance', name: 'opening_balance', searchable: false },
            { data: 'total_debits', name: 'total_debits', orderable: false, searchable: false },
            { data: 'total_credits', name: 'total_credits', orderable: false, searchable: false },
            { data: 'current_balance', name: 'current_balance', orderable: false, searchable: false },
            { data: 'transaction_count', name: 'transaction_count', orderable: false, searchable: false },
            { data: 'vendor_info', name: 'vendor_info', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw text-primary"></i>',
            emptyTable: "No payables found for the selected criteria",
            zeroRecords: "No matching payables found"
        }
    });
    
    // Apply Filters
    $('#applyFilters').on('click', function() {
        payablesTable.ajax.reload();
        $('.card-primary.collapsed-card').CardWidget('collapse');
    });
    
    // Reset Filters
    $('#resetFilters').on('click', function() {
        $('#filterForm')[0].reset();
        $('.select2').val(null).trigger('change');
        
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        $('#start_date').val(firstDay.toISOString().split('T')[0]);
        $('#end_date').val(today.toISOString().split('T')[0]);
        
        payablesTable.ajax.reload();
    });
    
    // View Transaction Details
    $(document).on('click', '.view-details', function() {
        currentAccountId = $(this).data('account-id');
        const accountName = $(this).data('account-name');
        
        $('#modalAccountName').text(accountName);
        $('#makePaymentBtn').attr('href', '{{ route("vouchers.payment.create") }}?paid_to_account_id=' + currentAccountId);
        $('#viewLedgerBtn').attr('href', '{{ url("accounts") }}/' + currentAccountId);
        $('#modalPeriod').text(formatDate($('#start_date').val()) + ' to ' + formatDate($('#end_date').val()));
        
        loadTransactionDetails(currentAccountId);
        $('#transactionModal').modal('show');
    });
    
    // Load Transaction Details
    function loadTransactionDetails(accountId) {
        if ($.fn.DataTable.isDataTable('#transactionDetailsTable')) {
            $('#transactionDetailsTable').DataTable().destroy();
        }
        
        $('#transactionDetailsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ url("reports/payables") }}/' + accountId + '/transactions',
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                },
                dataSrc: function(json) {
                    updateModalTotals(json.data);
                    return json.data;
                }
            },
            columns: [
                { data: 'date', name: 'date' },
                { data: 'reference_link', name: 'reference' },
                { data: 'transaction_type_badge', name: 'transaction_type' },
                { data: 'description_full', name: 'description' },
                { data: 'debit', name: 'debit', className: 'text-right' },
                { data: 'credit', name: 'credit', className: 'text-right' }
            ],
            order: [[0, 'desc']],
            pageLength: 10,
            language: {
                emptyTable: "No transactions found",
                processing: '<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>'
            }
        });
    }
    
    // Update Summary Cards
    function updateSummaryCards(data) {
        let totalPayable = 0;
        let totalAdvance = 0;
        let accountCount = 0;
        let vendorCount = 0;
        
        data.forEach(function(row) {
            const balanceText = $(row.current_balance).text().replace(/[৳,\s]/g, '');
            const balance = parseFloat(balanceText);
            
            if (balanceText.includes('Payable')) {
                totalPayable += balance;
                accountCount++;
            } else if (balanceText.includes('Advance')) {
                totalAdvance += balance;
            }
            
            if (row.vendor_info && !row.vendor_info.includes('text-muted')) {
                vendorCount++;
            }
        });
        
        $('#totalPayable').text('৳ ' + formatNumber(totalPayable));
        $('#totalAdvance').text('৳ ' + formatNumber(totalAdvance));
        $('#totalAccounts').text(accountCount);
        $('#totalVendors').text(vendorCount);
    }
    
    // Update Footer Totals
    function updateFooterTotals(data) {
        let totalDebits = 0;
        let totalCredits = 0;
        let totalBalance = 0;
        
        data.forEach(function(row) {
            totalDebits += parseFloat($(row.total_debits).text().replace(/[৳,\s]/g, ''));
            totalCredits += parseFloat($(row.total_credits).text().replace(/[৳,\s]/g, ''));
            
            const balanceText = $(row.current_balance).text().replace(/[৳,\s]/g, '');
            const balance = parseFloat(balanceText);
            
            if (balanceText.includes('Payable')) {
                totalBalance += balance;
            } else if (balanceText.includes('Advance')) {
                totalBalance -= balance;
            }
        });
        
        $('#footerDebits').text('৳ ' + formatNumber(totalDebits));
        $('#footerCredits').text('৳ ' + formatNumber(totalCredits));
        
        const balanceClass = totalBalance > 0 ? 'text-danger' : 'text-success';
        $('#footerBalance').html('<span class="' + balanceClass + '">৳ ' + formatNumber(Math.abs(totalBalance)) + '</span>');
    }
    
    // Update Modal Totals
    function updateModalTotals(data) {
        let totalDebits = 0;
        let totalCredits = 0;
        
        data.forEach(function(row) {
            const debitText = $(row.debit).text().replace(/[৳,\s]/g, '');
            const creditText = $(row.credit).text().replace(/[৳,\s]/g, '');
            
            totalDebits += parseFloat(debitText) || 0;
            totalCredits += parseFloat(creditText) || 0;
        });
        
        const netBalance = totalCredits - totalDebits;
        const balanceClass = netBalance > 0 ? 'text-danger' : 'text-success';
        const balanceLabel = netBalance > 0 ? 'Payable' : 'Advance';
        
        $('#modalTotalDebits').text('৳ ' + formatNumber(totalDebits));
        $('#modalTotalCredits').text('৳ ' + formatNumber(totalCredits));
        $('#modalSummary').html(
            'Net Balance: <span class="' + balanceClass + ' font-weight-bold">৳ ' + 
            formatNumber(Math.abs(netBalance)) + ' (' + balanceLabel + ')</span>'
        );
    }
    
    // Export to CSV
    $('#exportCsv').on('click', function() {
        const params = new URLSearchParams({
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            account_id: $('#account_id').val() || '',
            vendor_id: $('#vendor_id').val() || ''
        });
        
        window.location.href = '{{ route("reports.payables.csv") }}?' + params.toString();
        
        Swal.fire({
            icon: 'success',
            title: 'Exporting...',
            text: 'Your CSV file is being downloaded.',
            timer: 2000,
            showConfirmButton: false
        });
    });
    
    // Print Report
    $('#printReport').on('click', function() {
        const params = new URLSearchParams({
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            account_id: $('#account_id').val() || '',
            vendor_id: $('#vendor_id').val() || ''
        });
        
        window.open('{{ route("reports.payables.print") }}?' + params.toString(), '_blank');
    });
    
    // Helper Functions
    function formatNumber(num) {
        return num.toLocaleString('en-BD', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
});
</script>
@endpush
