@extends('adminlte::page')

@section('title', 'Create Delivery - ' . $invoice->invoice_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Create Delivery Challan for {{ $invoice->invoice_number }}</h3>
                </div>

                <form id="delivery-form">
                    @csrf
                    <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                    <div class="card-body">
                        <!-- Delivery Info -->
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="delivery_date">Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" id="delivery_date" name="delivery_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="delivery_method">Delivery Method</label>
                                <select id="delivery_method" name="delivery_method" class="form-control">
                                    <option value="auto">Auto</option>
                                    <option value="motorcycle">Motorcycle</option>
                                    <option value="truck">Truck</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="driver_name">Driver Name</label>
                                <input type="text" id="driver_name" name="driver_name" class="form-control">
                            </div>
                        </div>

                        <!-- Delivery Items -->
                        <hr>
                        <h5>Items to Deliver</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Ordered</th>
                                    <th>Already Delivered</th>
                                    <th>Remaining</th>
                                    <th>Deliver This Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $item)
                                @if($item->quantity > $item->delivered_quantity)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->delivered_quantity }}</td>
                                    <td>{{ $item->quantity - $item->delivered_quantity }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $item->id }}][invoice_item_id]" value="{{ $item->id }}">
                                        <input type="number" name="items[{{ $item->id }}][delivered_quantity]" 
                                               class="form-control" step="0.001" min="0" max="{{ $item->quantity - $item->delivered_quantity }}">
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Notes -->
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create Delivery</button>
                        <a href="{{ route('sales.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(document).ready(function() {
    $('#delivery-form').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serializeArray();
        
        $.ajax({
            url: '{{ route('deliveries.store') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire('Success!', response.message, 'success');
                window.location.href = '{{ route('sales.show', $invoice) }}';
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to create delivery', 'error');
            }
        });
    });
});
</script>
@endpush
