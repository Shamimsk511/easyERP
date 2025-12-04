@extends('adminlte::page')

@section('title', 'Trial Balance')

@section('content_header')
    <h1>Trial Balance</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Trial Balance Report</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-success btn-sm" id="export-excel">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="export-pdf">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="print-report">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form id="filter-form" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>As of Date</label>
                        <input type="date" 
                               class="form-control border" 
                               name="as_of_date" 
                               id="as_of_date" 
                               value="{{ $asOfDate }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Account Type</label>
                        <select class="form-control border" name="account_type" id="account_type">
                            @foreach($accountTypes as $key => $label)
                                <option value="{{ $key }}" {{ $accountType == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="show_zero_balance" 
                                   name="show_zero_balance" 
                                   value="1"
                                   {{ $showZeroBalance ? 'checked' : '' }}>
                            <label class="custom-control-label" for="show_zero_balance">
                                Show Zero Balances
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Balance Status Alert -->
        @if($isBalanced)
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>Trial Balance is Balanced!</strong> 
                Total Debits = Total Credits (৳ {{ number_format($totalDebit, 2) }})
            </div>
        @else
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Trial Balance is NOT Balanced!</strong> 
                Difference: ৳ {{ number_format($difference, 2) }}
            </div>
        @endif

        <!-- Trial Balance Table -->
        <div class="table-responsive" id="printable-area">
            <div class="text-center mb-3 d-none d-print-block">
                <h4>{{ config('app.name') }}</h4>
                <h5>Trial Balance</h5>
                <p>As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</p>
            </div>
            
            <table class="table table-bordered table-striped table-hover" id="trial-balance-table">
                <thead class="thead-dark">
                    <tr>
                        <th width="10%">Code</th>
                        <th width="40%">Account Name</th>
                        <th width="15%">Type</th>
                        <th width="17.5%" class="text-right">Debit (৳)</th>
                        <th width="17.5%" class="text-right">Credit (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>
                                <a href="{{ route('accounts.show', $account['id']) }}" target="_blank" class="d-print-none">
                                    <code>{{ $account['code'] }}</code>
                                </a>
                                <span class="d-none d-print-inline">{{ $account['code'] }}</span>
                            </td>
                            <td>{{ $account['name'] }}</td>
                            <td>
                                <span class="badge badge-{{ $account['type'] == 'asset' ? 'primary' : ($account['type'] == 'liability' ? 'danger' : ($account['type'] == 'equity' ? 'info' : ($account['type'] == 'income' ? 'success' : 'warning'))) }}">
                                    {{ ucfirst($account['type']) }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if($account['debit_balance'] > 0)
                                    <strong>{{ number_format($account['debit_balance'], 2) }}</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($account['credit_balance'] > 0)
                                    <strong>{{ number_format($account['credit_balance'], 2) }}</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No accounts found</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="thead-dark">
                    <tr>
                        <th colspan="3" class="text-right">Total:</th>
                        <th class="text-right">
                            <strong>{{ number_format($totalDebit, 2) }}</strong>
                        </th>
                        <th class="text-right">
                            <strong>{{ number_format($totalCredit, 2) }}</strong>
                        </th>
                    </tr>
                    @if(!$isBalanced)
                        <tr class="bg-warning">
                            <th colspan="3" class="text-right">Difference:</th>
                            <th colspan="2" class="text-center">
                                <strong>৳ {{ number_format($difference, 2) }}</strong>
                            </th>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Apply filter
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        window.location.href = '{{ route("reports.trial-balance") }}?' + $(this).serialize();
    });
    
    // Export Excel
    $('#export-excel').on('click', function() {
        window.location.href = '{{ route("reports.trial-balance.excel") }}?' + $('#filter-form').serialize();
    });
    
    // Export PDF
    $('#export-pdf').on('click', function() {
        window.location.href = '{{ route("reports.trial-balance.pdf") }}?' + $('#filter-form').serialize();
    });
    
    // Print
    $('#print-report').on('click', function() {
        window.print();
    });
});
</script>
@stop

@section('css')
<style>
@media print {
    .btn, .card-tools, .form-group, .alert {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        display: none !important;
    }
}
</style>
@stop
