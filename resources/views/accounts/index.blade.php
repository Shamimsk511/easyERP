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
                <a href="{{ route('accounts.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Account
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('accounts.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or code..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="asset" {{ request('type') == 'asset' ? 'selected' : '' }}>Asset</option>
                            <option value="liability" {{ request('type') == 'liability' ? 'selected' : '' }}>Liability</option>
                            <option value="equity" {{ request('type') == 'equity' ? 'selected' : '' }}>Equity</option>
                            <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="is_active" class="form-control">
                            <option value="">All Status</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Accounts Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
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
                        @forelse($accounts as $account)
                            <tr>
                                <td>{{ $account->code }}</td>
                                <td>{{ $account->name }}</td>
                                <td>
                                    <span class="badge badge-{{ $account->type == 'asset' ? 'primary' : ($account->type == 'liability' ? 'danger' : ($account->type == 'equity' ? 'info' : ($account->type == 'income' ? 'success' : 'warning'))) }}">
                                        {{ ucfirst($account->type) }}
                                    </span>
                                </td>
                                <td>{{ $account->parentAccount ? $account->parentAccount->name : '-' }}</td>
                                <td class="text-right">
                                    <span class="{{ $account->current_balance < 0 ? 'text-danger' : '' }}">
                                        {{ number_format($account->current_balance, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($account->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('accounts.show', $account) }}" class="btn btn-info btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('accounts.edit', $account) }}" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('accounts.destroy', $account) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this account?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer clearfix">
            {{ $accounts->links() }}
        </div>
    </div>
@stop

@section('css')
    <style>
        .table td, .table th {
            vertical-align: middle;
        }
    </style>
@stop

@section('js')
    <script>
        @if(session('success'))
            $(document).ready(function() {
                toastr.success('{{ session('success') }}');
            });
        @endif
        
        @if(session('error'))
            $(document).ready(function() {
                toastr.error('{{ session('error') }}');
            });
        @endif
    </script>
@stop
