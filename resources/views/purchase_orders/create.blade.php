@extends('adminlte::page')

@section('title', 'Add Purchase Order')

@section('content_header')
    <h1>Add Purchase Order</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Main Form Section -->
        <div class="col-lg-9 col-md-8 col-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Purchase Order Details</h3>
                </div>

                <div class="card-body">
                    <form id="purchase-form" csrf>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="vendor_id">Vendor <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="vendor_id" id="vendor_id" required style="width: 100%;">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" data-ledger-id="{{ $vendor->ledger_account_id }}">
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="order_date">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="order_date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_account_id">Purchase Account <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="purchase_account_id" id="purchase_account_id" required style="width: 100%;">
                                        <option value="">Select Purchase Account</option>
                                        @foreach($purchaseAccounts as $account)
                                            <option value="{{ $account->id }}" data-code="{{ $account->code }}"
                                                @if($account->id == ($defaultPurchaseAccount->id ?? null)) selected @endif>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Products Table with Alternative Units -->
                        <div class="form-group">
                            <label>Products <span class="text-danger">*</span></label>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="po-items">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="25%">Product</th>
                                            <th width="12%">Unit <i class="fas fa-info-circle" title="Select unit for quantity entry"></i></th>
                                            <th width="12%">Qty</th>
                                            <th width="10%">Base Qty</th>
                                            <th width="15%">Rate (per base unit)</th>
                                            <th width="15%">Amount</th>
                                            <th width="5%">
                                                <button type="button" class="btn btn-success btn-xs add-row" title="Add Row">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control form-control-sm select2 product-select" name="items[0][product_id]" required style="width: 100%;">
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" 
                                                            data-unit="{{ $product->baseUnit->symbol }}"
                                                            data-rate="{{ $product->purchase_price ?? 0 }}"
                                                            data-base-unit-id="{{ $product->base_unit_id }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <!-- Unit selector - will be populated based on product selection -->
                                                <select class="form-control form-control-sm select2 unit-select" name="items[0][unit_id]" required style="width: 100%;">
                                                    <option value="">--</option>
                                                </select>
                                                <small class="form-text text-muted base-unit-symbol"></small>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm qty" 
                                                    name="items[0][quantity]" min="0.001" step="0.001" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm base-qty" 
                                                    name="items[0][base_quantity]" readonly style="background-color: #f0f0f0;">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm rate" 
                                                    name="items[0][rate]" min="0.01" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm amount" readonly style="background-color: #f0f0f0;">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-xs remove-row" title="Remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="4" class="text-right"><strong>Total</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm" id="grand-total" readonly style="background-color: #f0f0f0;">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Purchase Order
                            </button>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Vendor Info Sidebar -->
        <div class="col-lg-3 col-md-4 col-12">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">Vendor Info</h3>
                </div>

                <div class="card-body">
                    <div id="vendor-info" class="text-center text-muted" style="display: none;">
                        <p class="mb-2"><strong id="vendor-name-display"></strong></p>
                        <hr>
                        <div class="info-box mb-0">
                            <span class="info-box-icon bg-warning"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Balance Due</span>
                                <span class="info-box-number" id="vendor-balance">0.00</span>
                            </div>
                        </div>
                    </div>
                    <p id="no-vendor-selected" class="text-muted text-center">
                        <i class="fas fa-info-circle"></i><br>
                        Select a vendor to view details
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
<style>
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(2.25rem - 0.75rem) !important;
        padding-left: 0;
    }

    .po-items tbody tr td {
        vertical-align: middle !important;
    }

    .po-items .form-control-sm {
        height: calc(1.8rem + 2px);
        font-size: 0.875rem;
    }

    .table-sm td, .table-sm th {
        padding: 0.3rem;
    }

    .amount, .grand-total {
        background-color: #f8f9fa !important;
        font-weight: 600;
    }

    #grand-total {
        font-size: 1.1rem;
        font-weight: bold;
    }
