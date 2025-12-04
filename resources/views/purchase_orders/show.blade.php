@extends('adminlte::page')
@section('title', "Order #$purchaseOrder->order_number")

@section('content')
<div class="card">
    <div class="card-header">
        <h3>Purchase Order #{{ $purchaseOrder->order_number }}</h3>
        @if($purchaseOrder->status == 'pending')
            <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST" class="float-right" id="receive-form">@csrf
                <button class="btn btn-success">Mark as Received</button>
            </form>
        @endif
    </div>
    <div class="card-body">
        <dl>
            <dt>Vendor</dt><dd>{{ $purchaseOrder->vendor->name }}</dd>
            <dt>Status</dt><dd>
                @if($purchaseOrder->status == 'received')
                    <span class="badge badge-success">Received</span>
                @else
                    <span class="badge badge-warning">Pending</span>
                @endif
            </dd>
            <dt>Order Date</dt><dd>{{ $purchaseOrder->order_date }}</dd>
            <dt>Notes</dt><dd>{{ $purchaseOrder->notes }}</dd>
        </dl>
        <h5>Items</h5>
        <table class="table">
            <thead>
                <tr><th>Product</th><th>Qty</th><th>Rate</th><th>Amount</th></tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->rate }}</td>
                    <td>{{ $item->amount }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot><tr>
                <th colspan="3" class="text-right">Total</th>
                <th>{{ $purchaseOrder->total_amount }}</th>
            </tr></tfoot>
        </table>
    </div>
</div>
@push('js')
<script>
$('#receive-form').on('submit',function(e){
    e.preventDefault();
    $.post($(this).attr('action'), $(this).serialize(), function(res){
        window.location.reload();
    }).fail(err=>Swal.fire('Error',err.responseJSON?.error||'Failed','error'));
})
</script>
@endpush
@endsection
