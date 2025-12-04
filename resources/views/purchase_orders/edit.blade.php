@extends('adminlte::page')
@section('title', 'Edit Purchase Order')

@section('content')
<div class="card">
    <div class="card-header"><h3>Edit Purchase Order #{{ $purchaseOrder->order_number }}</h3></div>
    <div class="card-body">
        @if($purchaseOrder->status == 'received')
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> This order has been received and cannot be edited.
            </div>
            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-secondary">View Order</a>
        @else
        <form id="purchase-edit-form" data-id="{{ $purchaseOrder->id }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="vendor_id">Vendor <span class="text-danger">*</span></label>
                <select class="form-control select2 border" name="vendor_id" id="vendor_id" required>
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ $purchaseOrder->vendor_id == $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Order Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control border" name="order_date" value="{{ $purchaseOrder->order_date }}" required>
            </div>
            <div class="form-group">
                <label>Products</label>
                <table class="table" id="po-items">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>
                                <button type="button" class="btn btn-success btn-sm add-row">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($purchaseOrder->items as $idx => $item)
                    <tr>
                        <td>
                            <select class="form-control select2 product-select border" name="items[{{ $idx }}][product_id]" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control border qty" name="items[{{ $idx }}][quantity]" value="{{ $item->quantity }}" min="1" required></td>
                        <td><input type="number" class="form-control border rate" name="items[{{ $idx }}][rate]" value="{{ $item->rate }}" min="0.01" step="0.01" required></td>
                        <td><input type="text" class="form-control border amount" value="{{ $item->amount }}" readonly></td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea class="form-control border" name="notes">{{ $purchaseOrder->notes }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Purchase Order</button>
        </form>
        @endif
    </div>
</div>

@push('js')
<script>
function rowCount() {return $('#po-items tbody tr').length;}
function updateIndexes() {
    $('#po-items tbody tr').each(function(i, tr){
        $(tr).find('select, input').each(function(){
            var n = $(this).attr('name');
            if(n && n.includes('items[')){
                var newN = n.replace(/items\[\d+\]/, 'items['+i+']');
                $(this).attr('name', newN);
            }
        });
    });
}
$('.select2, .product-select').select2({width: '100%'});
$('body').on('click','.add-row',function(){
    let idx = rowCount();
    let row = `<tr>
        <td>
            <select class="form-control select2 product-select border" name="items[${idx}][product_id]" required>
                <option value="">Select Product</option>
                @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control border qty" name="items[${idx}][quantity]" min="1" required></td>
        <td><input type="number" class="form-control border rate" name="items[${idx}][rate]" min="0.01" step="0.01" required></td>
        <td><input type="text" class="form-control border amount" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    $('#po-items tbody').append(row);
    $('.select2').select2({width:'100%'});
});
$('body').on('click', '.remove-row', function(){
    $(this).closest('tr').remove();
    updateIndexes();
});
$('body').on('input', '.qty, .rate', function(){
    let tr=$(this).closest('tr'), qty=parseFloat(tr.find('.qty').val()), rate=parseFloat(tr.find('.rate').val());
    tr.find('.amount').val((qty && rate) ? (qty*rate).toFixed(2) : '');
});
$('#purchase-edit-form').on('submit',function(e){
    e.preventDefault();
    let id = $(this).data('id');
    $.ajax({
        url: '{{ url("purchase-orders") }}/' + id,
        method: 'PUT',
        data: $(this).serialize(),
        success: () => window.location='{{ route("purchase-orders.index") }}',
        error: err => Swal.fire('Error', err.responseJSON?.message || 'Failed', 'error')
    });
});
</script>
@endpush

@endsection
