@extends('adminlte::page')

@section('title', 'Accounts')

@section('content_header')
    <h1>Chart of Accounts</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Accounts</h3>
            <div class="card-tools">
                <!-- View Toggle Buttons -->
                <div class="btn-group mr-2" role="group">
                    <a href="{{ route('accounts.index', ['view' => 'table'] + request()->except('view')) }}" 
                       class="btn btn-sm {{ $viewType === 'table' ? 'btn-primary' : 'btn-outline-primary' }}"
                       id="tableViewBtn">
                        <i class="fas fa-table"></i> Table View
                    </a>
                    <a href="{{ route('accounts.index', ['view' => 'tree'] + request()->except('view')) }}" 
                       class="btn btn-sm {{ $viewType === 'tree' ? 'btn-primary' : 'btn-outline-primary' }}"
                       id="treeViewBtn">
                        <i class="fas fa-sitemap"></i> Tree View
                    </a>
                </div>
                
                <a href="{{ route('accounts.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Account
                </a>
            </div>
        </div>
        
        <div class="card-body">
            @if($viewType === 'table')
                <!-- Filter Form for Table View -->
                <form method="GET" action="{{ route('accounts.index') }}" class="mb-3" id="filterForm">
                    <input type="hidden" name="view" value="table">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search by name or code..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-control" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="asset" {{ request('type') == 'asset' ? 'selected' : '' }}>Asset</option>
                                <option value="liability" {{ request('type') == 'liability' ? 'selected' : '' }}>Liability</option>
                                <option value="equity" {{ request('type') == 'equity' ? 'selected' : '' }}>Equity</option>
                                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Income</option>
                                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="is_active" class="form-control" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('accounts.index', ['view' => 'table']) }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table id="accountsTable" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Parent Account</th>
                                <th class="text-right">Current Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this via AJAX -->
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Tree View -->
                <div class="tree-view-container">
                    @if($accounts->count() > 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Click on account names to expand/collapse sub-accounts
                        </div>
                        
                        @foreach($accounts as $account)
                            @include('accounts.partials.tree-node', ['account' => $account, 'level' => 0])
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No accounts found. Create your first account to get started.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    
    <style>
        .table td, .table th {
            vertical-align: middle;
        }
        
        /* Tree View Styles */
        .tree-view-container {
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .tree-node {
            margin-bottom: 5px;
        }
        
        .tree-node-content {
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tree-node-content:hover {
            background-color: #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tree-node-content.level-0 {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .tree-node-content.level-0:hover {
            background-color: #0056b3;
        }
        
        .tree-node-content.level-1 {
            background-color: #e7f3ff;
            margin-left: 30px;
        }
        
        .tree-node-content.level-2 {
            background-color: #f0f8ff;
            margin-left: 60px;
        }
        
        .tree-node-content.level-3 {
            background-color: #f8fbff;
            margin-left: 90px;
        }
        
        .tree-children {
            margin-top: 5px;
            display: none;
        }
        
        .tree-children.show {
            display: block;
        }
        
        .tree-toggle {
            display: inline-block;
            width: 20px;
            text-align: center;
            margin-right: 5px;
            transition: transform 0.3s ease;
        }
        
        .tree-toggle.expanded {
            transform: rotate(90deg);
        }
        
        .account-code {
            font-weight: bold;
            color: #495057;
            margin-right: 10px;
        }
        
        .level-0 .account-code {
            color: #fff;
        }
        
        .account-name {
            flex: 1;
        }
        
        .account-balance {
            font-weight: bold;
            margin-left: auto;
            padding-left: 20px;
        }
        
        .account-balance.positive {
            color: #28a745;
        }
        
        .account-balance.negative {
            color: #dc3545;
        }
        
        .level-0 .account-balance {
            color: #fff;
            font-size: 1.2em;
        }
        
        .account-total {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            margin-top: 5px;
        }
        
        .tree-node-flex {
            display: flex;
            align-items: center;
        }
        
        /* View Toggle Buttons */
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
    </style>
@stop

@section('js')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            @if($viewType === 'table')
                // Initialize DataTable
                var table = $('#accountsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('accounts.index') }}',
                        data: function(d) {
                            d.type = $('#typeFilter').val();
                            d.is_active = $('#statusFilter').val();
                            d.view = 'table';
                        }
                    },
                    columns: [
                        { data: 'code', name: 'code' },
                        { data: 'name', name: 'name' },
                        { data: 'type_badge', name: 'type', orderable: true, searchable: true },
                        { data: 'parent_name', name: 'parentAccount.name', orderable: false },
                        { data: 'balance_formatted', name: 'current_balance', className: 'text-right' },
                        { data: 'status_badge', name: 'is_active', orderable: true, searchable: false },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false }
                    ],
                    order: [[0, 'asc']],
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    language: {
                        processing: '<i class="fas fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
                    },
                    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                });
                
                // Reload table when filters change
                $('#typeFilter, #statusFilter').on('change', function() {
                    table.ajax.reload();
                });
            @else
                // Tree View Toggle Functionality
                $('.tree-node-content').on('click', function(e) {
                    e.preventDefault();
                    var $this = $(this);
                    var $children = $this.siblings('.tree-children');
                    var $toggle = $this.find('.tree-toggle');
                    
                    if ($children.length > 0) {
                        $children.toggleClass('show');
                        $toggle.toggleClass('expanded');
                    }
                });
                
                // Expand all primary accounts by default
                $('.tree-node-content.level-0').each(function() {
                    $(this).siblings('.tree-children').addClass('show');
                    $(this).find('.tree-toggle').addClass('expanded');
                });
            @endif
            
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
