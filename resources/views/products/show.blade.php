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
                                        odede>{{ $product->code }}</code>
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
                    <small class="text-muted">
                        <strong>Example:</strong> If you sell {{ number_format($product->alternativeUnits->first()->pivot->conversion_factor ?? 100, 0) }} {{ $product->baseUnit->symbol }}, 
                        it will show as {{ number_format(($product->alternativeUnits->first()->pivot->conversion_factor ?? 100) / ($product->alternativeUnits->first()->pivot->conversion_factor ?? 1), 0) }} {{ $product->alternativeUnits->first()->symbol ?? '' }} 
                        on invoices.
                    </small>
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
@stop
