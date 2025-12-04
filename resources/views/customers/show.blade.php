@extends('adminlte::page')

@section('title', 'Customer Details - ' . $customer->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Customer Details</h1>
        <div>
            <a href="{{ route('customers.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Customer
            </a>
            @if($customer->is_active)
                <button type="button" class="btn btn-warning" id="deactivateBtn">
                    <i class="fas fa-ban"></i> Deactivate
                </button>
            @else
                <span class="badge badge-secondary p-2">Deactivated</span>
            @endif
            <button type="button" class="btn btn-danger" id="deleteBtn">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <!-- Left Column: Customer Info & Status -->
        <div class="col-md-4">
            <!-- Customer Profile Card -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <div class="profile-user-img img-fluid img-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                             style="width: 100px; height: 100px; font-size: 48px; color: white;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <h3 class="profile-username text-center">{{ $customer->name }}</h3>

                    <p class="text-muted text-center">
                        @if($customer->group)
                            <span class="badge badge-info">{{ $customer->group->name }}</span>
                        @else
                            <span class="badge badge-secondary">No Group</span>
                        @endif
                        
                        @if($customer->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Customer Code</b>
                            <a class="float-right">{{ $customer->customer_code }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Current Balance</b>
                            <a class="float-right">
                                @php
                                    $balance = $customer->transactions()->sum(DB::raw('debit - credit')) + 
                                               ($customer->opening_balance_type === 'debit' ? $customer->opening_balance : -$customer->opening_balance);
                                    $balanceClass = $balance >= 0 ? 'text-danger' : 'text-success';
                                    $balanceLabel = $balance >= 0 ? 'Dr' : 'Cr';
                                @endphp
                                <span class="{{ $balanceClass }} font-weight-bold">
                                    ৳ {{ number_format(abs($balance), 2) }} {{ $balanceLabel }}
                                </span>
                            </a>
                        </li>
                        <li class="list-group-item">
                            <b>Credit Limit</b>
                            <a class="float-right">৳ {{ number_format($customer->credit_limit, 2) }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Credit Period</b>
                            <a class="float-right">{{ $customer->credit_period_days }} days</a>
                        </li>
                        @if($customer->current_due_date)
                            <li class="list-group-item">
                                <b>Due Date</b>
                                <a class="float-right">
                                    {{ \Carbon\Carbon::parse($customer->current_due_date)->format('d M, Y') }}
                                    @php
                                        $dueDate = \Carbon\Carbon::parse($customer->current_due_date);
                                        $today = \Carbon\Carbon::today();
                                        $isOverdue = $dueDate->lt($today) && $balance > 0;
                                    @endphp
                                    @if($isOverdue)
                                        <br><span class="badge badge-danger mt-1">
                                            Overdue: {{ $today->diffInDays($dueDate) }} days
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    </ul>

                    @if($customer->current_due_date && $balance > 0)
                        <button type="button" class="btn btn-warning btn-block" id="extendDueDateBtn">
                            <i class="fas fa-calendar-plus"></i> Extend Due Date
                        </button>
                    @endif
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-address-card"></i> Contact Information</h3>
                </div>
                <div class="card-body">
                    <strong><i class="fas fa-phone mr-1"></i> Phone</strong>
                    <p class="text-muted">{{ $customer->phone }}</p>

                    @if($customer->email)
                        <hr>
                        <strong><i class="fas fa-envelope mr-1"></i> Email</strong>
                        <p class="text-muted">{{ $customer->email }}</p>
                    @endif

                    @if($customer->address)
                        <hr>
                        <strong><i class="fas fa-map-marker-alt mr-1"></i> Address</strong>
                        <p class="text-muted">
                            {{ $customer->address }}
                            @if($customer->city), {{ $customer->city }}@endif
                            @if($customer->state), {{ $customer->state }}@endif
                            @if($customer->postal_code) - {{ $customer->postal_code }}@endif
                            @if($customer->country)<br>{{ $customer->country }}@endif
                        </p>
                    @endif

                    @if($customer->notes)
                        <hr>
                        <strong><i class="fas fa-sticky-note mr-1"></i> Notes</strong>
                        <p class="text-muted">{{ $customer->notes }}</p>
                    @endif
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book"></i> Accounting Information</h3>
                </div>
                <div class="card-body">
                    <strong><i class="fas fa-code-branch mr-1"></i> Ledger Account</strong>
                    <p class="text-muted">
                        {{ $customer->ledgerAccount->name }}<br>
                        <small>Code: {{ $customer->ledgerAccount->code }}</small>
                    </p>

                    <hr>
                    <strong><i class="fas fa-calendar mr-1"></i> Opening Balance</strong>
                    <p class="text-muted">
                        ৳ {{ number_format($customer->opening_balance, 2) }} 
                        <span class="badge badge-{{ $customer->opening_balance_type === 'debit' ? 'danger' : 'success' }}">
                            {{ ucfirst($customer->opening_balance_type) }}
                        </span>
                        <br>
                        <small>Date: {{ \Carbon\Carbon::parse($customer->opening_balance_date)->format('d M, Y') }}</small>
                    </p>

                    <hr>
                    <strong><i class="fas fa-clock mr-1"></i> Member Since</strong>
                    <p class="text-muted">{{ $customer->created_at->format('d M, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Right Column: Transactions & Activity -->
        <div class="col-md-8">
            <!-- Statistics Cards Row -->
            <div class="row">
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Sales</span>
                            <span class="info-box-number">৳ {{ number_format($customer->transactions()->where('voucher_type', 'Invoice')->sum('debit'), 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Received</span>
                            <span class="info-box-number">৳ {{ number_format($customer->transactions()->where('voucher_type', 'Receipt')->sum('credit'), 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Invoices</span>
                            <span class="info-box-number">{{ $customer->transactions()->where('voucher_type', 'Invoice')->count() }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Outstanding</span>
                            <span class="info-box-number">৳ {{ number_format($balance >= 0 ? $balance : 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Summary Chart -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Transaction Trend (Last 12 Months)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="transactionChart" style="height: 300px;"></canvas>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Recent Transactions</h3>
                    <div class="card-tools">
                        <a href="{{ route('customers.ledger', $customer->id) }}" class="btn btn-sm btn-light">
                            <i class="fas fa-book"></i> View Full Ledger
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="transactionsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Voucher No.</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                                <th>Narration</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Due Date Extension History -->
            @if($customer->dueExtensions->count() > 0)
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> Due Date Extension History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Original Date</th>
                                    <th>Extended To</th>
                                    <th>Reason</th>
                                    <th>Extended By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->dueExtensions as $extension)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($extension->original_due_date)->format('d M, Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($extension->extended_due_date)->format('d M, Y') }}</td>
                                        <td>{{ $extension->reason ?? '-' }}</td>
                                        <td>{{ $extension->extendedBy->name ?? 'System' }}</td>
                                        <td>{{ $extension->created_at->format('d M, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <style>
        .profile-user-img {
            border: 3px solid #adb5bd;
            margin: 0 auto;
            padding: 3px;
        }
        
        .info-box-number {
            font-size: 1.2rem;
        }
        
        .list-group-item b {
            color: #495057;
        }
    </style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize DataTable for recent transactions
    const transactionsTable = $('#transactionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("customers.ledger.data", $customer->id) }}',
            type: 'GET'
        },
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'voucher_type', name: 'voucher_type', orderable: false },
            { data: 'voucher_number', name: 'voucher_number' },
            { data: 'debit', name: 'debit', className: 'text-right' },
            { data: 'credit', name: 'credit', className: 'text-right' },
            { data: 'balance', name: 'balance', className: 'text-right' },
            { data: 'narration', name: 'narration', orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
            emptyTable: "No transactions found for this customer"
        },
        drawCallback: function() {
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

// Transaction Trend Chart
const ctx = document.getElementById('transactionChart').getContext('2d');
    
    // Fetch chart data
   $.ajax({
    url: '/customers/{{ $customer->id }}/chart-data',

    type: 'GET',
    success: function(response) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: response.labels,
                datasets: [
                    {
                        label: 'Sales (Debit)',
                        data: response.sales,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Payments (Credit)',
                        data: response.payments,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Balance',
                        data: response.balance,
                        borderColor: 'rgb(255, 205, 86)',
                        backgroundColor: 'rgba(255, 205, 86, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '৳ ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Amount (৳)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Balance (৳)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    },
    error: function(xhr, status, error) {
        console.error('Chart data error:', error);
        console.error('Response:', xhr.responseText);
        $('#transactionChart').parent().html('<p class="text-center text-muted">Unable to load chart data</p>');
    }
});

    // Extend Due Date Button
    $('#extendDueDateBtn').on('click', function() {
        Swal.fire({
            title: 'Extend Due Date',
            html: `
                <div class="form-group text-left">
                    <label>New Due Date</label>
                    <input type="date" id="extended_due_date" class="form-control" 
                           min="{{ \Carbon\Carbon::parse($customer->current_due_date)->addDay()->format('Y-m-d') }}"
                           value="{{ \Carbon\Carbon::parse($customer->current_due_date)->addDays(7)->format('Y-m-d') }}">
                </div>
                <div class="form-group text-left">
                    <label>Reason (Optional)</label>
                    <textarea id="extension_reason" class="form-control" rows="3" 
                              placeholder="Enter reason for extension..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Extend',
            confirmButtonColor: '#28a745',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const date = document.getElementById('extended_due_date').value;
                const reason = document.getElementById('extension_reason').value;
                
                if (!date) {
                    Swal.showValidationMessage('Please select a new due date');
                    return false;
                }
                
                return { date: date, reason: reason };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("customers.extend-due-date", $customer->id) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        extended_due_date: result.value.date,
                        reason: result.value.reason
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to extend due date'
                        });
                    }
                });
            }
        });
    });

    // Deactivate Button
    $('#deactivateBtn').on('click', function() {
        Swal.fire({
            title: 'Deactivate Customer?',
            text: "{{ $customer->name }} will be marked as inactive and won't appear in active lists.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, deactivate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("customers.deactivate", $customer->id) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deactivated!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to deactivate customer'
                        });
                    }
                });
            }
        });
    });

    // Delete Button
    $('#deleteBtn').on('click', function() {
        Swal.fire({
            title: 'Delete Customer?',
            html: `
                <p class="text-danger font-weight-bold">This action cannot be undone!</p>
                <p>Customer: <strong>{{ $customer->name }}</strong></p>
                <p class="text-muted">All related data will be permanently deleted.</p>
            `,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete permanently',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("customers.destroy", $customer->id) }}',
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '{{ route("customers.index") }}';
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot Delete!',
                            text: xhr.responseJSON?.message || 'Failed to delete customer',
                            footer: '<span class="text-muted">This customer may have existing transactions</span>'
                        });
                    }
                });
            }
        });
    });
});
</script>
@stop
