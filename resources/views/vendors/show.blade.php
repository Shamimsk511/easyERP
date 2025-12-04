@extends('adminlte::page')
@section('title', 'Vendor Details')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>Vendor: {{ $vendor->name }}</h3>
        <div class="card-tools">
            <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-sm btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Name:</dt>
            <dd class="col-sm-9">{{ $vendor->name }}</dd>
            
            <dt class="col-sm-3">Description:</dt>
            <dd class="col-sm-9">{{ $vendor->description ?? '-' }}</dd>
            
            <dt class="col-sm-3">Ledger Account:</dt>
            <dd class="col-sm-9">
                @if($vendor->ledgerAccount)
                    <a href="{{ route('accounts.show', $vendor->ledger_account_id) }}">
                        <span class="badge badge-info">{{ $vendor->ledgerAccount->name }}</span>
                    </a>
                @else
                    <span class="text-muted">Not assigned</span>
                @endif
            </dd>
            
            <dt class="col-sm-3">Created:</dt>
            <dd class="col-sm-9">{{ $vendor->created_at->format('d M Y, H:i') }}</dd>
        </dl>
    </div>
</div>

<!-- Purchase Orders List -->
<div class="card">
    <div class="card-header">
        <h4>Purchase Orders</h4>
    </div>
    <div class="card-body">
        <table id="vendor-po-table" class="table table-bordered">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendor->purchaseOrders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->order_date }}</td>
                        <td>
                            @if($order->status == 'received')
                                <span class="badge badge-success">Received</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td>৳ {{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No purchase orders found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
