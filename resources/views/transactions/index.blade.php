@extends('adminlte::page')

@section('title', 'Transactions')

@section('content_header')
    <h1>Journal Transactions</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Transactions</h3>
            <div class="card-tools">
                <a href="{{ route('transactions.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Transaction
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('transactions.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" placeholder="From Date" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" placeholder="To Date" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->date->format('d M Y') }}</td>
                                <td>{{ $transaction->reference ?? '-' }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td class="text-right">{{ number_format($transaction->getTotalDebits(), 2) }}</td>
                                <td>
                                    @if($transaction->status == 'posted')
                                        <span class="badge badge-success">Posted</span>
                                    @elseif($transaction->status == 'draft')
                                        <span class="badge badge-warning">Draft</span>
                                    @else
                                        <span class="badge badge-danger">Voided</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-info btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($transaction->status == 'draft')
                                        <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($transaction->status == 'posted')
                                        <form action="{{ route('transactions.void', $transaction) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Void this transaction?')" title="Void">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer clearfix">
            {{ $transactions->links() }}
        </div>
    </div>
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
