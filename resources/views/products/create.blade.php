@extends('adminlte::page')

@section('title', 'Create Product')

@section('content_header')
    <h1>Create New Product</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Product Information</h3>
        </div>
        
        <form action="{{ route('products.store') }}" method="POST" id="productForm">
            @csrf
            
            <div class="card-body">
                {{-- Basic Information --}}
                <h5 class="text-primary"><i class="fas fa-info-circle"></i> Basic Information</h5>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Product Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   placeholder="Enter unique product name"
                                   value="{{ old('name') }}"
                                   required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Product Code</label>
                            <input type="text" 
                                   class="form-control @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   placeholder="Optional SKU/Code"
                                   value="{{ old('code') }}">
                            @error('code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="product_group_id">Product Group</label>
                            <div class="input-group">
                                <select class="form-control select2 @error('product_group_id') is-invalid @enderror" 
                                        id="product_group_id" 
                                        name="product_group_id"
                                        style="width: calc(100% - 40px);">
                                    <option value="">-- No Group --</option>
                                    @foreach($productGroups as $group)
                                        <option value="{{ $group->id }}" {{ old('product_group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->full_path }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#groupModal" title="Add New Group">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            @error('product_group_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="base_unit_id">Base Unit <span class="text-danger">*</span></label>
                            <select class="form-control @error('base_unit_id') is-invalid @enderror" 
                                    id="base_unit_id" 
                                    name="base_unit_id"
                                    required>
                                <option value="">-- Select Base Unit --</option>
                                @foreach($baseUnits as $unit)
                                    <option value="{{ $unit->id }}" {{ old('base_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('base_unit_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">This is the smallest measurement unit</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description" 
                              rows="2">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Alternative Units Section --}}
                <h5 class="text-primary mt-4"><i class="fas fa-ruler-combined"></i> Alternative Units (Compound Units)</h5>
                <hr>
                <div id="alternative-units-section">
                    <table class="table table-bordered" id="alt-units-table">
                        <thead>
                            <tr>
                                <th width="40%">Alternative Unit</th>
                                <th width="25%">Conversion Factor</th>
                                <th width="15%">Purchase</th>
                                <th width="15%">Sales</th>
                                <th width="5%">
                                    <button type="button" class="btn btn-sm btn-success" id="add-alt-unit">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="alt-units-body">
                            {{-- Dynamic rows will be added here --}}
                        </tbody>
                    </table>
                    <small class="form-text text-muted">
                        Example: If base unit is "pieces" and 1 box = 25 pieces, select "box" and enter 25 as conversion factor
                    </small>
                </div>

                {{-- Opening Stock Section --}}
                <h5 class="text-primary mt-4"><i class="fas fa-boxes"></i> Opening Stock</h5>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="opening_quantity">Opening Quantity</label>
                            <input type="number" 
                                   class="form-control @error('opening_quantity') is-invalid @enderror" 
                                   id="opening_quantity" 
                                   name="opening_quantity" 
                                   step="0.001"
                                   min="0"
                                   value="{{ old('opening_quantity', 0) }}">
                            @error('opening_quantity')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="opening_rate">Opening Rate (per base unit)</label>
                            <input type="number" 
                                   class="form-control @error('opening_rate') is-invalid @enderror" 
                                   id="opening_rate" 
                                   name="opening_rate" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('opening_rate') }}">
                            @error('opening_rate')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="opening_date">Opening Date</label>
                            <input type="date" 
                                   class="form-control @error('opening_date') is-invalid @enderror" 
                                   id="opening_date" 
                                   name="opening_date" 
                                   value="{{ old('opening_date', now()->toDateString()) }}">
                            @error('opening_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="inventory_account_id">Inventory Account</label>
                    <select class="form-control select2 @error('inventory_account_id') is-invalid @enderror" 
                            id="inventory_account_id" 
                            name="inventory_account_id">
                        <option value="">-- Select Account --</option>
                        @foreach($inventoryAccounts as $account)
                            <option value="{{ $account->id }}" {{ old('inventory_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->code }} - {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('inventory_account_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted">Required if opening stock is entered</small>
                </div>

                {{-- Pricing Section --}}
                <h5 class="text-primary mt-4"><i class="fas fa-money-bill-wave"></i> Pricing</h5>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="purchase_price">Purchase Price</label>
                            <input type="number" 
                                   class="form-control @error('purchase_price') is-invalid @enderror" 
                                   id="purchase_price" 
                                   name="purchase_price" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('purchase_price') }}">
                            @error('purchase_price')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="selling_price">Selling Price</label>
                            <input type="number" 
                                   class="form-control @error('selling_price') is-invalid @enderror" 
                                   id="selling_price" 
                                   name="selling_price" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('selling_price') }}">
                            @error('selling_price')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mrp">MRP</label>
                            <input type="number" 
                                   class="form-control @error('mrp') is-invalid @enderror" 
                                   id="mrp" 
                                   name="mrp" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('mrp') }}">
                            @error('mrp')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Stock Levels Section --}}
                <h5 class="text-primary mt-4"><i class="fas fa-chart-line"></i> Stock Levels</h5>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="minimum_stock">Minimum Stock</label>
                            <input type="number" 
                                   class="form-control @error('minimum_stock') is-invalid @enderror" 
                                   id="minimum_stock" 
                                   name="minimum_stock" 
                                   step="0.001"
                                   min="0"
                                   value="{{ old('minimum_stock', 0) }}">
                            @error('minimum_stock')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reorder_level">Reorder Level</label>
                            <input type="number" 
                                   class="form-control @error('reorder_level') is-invalid @enderror" 
                                   id="reorder_level" 
                                   name="reorder_level" 
                                   step="0.001"
                                   min="0"
                                   value="{{ old('reorder_level', 0) }}">
                            @error('reorder_level')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="maximum_stock">Maximum Stock</label>
                            <input type="number" 
                                   class="form-control @error('maximum_stock') is-invalid @enderror" 
                                   id="maximum_stock" 
                                   name="maximum_stock" 
                                   step="0.001"
                                   min="0"
                                   value="{{ old('maximum_stock', 0) }}">
                            @error('maximum_stock')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Product
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    {{-- Modal for Quick Group Creation --}}
    <div class="modal fade" id="groupModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title">Add New Product Group</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="groupForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="modal_group_name">Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_group_name" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_parent_id">Parent Group</label>
                            <select class="form-control" id="modal_parent_id">
                                <option value="">-- No Parent --</option>
                                @foreach($productGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->full_path }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="modal_is_active" checked>
                            <label class="form-check-label" for="modal_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save Group</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        allowClear: true
    });

    // Alternative Units Management
    let altUnitIndex = 0;
    
    $('#add-alt-unit').click(function() {
        addAltUnitRow();
    });

    function addAltUnitRow() {
        const row = `
            <tr class="alt-unit-row">
                <td>
                    <select class="form-control" name="alt_unit_id[]">
                        <option value="">-- Select Unit --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control" name="conversion_factor[]" 
                           step="0.0001" min="0.0001" placeholder="e.g., 25">
                </td>
                <td class="text-center">
                    <input type="checkbox" name="is_purchase_unit[]" value="1">
                </td>
                <td class="text-center">
                    <input type="checkbox" name="is_sales_unit[]" value="1">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-alt-unit">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#alt-units-body').append(row);
        altUnitIndex++;
    }

    // Remove alternative unit row
    $(document).on('click', '.remove-alt-unit', function() {
        $(this).closest('tr').remove();
    });

    // Quick Group Creation via Modal
    $('#groupForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#modal_group_name').val(),
            parent_id: $('#modal_parent_id').val() || null,
            is_active: $('#modal_is_active').is(':checked') ? 1 : 0,
            _token: "{{ csrf_token() }}"
        };

        $.ajax({
            url: "{{ route('product-groups.store') }}",
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Add new group to select dropdown
                    const newOption = new Option(response.data.name, response.data.id, true, true);
                    $('#product_group_id').append(newOption).trigger('change');
                    
                    // Close modal and reset form
                    $('#groupModal').modal('hide');
                    $('#groupForm')[0].reset();
                    
                    // Show success message
                    Swal.fire('Success!', response.message, 'success');
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON.message || 'Failed to create group', 'error');
            }
        });
    });

    // Reset modal form when closed
    $('#groupModal').on('hidden.bs.modal', function() {
        $('#groupForm')[0].reset();
    });
});
</script>
@stop
