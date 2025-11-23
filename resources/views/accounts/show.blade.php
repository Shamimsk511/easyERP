@extends('adminlte::page')

@section('title', 'Account Details')

@section('content_header')
    <h1>Account Details</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $account->code }} - {{ $account->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('accounts.edit', $account) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Account Code</th>
                            <td>{{ $account->code }}</td>
                        </tr>
                        <tr>
                            <th>Account Name</th>
                            <td>{{ $account->name }}</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>
                                <span class="badge badge-{{ $account->type == 'asset' ? 'primary' : ($account->type == 'liability' ? 'danger' : ($account->type == 'equity' ? 'info' : ($account->type == 'income' ? 'success' : 'warning'))) }}">
                                    {{ ucfirst($account->type) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Parent Account</th>
                            <td>{{ $account->parentAccount ? $account->parentAccount->code . ' - ' . $account->parentAccount->name : 'None' }}</td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>{{ $account->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Opening Balance</th>
                            <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Opening Balance Date</th>
                            <td>{{ $account->opening_balance_date?->format('d M Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($account->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                </div>
                
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEntries as $entry)
                                <tr>
                                    <td>{{ $entry->transaction->date->format('d M Y') }}</td>
                                    <td>{{ $entry->transaction->description }}</td>
                                    <td class="text-right">{{ $entry->type == 'debit' ? number_format($entry->amount, 2) : '' }}</td>
                                    <td class="text-right">{{ $entry->type == 'credit' ? number_format($entry->amount, 2) : '' }}</td>
                                    <td>
                                        <a href="{{ route('transactions.show', $entry->transaction) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No transactions found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Current Balance Widget -->
            <div class="small-box bg-{{ $account->isDebitAccount() ? 'info' : 'success' }}">
                <div class="inner">
                    <h3>{{ number_format($currentBalance, 2) }}</h3>
                    <p>Current Balance</p>
                </div>
                <div class="icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <!-- Child Accounts -->
            @if($account->childAccounts->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sub-Accounts</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($account->childAccounts as $child)
                                <li class="list-group-item">
                                    <a href="{{ route('accounts.show', $child) }}">
                                        {{ $child->code }} - {{ $child->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop
