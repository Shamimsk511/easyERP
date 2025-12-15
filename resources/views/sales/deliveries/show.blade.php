@extends('adminlte::page')
@section('title', 'Delivery - ' . $delivery->challan_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Delivery Challan {{ $delivery->challan_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('deliveries.print', $delivery) }}" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <button type="button" class="btn btn-danger btn-sm delete-delivery" data-id="{{ $delivery->id }}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Delivery Details</h5>
                            <p><strong>Challan #:</strong> {{ $delivery->challan_number }}</p>
                            <p><strong>Delivery Date:</strong> {{ $delivery->delivery_date->format('d M Y') }}</p>
                            <p><strong>Invoice #:</strong> {{ $delivery->invoice->invoice_number }}</p>
                            <p><strong>Delivery Method:</strong> {{ ucfirst($delivery->delivery_method) }}</p>
                            <p><strong>Driver:</strong> {{ $delivery->driver_name ?? 'N/A' }}</p>
                            <p><strong>Delivered By:</strong> {{ $delivery->deliveredBy->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Customer</h5>
                            <p><strong>{{ $delivery->invoice->customer->name }}</strong></p>
                            <p>{{ $delivery->invoice->customer->phone }}</p>
                            <p>{{ $delivery->invoice->customer->address }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Delivered Items</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Delivered Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($delivery->items as $item)
                            <tr>
                                <td>{{ $item->invoiceItem->description }}</td>
                                <td>{{ $item->invoiceItem->unit->symbol ?? 'Unit' }}</td>
                                <td>{{ $item->delivered_quantity }}</td>
                                <td>৳ {{ number_format($item->invoiceItem->unit_price, 2) }}</td>
                                <td>৳ {{ number_format($item->delivered_quantity * $item->invoiceItem->unit_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($delivery->notes)
                    <hr>
                    <h5>Notes</h5>
                    <p>{{ $delivery->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-delivery').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Delivery?',
            text: 'Stock will be restored to the inventory.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/deliveries/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        Swal.fire('Deleted!', response.message, 'success');
                        window.location.href = '{{ route('deliveries.index') }}';
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