</style>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const productsData = {!! json_encode($products->mapWithKeys(function($p) {
        return [$p->id => [
            'id' => $p->id,
            'name' => $p->name,
            'base_unit_id' => $p->base_unit_id,
            'base_unit_symbol' => $p->baseUnit->symbol,
            'purchase_price' => $p->purchase_price ?? 0,
            'alternative_units' => $p->alternativeUnits->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'symbol' => $u->symbol,
                    'conversion_factor' => $u->pivot->conversion_factor,
                    'is_purchase_unit' => (bool) $u->pivot->is_purchase_unit,
                ];
            })->toArray(),
        ]];
    })) !!};

    // Initialize Select2
    function initSelect2(element) {
        if (element.hasClass('select2-hidden-accessible')) {
            element.select2('destroy');
        }
        element.select2({
            width: '100%',
            theme: 'bootstrap4',
            placeholder: 'Select an option',
            allowClear: true,
            closeOnSelect: true,
            dropdownAutoWidth: false
        });

        element.on('select2:open', function() {
            setTimeout(function() {
                document.querySelector('.select2-search__field').focus();
            }, 50);
        });
    }

    // Initialize all select2 on page load
    initSelect2($('#vendor_id'));
    initSelect2($('#purchase_account_id'));
    $('.product-select').each(function() {
        initSelect2($(this));
    });

    // Vendor Selection - Show Balance
    $('#vendor_id').on('select2:select', function(e) {
        const vendorId = $(this).val();
        const vendorName = $(this).find('option:selected').text();

        if (vendorId) {
            $('#no-vendor-selected').hide();
            $('#vendor-info').show();
            $('#vendor-name-display').text(vendorName);

            // Fetch vendor balance via AJAX
            $.ajax({
                url: `/vendors/${vendorId}/balance`,
                method: 'GET',
                success: function(response) {
                    const balance = parseFloat(response.balance || 0);
                    const formattedBalance = Math.abs(balance).toFixed(2);

                    if (balance > 0) {
                        $('#vendor-balance').html(formattedBalance + '<small class="text-danger"> You owe</small>');
                        $('#vendor-info .info-box-icon').removeClass('bg-success bg-danger').addClass('bg-danger');
                    } else if (balance < 0) {
                        $('#vendor-balance').html(formattedBalance + '<small class="text-success"> Advance</small>');
                        $('#vendor-info .info-box-icon').removeClass('bg-success bg-danger').addClass('bg-success');
                    } else {
                        $('#vendor-balance').text(formattedBalance);
                        $('#vendor-info .info-box-icon').removeClass('bg-success bg-danger').addClass('bg-warning');
                    }
                },
                error: function() {
                    $('#vendor-balance').text('0.00');
                }
            });
        } else {
            $('#vendor-info').hide();
            $('#no-vendor-selected').show();
        }
    });

    // Product Selection - Populate Units and Auto-fill Rate
    $(document).on('select2:select', '.product-select', function(e) {
        const row = $(this).closest('tr');
        const productId = $(this).val();
        const product = productsData[productId];

        if (!product) return;

        // Populate unit selector with base unit and alternative units
        const unitSelect = row.find('.unit-select');
        unitSelect.html('');

        // Add base unit first
        unitSelect.append(`<option value="${product.base_unit_id}" selected>${product.base_unit_symbol}</option>`);

        // Add alternative units that are purchase units
        product.alternative_units.forEach(unit => {
            if (unit.is_purchase_unit) {
                unitSelect.append(`<option value="${unit.id}">${unit.symbol} (1 = ${unit.conversion_factor} ${product.base_unit_symbol})</option>`);
            }
        });

        // Initialize select2 on new unit selector
        if (unitSelect.hasClass('select2-hidden-accessible')) {
            unitSelect.select2('destroy');
        }
        unitSelect.select2({
            width: '100%',
            theme: 'bootstrap4',
        });

        // Set base unit symbol
        row.find('.base-unit-symbol').text(`Base: ${product.base_unit_symbol}`);

        // Set rate
        row.find('.rate').val(parseFloat(product.purchase_price).toFixed(2));

        // Focus on quantity
        setTimeout(() => row.find('.qty').focus(), 100);
    });

    // Unit Change - Recalculate base quantity
    $(document).on('change', '.unit-select', function() {
        const row = $(this).closest('tr');
        calculateBaseQuantity(row);
        calculateRowAmount(row);
    });

    // Quantity or Rate Change - Recalculate Amount
    $(document).on('input', '.qty, .rate', function() {
        const row = $(this).closest('tr');
        calculateBaseQuantity(row);
        calculateRowAmount(row);
    });

    // Calculate Base Quantity from Alternative Unit
    function calculateBaseQuantity(row) {
        const qtyInput = row.find('.qty');
        const baseQtyInput = row.find('.base-qty');
        const unitSelect = row.find('.unit-select');
        const productSelect = row.find('.product-select');

        const productId = productSelect.val();
        const quantity = parseFloat(qtyInput.val()) || 0;
        const unitId = unitSelect.val();

        if (!productId || !unitId) {
            baseQtyInput.val('');
            return;
        }

        const product = productsData[productId];

        // If selected unit is base unit
        if (unitId == product.base_unit_id) {
            baseQtyInput.val(quantity.toFixed(4));
        } else {
            // Find conversion factor
            const altUnit = product.alternative_units.find(u => u.id == unitId);
            if (altUnit) {
                const baseQuantity = quantity * altUnit.conversion_factor;
                baseQtyInput.val(baseQuantity.toFixed(4));
            }
        }
    }

    // Calculate Amount per Row
    function calculateRowAmount(row) {
        const baseQty = parseFloat(row.find('.base-qty').val()) || 0;
        const rate = parseFloat(row.find('.rate').val()) || 0;
        const amount = baseQty * rate;

        row.find('.amount').val(amount > 0 ? amount.toFixed(2) : '');
        calculateGrandTotal();
    }

    // Calculate Grand Total
    function calculateGrandTotal() {
        let total = 0;
        $('#po-items tbody tr').each(function() {
            const amount = parseFloat($(this).find('.amount').val()) || 0;
            total += amount;
        });
        $('#grand-total').val(total.toFixed(2));
    }

    // Add New Row
    $(document).on('click', '.add-row', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const firstRow = $('#po-items tbody tr').first();
        const newRow = firstRow.clone();

        // Clear values
        newRow.find('input, textarea').val('');
        newRow.find('.base-unit-symbol').text('');

        // Remove Select2 from cloned row
        newRow.find('.select2-container').remove();
        const selects = newRow.find('select');
        selects.each(function() {
            $(this).removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id')
                .removeAttr('aria-hidden')
                .removeAttr('tabindex')
                .show();
            $(this).find('option').first().prop('selected', true);
        });

        // Update names with new index
        newRow.find('[name]').each(function() {
            const name = $(this).attr('name');
            if (name.includes('items')) {
                $(this).attr('name', name.replace(/items\[0\]/, `items[${rowIndex}]`));
            }
        });

        $('#po-items tbody').append(newRow);
        
        // Initialize select2 on new row
        newRow.find('.product-select').each(function() {
            initSelect2($(this));
        });

        rowIndex++;
    });

    // Remove Row
    $(document).on('click', '.remove-row', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if ($('#po-items tbody tr').length === 1) {
            $(this).closest('tr').remove();
            calculateGrandTotal();
        } else {
            Swal.fire({
                title: 'Warning',
                text: 'At least one product row is required.',
                icon: 'warning'
            });
        }
    });

    // Form Submission
    $('#purchase-form').on('submit', function(e) {
        e.preventDefault();

        // Validate at least one product
        if ($('#po-items tbody tr').length === 0) {
            Swal.fire({
                title: 'Warning',
                text: 'Please add at least one product.',
                icon: 'warning'
            });
            return false;
        }

        // Validate vendor is selected
        if (!$('#vendor_id').val()) {
            Swal.fire({
                title: 'Warning',
                text: 'Please select a vendor.',
                icon: 'warning'
            });
            return false;
        }

        // Validate purchase account is selected
        if (!$('#purchase_account_id').val()) {
            Swal.fire({
                title: 'Warning',
                text: 'Please select a purchase account.',
                icon: 'warning'
            });
            return false;
        }

        // Prepare data
        const formData = new FormData(this);
        
        // Remove base_quantity fields (calculated, not submitted)
        $('#po-items tbody tr').each(function(index) {
            formData.delete(`items[${index}][base_quantity]`);
        });

        // Show loading
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we save your purchase order.',
            allowOutsideClick: false,
            didOpen() {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("purchase-orders.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Purchase order created successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = '{{ route("purchase-orders.index") }}';
                    });
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to save purchase order';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });

    // Initialize calculations on page load
    calculateGrandTotal();
});
</script>
@endpush
