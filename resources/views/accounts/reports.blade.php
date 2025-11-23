@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <h1>Accounting Reports</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-file-invoice fa-3x mb-3"></i>
                    </div>
                    <h3 class="profile-username text-center">Trial Balance</h3>
                    <p class="text-muted text-center">View debits and credits summary</p>
                    <a href="{{ route('reports.trial-balance') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-eye"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-success card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                    </div>
                    <h3 class="profile-username text-center">Profit & Loss</h3>
                    <p class="text-muted text-center">Income and expense statement</p>
                    <a href="{{ route('reports.profit-loss') }}" class="btn btn-success btn-block">
                        <i class="fas fa-eye"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-balance-scale fa-3x mb-3"></i>
                    </div>
                    <h3 class="profile-username text-center">Balance Sheet</h3>
                    <p class="text-muted text-center">Assets, liabilities & equity</p>
                    <a href="{{ route('reports.balance-sheet') }}" class="btn btn-info btn-block">
                        <i class="fas fa-eye"></i> View Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-warning card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-book fa-3x mb-3"></i>
                    </div>
                    <h3 class="profile-username text-center">General Ledger</h3>
                    <p class="text-muted text-center">Detailed account transactions</p>
                    <a href="{{ route('reports.general-ledger') }}" class="btn btn-warning btn-block">
                        <i class="fas fa-eye"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-danger card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                    </div>
                    <h3 class="profile-username text-center">Journal Entries</h3>
                    <p class="text-muted text-center">All transaction entries</p>
                    <a href="{{ route('transactions.index') }}" class="btn btn-danger btn-block">
                        <i class="fas fa-eye"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-secondary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-sitemap fa-3x mb-3"></i>
                    </div>
                    <h3 class="profile-username text-center">Chart of Accounts</h3>
                    <p class="text-muted text-center">All account hierarchy</p>
                    <a href="{{ route('accounts.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-eye"></i> View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop
