@extends('adminlte::page')

@section('title', 'Units')

@section('content_header')
    <h1>Units Management</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Units</h3>
            <div class="card-tools">
                <a href="{{ route('units.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add New Unit
                </a>
            </div>
        </div>
        <div class="card-body">
            <table id="units-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Symbol</th>
                        <th>Type</th>
                        <th>Base Unit</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@section('css')
    {{-- Add any extra stylesheets here --}}
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#units-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('units.index') }}",
            columns: [
                {data: 'id', name: 'id'},
                {data: 'name', name: 'name'},
                {data: 'symbol', name: 'symbol'},
                {data: 'type', name: 'type'},
                {data: 'is_base_unit', name: 'is_base_unit', orderable: false, searchable: false},
                {data: 'is_active', name: 'is_active', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Delete Unit with SweetAlert
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
                        url: "{{ url('units') }}/" + id,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON.message,
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@stop
