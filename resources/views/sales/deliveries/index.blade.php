@extends('adminlte::page')

@section('title', 'Delivery Chalan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Delivery Chalan</h3>
                </div>
                <div class="card-body">
                    <table id="deliveries-table" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Challan #</th>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Delivery Date</th>
                                <th>Amount</th>
                                <th>Delivered By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#deliveries-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('deliveries.index') }}',
        columns: [
            { data: 'challan_number', name: 'challan_number' },
            { data: 'invoice_number', name: 'invoice.invoice_number' },
            { data: 'customer_name', name: 'invoice.customer.name' },
            { data: 'delivery_date', name: 'delivery_date' },
            { data: 'total_amount', name: 'total_amount', searchable: false },
            { data: 'delivered_by_name', name: 'deliveredBy.name' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Delete delivery
    $(document).on('click', '.delete-delivery-btn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Delivery?',
            text: 'Stock will be restored.',
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
                        table.draw();
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
