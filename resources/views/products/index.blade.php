@extends('adminlte::page')

@section('title', 'Products')

@section('content_header')
    <h1>Products</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Products</h3>
            <div class="card-tools">
                <a href="{{ route('products.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
        </div>
        <div class="card-body">
            {{-- Filter Section --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filter_group">Filter by Product Group</label>
                        <select id="filter_group" class="form-control select2" style="width: 100%;">
                            <option value="">-- All Groups --</option>
                            @foreach($productGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->full_path }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filter_status">Filter by Status</label>
                        <select id="filter_status" class="form-control">
                            <option value="">-- All Status --</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" id="reset_filters" class="btn btn-secondary btn-block">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <table id="products-table" class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Group</th>
                        <th>Unit</th>
                        <th>Current Stock</th>
                        <th>Rate</th>
                        <th>Stock Value</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: '-- All Groups --',
            allowClear: true
        });

        // Initialize DataTable
        var table = $('#products-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('products.index') }}",
                data: function (d) {
                    d.product_group_id = $('#filter_group').val();
                    d.status = $('#filter_status').val();
                }
            },
            columns: [
                {data: 'id', name: 'id', width: '50px'},
                {data: 'name', name: 'name'},
                {data: 'code', name: 'code'},
                {data: 'group_name', name: 'productGroup.name'},
                {data: 'unit_name', name: 'baseUnit.symbol', width: '80px'},
                {data: 'current_stock', name: 'opening_quantity', orderable: false},
                {data: 'rate_info', name: 'purchase_price'},
                {data: 'stock_value', name: 'stock_value', orderable: false, searchable: false},
                {data: 'is_active', name: 'is_active', orderable: false, searchable: false, width: '80px'},
                {data: 'action', name: 'action', orderable: false, searchable: false, width: '120px'}
            ],
            order: [[0, 'desc']]
        });

        // Reload table when filter changes
        $('#filter_group, #filter_status').change(function() {
            table.draw();
        });

        // Reset filters
        $('#reset_filters').click(function() {
            $('#filter_group').val('').trigger('change');
            $('#filter_status').val('');
            table.draw();
        });

        // Delete Product with SweetAlert
        $(document).on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('products') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
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
@stop
