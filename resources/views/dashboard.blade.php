@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Accounting Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <!-- Total Accounts -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ \App\Models\Account::where('is_active', true)->count() }}</h3>
                    <p>Active Accounts</p>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
                <a href="{{ route('accounts.index') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ \App\Models\Transaction::where('status', 'posted')->count() }}</h3>
                    <p>Posted Transactions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <a href="{{ route('transactions.index', ['status' => 'posted']) }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- Draft Transactions -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ \App\Models\Transaction::where('status', 'draft')->count() }}</h3>
                    <p>Draft Transactions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-edit"></i>
                </div>
                <a href="{{ route('transactions.index', ['status' => 'draft']) }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <!-- This Month Transactions -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ \App\Models\Transaction::whereMonth('date', date('m'))->whereYear('date', date('Y'))->count() }}</h3>
                    <p>This Month</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <a href="{{ route('transactions.index') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header border-transparent">
                    <h3 class="card-title">Recent Transactions</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table m-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentTransactions = \App\Models\Transaction::with('entries')
                                        ->latest('date')
                                        ->take(10)
                                        ->get();
                                @endphp
                                @forelse($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->date->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('transactions.show', $transaction) }}">
                                                {{ $transaction->description }}
                                            </a>
                                        </td>
                                        <td>{{ number_format($transaction->getTotalDebits(), 2) }}</td>
                                        <td>
                                            @if($transaction->status == 'posted')
                                                <span class="badge badge-success">Posted</span>
                                            @elseif($transaction->status == 'draft')
                                                <span class="badge badge-warning">Draft</span>
                                            @else
                                                <span class="badge badge-danger">Voided</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No transactions yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer clearfix">
                    <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-success float-left">
                        <i class="fas fa-plus"></i> New Transaction
                    </a>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-secondary float-right">
                        View All
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('transactions.create') }}" class="btn btn-success btn-block">
                        <i class="fas fa-plus"></i> New Transaction
                    </a>
                    <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-plus"></i> New Account
                    </a>
                    <a href="{{ route('accounts.index') }}" class="btn btn-info btn-block">
                        <i class="fas fa-list"></i> Chart of Accounts
                    </a>
                    <a href="{{ route('transactions.index') }}" class="btn btn-warning btn-block">
                        <i class="fas fa-book"></i> Journal Entries
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Types Summary</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach(['asset', 'liability', 'equity', 'income', 'expense'] as $type)
                            @php
                                $count = \App\Models\Account::where('type', $type)->where('is_active', true)->count();
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fas fa-circle text-{{ $type == 'asset' ? 'primary' : ($type == 'liability' ? 'danger' : ($type == 'equity' ? 'info' : ($type == 'income' ? 'success' : 'warning'))) }}"></i>
                                    {{ ucfirst($type) }}
                                </span>
                                <span class="badge badge-primary badge-pill">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .small-box h3 {
            font-size: 2.2rem;
        }
    </style>
@stop
