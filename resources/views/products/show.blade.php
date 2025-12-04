@extends('adminlte::page')

@section('title', 'View Product')

@section('content_header')
    <h1>Product Details</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            {{-- Basic Information Card --}}
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                    <div class="card-tools">
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">Product Name</th>
                                <td><strong>{{ $product->name }}</strong></td>
                            </tr>
                            <tr>
                                <th>Product Code</th>
                                <td>
                                    @if($product->code)
                                        de>{{ $product->code }}</code>
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Product Group</th>
                                <td>
                                    @if($product->productGroup)
                                        <span class="badge badge-info">{{ $product->productGroup->full_path }}</span>
                                    @else
                                        <span class="text-muted">No Group</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Base Unit</th>
                                <td>
                                    <span class="badge badge-primary">{{ $product->baseUnit->name }} ({{ $product->baseUnit->symbol }})</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Current Stock</th>
                                <td>
                                    @php
                                        $currentStock = $product->current_stock ?? 0;
                                        $badgeClass = 'badge-success';
                                        if ($currentStock <= $product->minimum_stock) {
                                            $badgeClass = 'badge-danger';
                                        } elseif ($currentStock <= $product->reorder_level) {
                                            $badgeClass = 'badge-warning';
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }}" style="font-size: 1.1em;">
                                        <i class="fas fa-boxes"></i> {{ number_format($currentStock, 2) }} {{ $product->baseUnit->symbol }}
                                    </span>
                                    
                                    @if($product->purchase_price)
                                        <br><small class="text-muted">
                                            Value: ৳ {{ number_format($currentStock * $product->purchase_price, 2) }}
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $product->description ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Product Movement History Card --}}
            <div class="card">
                <div class="card-header bg-dark">
                    <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Stock Movement History</h3>
                    <div class="card-tools">
                        @if($movements->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-light" data-toggle="modal" data-target="#movementsModal">
                                <i class="fas fa-list"></i> View All Movements
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($movements->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Reference</th>
                                        <th class="text-right">Quantity</th>
                                        <th class="text-right">Rate</th>
                                        <th class="text-right">Stock After</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movements->take(5) as $movement)
                                        <tr>
                                            <td>
                                                <small>{{ $movement->movement_date->format('d M Y') }}</small><br>
                                                <small class="text-muted">{{ $movement->created_at->format('h:i A') }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $badges = [
                                                        'purchase' => 'badge-success',
                                                        'sale' => 'badge-danger',
                                                        'adjustment' => 'badge-warning',
                                                        'opening_stock' => 'badge-info',
                                                        'return' => 'badge-primary',
                                                    ];
                                                    $icons = [
                                                        'purchase' => 'fa-cart-plus',
                                                        'sale' => 'fa-shopping-cart',
                                                        'adjustment' => 'fa-adjust',
                                                        'opening_stock' => 'fa-box-open',
                                                        'return' => 'fa-undo',
                                                    ];
                                                @endphp
                                                <span class="badge {{ $badges[$movement->type] ?? 'badge-secondary' }}">
                                                    <i class="fas {{ $icons[$movement->type] ?? 'fa-question' }}"></i>
                                                    {{ ucfirst(str_replace('_', ' ', $movement->type)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($movement->reference_type && $movement->reference_id)
                                                    @php
                                                        $refClass = class_basename($movement->reference_type);
                                                    @endphp
                                                    <small class="text-muted">{{ $refClass }}</small><br>
                                                    de>#{{ $movement->reference_id }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <strong class="{{ $movement->type == 'purchase' ? 'text-success' : ($movement->type == 'sale' ? 'text-danger' : '') }}">
                                                    {{ $movement->type == 'purchase' ? '+' : ($movement->type == 'sale' ? '-' : '') }}{{ number_format($movement->quantity, 2) }}
                                                </strong>
                                                <small class="text-muted">{{ $product->baseUnit->symbol }}</small>
                                            </td>
                                            <td class="text-right">
                                                @if($movement->rate)
                                                    <small>৳ {{ number_format($movement->rate, 2) }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <strong>{{ number_format($movement->stock_after, 2) }}</strong>
                                            </td>
                                            <td>
                                                <small>{{ Str::limit($movement->notes, 30) ?: '-' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No stock movements recorded yet.</p>
                        </div>
                    @endif
                </div>
                @if($movements->count() > 5)
                    <div class="card-footer text-center">
                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#movementsModal">
                            <i class="fas fa-list"></i> View All {{ $movements->count() }} Movements
                        </button>
                    </div>
                @elseif($movements->isNotEmpty())
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Total Movements: <strong>{{ $movements->count() }}</strong>
                        </small>
                    </div>
                @endif
            </div>

            {{-- Alternative Units Card --}}
            @if($product->alternativeUnits->isNotEmpty())
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title"><i class="fas fa-ruler-combined"></i> Alternative Units</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Conversion Factor</th>
                                <th>Purchase</th>
                                <th>Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->alternativeUnits as $altUnit)
                                <tr>
                                    <td><strong>{{ $altUnit->name }}</strong> ({{ $altUnit->symbol }})</td>
                                    <td>
                                        1 {{ $altUnit->symbol }} = {{ number_format($altUnit->pivot->conversion_factor, 4) }} {{ $product->baseUnit->symbol }}
                                    </td>
                                    <td class="text-center">
                                        @if($altUnit->pivot->is_purchase_unit)
                                            <i class="fas fa-check text-success"></i>
                                        @else
                                            <i class="fas fa-times text-danger"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($altUnit->pivot->is_sales_unit)
                                            <i class="fas fa-check text-success"></i>
                                        @else
                                            <i class="fas fa-times text-danger"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Pricing Card --}}
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Pricing Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">Purchase Price</th>
                                <td>
                                    @if($product->purchase_price)
                                        ৳ {{ number_format($product->purchase_price, 2) }} per {{ $product->baseUnit->symbol }}
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Selling Price</th>
                                <td>
                                    @if($product->selling_price)
                                        ৳ {{ number_format($product->selling_price, 2) }} per {{ $product->baseUnit->symbol }}
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>MRP</th>
                                <td>
                                    @if($product->mrp)
                                        ৳ {{ number_format($product->mrp, 2) }} per {{ $product->baseUnit->symbol }}
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            @if($product->selling_price && $product->purchase_price)
                            <tr>
                                <th>Profit Margin</th>
                                <td>
                                    <strong class="text-success">
                                        ৳ {{ number_format($product->selling_price - $product->purchase_price, 2) }}
                                        ({{ number_format((($product->selling_price - $product->purchase_price) / $product->purchase_price) * 100, 2) }}%)
                                    </strong>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Stock Summary Card --}}
            <div class="card card-widget widget-user-2">
                <div class="widget-user-header bg-gradient-primary">
                    <h3 class="widget-user-username">Current Stock</h3>
                    <h5 class="widget-user-desc">{{ $product->name }}</h5>
                </div>
                <div class="card-footer p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <span class="nav-link">
                                Current Quantity 
                                <span class="float-right badge bg-success">
                                    {{ number_format($product->current_stock ?? 0, 2) }} {{ $product->baseUnit->symbol }}
                                </span>
                            </span>
                        </li>
                        @if($product->purchase_price)
                        <li class="nav-item">
                            <span class="nav-link">
                                Stock Value 
                                <span class="float-right badge bg-info">
                                    ৳ {{ number_format(($product->current_stock ?? 0) * $product->purchase_price, 2) }}
                                </span>
                            </span>
                        </li>
                        @endif
                        <li class="nav-item">
                            <span class="nav-link">
                                Opening Stock 
                                <span class="float-right badge bg-secondary">
                                    {{ number_format($product->opening_quantity, 2) }} {{ $product->baseUnit->symbol }}
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Opening Stock Card --}}
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title"><i class="fas fa-boxes"></i> Opening Stock</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th>Quantity</th>
                                <td>
                                    @if($product->opening_quantity > 0)
                                        <strong>{{ number_format($product->opening_quantity, 3) }} {{ $product->baseUnit->symbol }}</strong>
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Rate</th>
                                <td>
                                    @if($product->opening_rate)
                                        ৳ {{ number_format($product->opening_rate, 2) }}
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>
                                    @if($product->opening_date)
                                        {{ $product->opening_date->format('d M Y') }}
                                    @else
                                        <span class="text-muted">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="bg-light">
                                <th>Total Value</th>
                                <td>
                                    @if($product->opening_stock_value > 0)
                                        <strong class="text-success">৳ {{ number_format($product->opening_stock_value, 2) }}</strong>
                                    @else
                                        <span class="text-muted">৳ 0.00</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    @if($product->inventoryAccount)
                        <div class="alert alert-info mb-0 mt-2">
                            <small>
                                <strong>Account:</strong><br>
                                {{ $product->inventoryAccount->code }} - {{ $product->inventoryAccount->name }}
                            </small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Stock Levels Card --}}
            <div class="card">
                <div class="card-header bg-secondary">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Stock Levels</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th>Minimum Stock</th>
                                <td>{{ number_format($product->minimum_stock, 3) }} {{ $product->baseUnit->symbol }}</td>
                            </tr>
                            <tr>
                                <th>Reorder Level</th>
                                <td>{{ number_format($product->reorder_level, 3) }} {{ $product->baseUnit->symbol }}</td>
                            </tr>
                            <tr>
                                <th>Maximum Stock</th>
                                <td>{{ number_format($product->maximum_stock, 3) }} {{ $product->baseUnit->symbol }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- System Info Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock"></i> System Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th>Created</th>
                                <td>{{ $product->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Last Updated</th>
                                <td>{{ $product->updated_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Movement Modal with DataTables --}}
    <div class="modal fade" id="movementsModal" tabindex="-1" role="dialog" aria-labelledby="movementsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title" id="movementsModalLabel">
                        <i class="fas fa-exchange-alt"></i> All Stock Movements - {{ $product->name }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="movements-table" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Quantity</th>
                                <th>Rate</th>
                                <th>Stock Before</th>
                                <th>Stock After</th>
                                <th>Notes</th>
                                <th>By</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize DataTable when modal opens
    $('#movementsModal').on('shown.bs.modal', function () {
        if (!$.fn.DataTable.isDataTable('#movements-table')) {
            $('#movements-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('products.movements.datatable', $product->id) }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '40px' },
                    { data: 'date_formatted', name: 'movement_date', width: '100px' },
                    { data: 'type_badge', name: 'type', width: '120px' },
                    { data: 'reference', name: 'reference_type', orderable: false, width: '100px' },
                    { data: 'quantity_formatted', name: 'quantity', width: '100px' },
                    { data: 'rate_formatted', name: 'rate', width: '80px' },
                    { data: 'stock_before_formatted', name: 'stock_before', width: '80px' },
                    { data: 'stock_after_formatted', name: 'stock_after', width: '80px' },
                    { data: 'notes', name: 'notes' },
                    { data: 'created_by_name', name: 'createdBy.name', width: '100px' },
                ],
                order: [[1, 'desc']], // Order by date descending
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    processing: '<i class="fas fa-spinner fa-spin fa-3x"></i><br>Loading movements...',
                    emptyTable: "No movements found",
                    zeroRecords: "No matching movements found"
                },
                responsive: true,
                autoWidth: false
            });
        }
    });
    
    // Destroy DataTable when modal closes to allow re-initialization
    $('#movementsModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#movements-table')) {
            $('#movements-table').DataTable().destroy();
        }
    });
});
</script>
@stop

@section('css')
<style>
    .table-sm th {
        width: 40%;
    }
    .modal-xl {
        max-width: 95%;
    }
</style>
@stop
