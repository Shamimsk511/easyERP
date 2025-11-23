@extends('adminlte::page')

@section('title', 'Transaction Details')

@section('content_header')
    <h1>Transaction Details</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaction #{{ $transaction->id }}</h3>
            <div class="card-tools">
                @if($transaction->status == 'draft')
                    <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                @endif
                @if($transaction->status == 'posted')
                    <form action="{{ route('transactions.void', $transaction) }}" method="POST" style="display: inline-block;">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Void this transaction?')">
                            <i class="fas fa-ban"></i> Void
                        </button>
                    </form>
                @endif
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <strong>Date:</strong><br>
                    {{ $transaction->date->format('d M Y') }}
                </div>
                <div class="col-md-3">
                    <strong>Reference:</strong><br>
                    {{ $transaction->reference ?? '-' }}
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    @if($transaction->status == 'posted')
                        <span class="badge badge-success">Posted</span>
                    @elseif($transaction->status == 'draft')
                        <span class="badge badge-warning">Draft</span>
                    @else
                        <span class="badge badge-danger">Voided</span>
                    @endif
                </div>
                <div class="col-md-3">
                    <strong>Balanced:</strong><br>
                    @if($transaction->isBalanced())
                        <span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>
                    @else
                        <span class="badge badge-danger"><i class="fas fa-times"></i> No</span>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <strong>Description:</strong><br>
                    {{ $transaction->description }}
                </div>
            </div>

            @if($transaction->notes)
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Notes:</strong><br>
                        {{ $transaction->notes }}
                    </div>
                </div>
            @endif

            <hr>

            <h5>Journal Entries</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Memo</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->entries as $entry)
                            <tr>
                                <td>
                                    <a href="{{ route('accounts.show', $entry->account) }}">
                                        {{ $entry->account->code }} - {{ $entry->account->name }}
                                    </a>
                                </td>
                                <td>{{ $entry->memo ?? '-' }}</td>
                                <td class="text-right">
                                    {{ $entry->type == 'debit' ? number_format($entry->amount, 2) : '' }}
                                </td>
                                <td class="text-right">
                                    {{ $entry->type == 'credit' ? number_format($entry->amount, 2) : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold bg-light">
                            <td colspan="2" class="text-right">Total:</td>
                            <td class="text-right">{{ number_format($transaction->getTotalDebits(), 2) }}</td>
                            <td class="text-right">{{ number_format($transaction->getTotalCredits(), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@stop
