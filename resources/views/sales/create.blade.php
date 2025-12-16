@extends('adminlte::page')

@section('title', 'Create Invoice')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-invoice"></i> Create Invoice</h1>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Invoice Form -->
        <div class="col-md-8">
            <form id="invoice-form">
                @csrf
                
                <!-- Header Section -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user"></i> Customer & Date</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select id="customer_id" name="customer_id" class="form-control select2-customer" style="width: 100%;" required>
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" 
                                                    data-phone="{{ $customer->phone }}"
                                                    data-address="{{ $customer->address }}">
                                                {{ $customer->name }} {{ $customer->phone ? '(' . $customer->phone . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger error-msg" id="error-customer_id"></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" id="invoice_date" name="invoice_date" 
                                           class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Tally-like Account Selection -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sales_account_id">
                                        <i class="fas fa-book text-primary"></i> Sales Account
                                    </label>
                                    <select id="sales_account_id" name="sales_account_id" class="form-control select2-account" style="width: 100%;">
                                        <option value="">-- Default (Sales) --</option>
                                        @foreach($salesAccounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="labour_account_id">
                                        <i class="fas fa-hard-hat text-warning"></i> Labour Account
                                    </label>
                                    <select id="labour_account_id" name="labour_account_id" class="form-control select2-account" style="width: 100%;">
                                        <option value="">-- Select Account --</option>
                                        @foreach($labourAccounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transportation_account_id">
                                        <i class="fas fa-truck text-info"></i> Transportation Account
                                    </label>
                                    <select id="transportation_account_id" name="transportation_account_id" class="form-control select2-account" style="width: 100%;">
                                        <option value="">-- Select Account --</option>
                                        @foreach($transportationAccounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items Section -->
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-boxes"></i> Product Items</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm" id="add-item-btn">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" id="items-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 25%;">Product</th>
                                        <th style="width: 10%;">Qty</th>
                                        <th style="width: 12%;">Alt Qty</th>
                                        <th style="width: 10%;">Unit</th>
                                        <th style="width: 12%;">Price</th>
                                        <th style="width: 8%;">Disc %</th>
                                        <th style="width: 13%;">Total</th>
                                        <th style="width: 5%;">Stock</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="items-body">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Totals & Additional Charges Section -->
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calculator"></i> Summary & Additional Charges</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Left: Additional Charges -->
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Additional Charges (Tally Style)</h6>
                                
                                <div class="form-group row">
                                    <label class="col-sm-5 col-form-label">
                                        <i class="fas fa-hard-hat text-warning"></i> Labour Charges
                                    </label>
                                    <div class="col-sm-7">
                                        <input type="number" id="labour_amount" name="labour_amount" 
                                               class="form-control form-control-sm text-right calc-trigger" 
                                               value="0" step="0.01" min="0">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-5 col-form-label">
                                        <i class="fas fa-truck text-info"></i> Transportation
                                    </label>
                                    <div class="col-sm-7">
                                        <input type="number" id="transportation_amount" name="transportation_amount" 
                                               class="form-control form-control-sm text-right calc-trigger" 
                                               value="0" step="0.01" min="0">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-5 col-form-label">
                                        <i class="fas fa-percentage text-secondary"></i> Discount
                                    </label>
                                    <div class="col-sm-7">
                                        <input type="number" id="discount_amount" name="discount_amount" 
                                               class="form-control form-control-sm text-right calc-trigger" 
                                               value="0" step="0.01" min="0">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-5 col-form-label">
                                        <i class="fas fa-balance-scale text-muted"></i> Round Off
                                    </label>
                                    <div class="col-sm-7">
                                        <input type="number" id="round_off_amount" name="round_off_amount" 
                                               class="form-control form-control-sm text-right calc-trigger" 
                                               value="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Totals Display -->
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-right">Subtotal:</td>
                                            <td class="text-right font-weight-bold" style="width: 120px;">
                                                <span id="display-subtotal">0.00</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">Labour:</td>
                                            <td class="text-right"><span id="display-labour">0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">Transportation:</td>
                                            <td class="text-right"><span id="display-transportation">0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right text-danger">Discount:</td>
                                            <td class="text-right text-danger">-<span id="display-discount">0.00</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">Round Off:</td>
                                            <td class="text-right"><span id="display-roundoff">0.00</span></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="text-right"><strong>Grand Total:</strong></td>
                                            <td class="text-right">
                                                <h4 class="text-primary mb-0"><span id="display-total">0.00</span></h4>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_notes">Customer Notes (prints on invoice)</label>
                                    <textarea id="customer_notes" name="customer_notes" 
                                              class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="internal_notes">Internal Notes (not printed)</label>
                                    <textarea id="internal_notes" name="internal_notes" 
                                              class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                            <i class="fas fa-save"></i> Create Invoice
                        </button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Customer Info Sidebar -->
        <div class="col-md-4">
            <div class="card card-primary card-outline sticky-top" style="top: 70px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-circle"></i> Customer Profile</h3>
                </div>
                <div class="card-body" id="customer-profile">
                    <p class="text-muted text-center">
                        <i class="fas fa-hand-pointer fa-2x mb-2 d-block"></i>
                        Select a customer to view profile
                    </p>
                </div>
            </div>

            <!-- Quick Product Info -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-box"></i> Product Info</h3>
                </div>
                <div class="card-body" id="product-info">
                    <p class="text-muted text-center">
                        <i class="fas fa-boxes fa-2x mb-2 d-block"></i>
                        Select a product to view stock
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Row Template -->
<template id="item-row-template">
    <tr class="item-row" data-index="__INDEX__">
        <td>
            <select name="items[__INDEX__][product_id]" class="form-control form-control-sm product-select" style="width: 100%;">
                <option value="">-- Select --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" 
                            data-name="{{ $product->name }}"
                            data-code="{{ $product->code }}"
                            data-price="{{ $product->selling_price }}"
                            data-base-unit="{{ $product->base_unit_id }}"
                            data-base-symbol="{{ $product->baseUnit?->symbol }}">
                        {{ $product->name }} {{ $product->code ? '(' . $product->code . ')' : '' }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="items[__INDEX__][description]" class="item-description" value="">
        </td>
        <td>
            <input type="number" name="items[__INDEX__][quantity]" 
                   class="form-control form-control-sm qty-input text-right" 
                   step="0.001" min="0.001" value="" placeholder="Qty">
        </td>
        <td>
            <span class="alt-qty-display badge badge-info">-</span>
        </td>
        <td>
            <select name="items[__INDEX__][unit_id]" class="form-control form-control-sm unit-select">
                <option value="">-</option>
            </select>
        </td>
        <td>
            <input type="number" name="items[__INDEX__][unit_price]" 
                   class="form-control form-control-sm price-input text-right" 
                   step="0.01" min="0" value="" placeholder="Price">
        </td>
        <td>
            <input type="number" name="items[__INDEX__][discount_percent]" 
                   class="form-control form-control-sm discount-input text-right" 
                   step="0.01" min="0" max="100" value="0">
        </td>
        <td>
            <span class="line-total font-weight-bold">0.00</span>
        </td>
        <td>
            <span class="stock-display badge badge-secondary">-</span>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-xs remove-row-btn">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>
@stop

@section('css')
<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da !important;
        height: 38px !important;
        padding: 5px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
    }
    .select2-dropdown {
        border: 1px solid #80bdff !important;
    }
    .form-control-sm.select2-container--default .select2-selection--single {
        height: 31px !important;
        padding: 2px !important;
    }
    .item-row .select2-container {
        min-width: 100% !important;
    }
    .alt-qty-display {
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .stock-display {
        font-size: 0.75rem;
    }
    .stock-low {
        background-color: #dc3545 !important;
    }
    .stock-ok {
        background-color: #28a745 !important;
    }
    #items-table th, #items-table td {
        vertical-align: middle;
        padding: 0.4rem;
    }
    .line-total {
        font-size: 0.95rem;
    }
</style>
@stop

@section('js')
/**
 * Invoice Create Module
 * Features:
 * - Customer Select2 with phone search
 * - Product Select2 with code/name search
 * - Real-time alternative quantity display (box + pcs)
 * - Tally-like account selection
 * - Auto-calculation of totals
 */
<script>
$(document).ready(function() {
    let itemIndex = 0;
    const productsCache = {};

    // Initialize Select2 for Customer
    initCustomerSelect2();
    
    // Initialize Select2 for Account dropdowns
    initAccountSelect2();

    // Add first item row
    addItemRow();

    // ==========================================
    // Customer Select2 with Phone Search
    // ==========================================
    function initCustomerSelect2() {
        $('.select2-customer').select2({
            placeholder: '-- Select Customer (Name/Phone) --',
            allowClear: true,
            width: '100%',
            matcher: function(params, data) {
                if ($.trim(params.term) === '') return data;
                
                const term = params.term.toLowerCase();
                const text = (data.text || '').toLowerCase();
                const phone = ($(data.element).data('phone') || '').toLowerCase();
                
                if (text.indexOf(term) > -1 || phone.indexOf(term) > -1) {
                    return data;
                }
                return null;
            }
        }).on('select2:open', function() {
            setTimeout(() => $('.select2-search__field').focus(), 100);
        });
    }

    // ==========================================
    // Account Select2
    // ==========================================
    function initAccountSelect2() {
        $('.select2-account').select2({
            placeholder: '-- Select Account --',
            allowClear: true,
            width: '100%'
        });
    }

    // ==========================================
    // Customer Selection - Load Profile
    // ==========================================
    $('#customer_id').on('change', function() {
        const customerId = $(this).val();
        
        if (!customerId) {
            $('#customer-profile').html(`
                <p class="text-muted text-center">
                    <i class="fas fa-hand-pointer fa-2x mb-2 d-block"></i>
                    Select a customer to view profile
                </p>
            `);
            return;
        }

        $('#customer-profile').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');

        $.ajax({
            url: `/sales/customer/${customerId}/balance`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const c = response.customer;
                    const balance = parseFloat(response.balance) || 0;
                    const balanceClass = balance > 0 ? 'text-danger' : 'text-success';
                    const balanceLabel = balance > 0 ? 'Outstanding' : 'Credit';

                    let html = `
                        <div class="text-center mb-3">
                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                            <h5 class="mt-2 mb-0">${c.name}</h5>
                            <small class="text-muted">${c.phone || 'No phone'}</small>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <strong>${balanceLabel}:</strong>
                            <span class="${balanceClass} float-right font-weight-bold">
                                ${formatCurrency(Math.abs(balance))}
                            </span>
                        </div>
                        ${c.credit_limit ? `
                        <div class="mb-2">
                            <strong>Credit Limit:</strong>
                            <span class="float-right">${formatCurrency(c.credit_limit)}</span>
                        </div>
                        ` : ''}
                        ${c.address ? `
                        <div class="mb-2">
                            <strong>Address:</strong><br>
                            <small class="text-muted">${c.address}</small>
                        </div>
                        ` : ''}
                    `;

                    // Recent invoices
                    if (response.recent_invoices && response.recent_invoices.length > 0) {
                        html += `<hr><h6>Recent Invoices</h6><ul class="list-unstyled small">`;
                        response.recent_invoices.forEach(inv => {
                            const paid = parseFloat(inv.total_paid) || 0;
                            const total = parseFloat(inv.total_amount) || 0;
                            const status = paid >= total ? 'success' : 'warning';
                            html += `
                                <li class="mb-1">
                                    <a href="/sales/${inv.id}">${inv.invoice_number}</a>
                                    <span class="badge badge-${status} float-right">
                                        ${formatCurrency(total)}
                                    </span>
                                </li>
                            `;
                        });
                        html += `</ul>`;
                    }

                    $('#customer-profile').html(html);
                }
            },
            error: function() {
                $('#customer-profile').html('<p class="text-danger">Error loading customer</p>');
            }
        });
    });

    // ==========================================
    // Add Item Row
    // ==========================================
    function addItemRow() {
        const template = $('#item-row-template').html();
        const newRow = template.replace(/__INDEX__/g, itemIndex);
        $('#items-body').append(newRow);

        const $row = $(`tr[data-index="${itemIndex}"]`);
        initProductSelect2($row);
        
        itemIndex++;
        
        // Focus on product select
        setTimeout(() => {
            $row.find('.product-select').select2('open');
        }, 100);
    }

    $('#add-item-btn').on('click', addItemRow);

    // ==========================================
    // Product Select2 with Code/Name Search
    // ==========================================
    function initProductSelect2($row) {
        $row.find('.product-select').select2({
            placeholder: '-- Product --',
            allowClear: true,
            width: '100%',
            matcher: function(params, data) {
                if ($.trim(params.term) === '') return data;
                
                const term = params.term.toLowerCase();
                const text = (data.text || '').toLowerCase();
                const code = ($(data.element).data('code') || '').toLowerCase();
                
                if (text.indexOf(term) > -1 || code.indexOf(term) > -1) {
                    return data;
                }
                return null;
            }
        }).on('select2:open', function() {
            setTimeout(() => $('.select2-search__field').focus(), 100);
        });
    }

    // ==========================================
    // Product Selection - Load Details
    // ==========================================
    $(document).on('change', '.product-select', function() {
        const $row = $(this).closest('tr');
        const productId = $(this).val();
        const $option = $(this).find(':selected');

        if (!productId) {
            resetRowProduct($row);
            return;
        }

        // Set description
        $row.find('.item-description').val($option.data('name') || $option.text());

        // Set default price
        const price = $option.data('price') || 0;
        $row.find('.price-input').val(price);

        // Load product details for units and stock
        loadProductDetails(productId, $row);
        
        // Focus on quantity
        setTimeout(() => $row.find('.qty-input').focus(), 100);
    });

    function loadProductDetails(productId, $row) {
        // Check cache
        if (productsCache[productId]) {
            applyProductDetails(productsCache[productId], $row);
            return;
        }

        $.ajax({
            url: `/sales/product/${productId}/details`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    productsCache[productId] = response;
                    applyProductDetails(response, $row);
                    updateProductInfoSidebar(response);
                }
            }
        });
    }

    function applyProductDetails(data, $row) {
        const product = data.product;
        const units = data.units || [];

        // Populate unit dropdown
        const $unitSelect = $row.find('.unit-select');
        $unitSelect.empty().append('<option value="">-</option>');
        
        units.forEach(unit => {
            const selected = unit.is_base ? 'selected' : '';
            $unitSelect.append(`
                <option value="${unit.id}" ${selected} data-factor="${unit.conversion_factor}">
                    ${unit.symbol}
                </option>
            `);
        });

        // Show stock
        const stock = product.current_stock || 0;
        const stockClass = stock < (product.minimum_stock || 0) ? 'stock-low' : 'stock-ok';
        $row.find('.stock-display')
            .removeClass('stock-low stock-ok badge-secondary')
            .addClass(stockClass)
            .text(product.stock_display || stock);

        // Store base unit for calculations
        $row.data('base-unit-id', product.base_unit_id);
    }

    function updateProductInfoSidebar(data) {
        const p = data.product;
        const html = `
            <div class="text-center mb-2">
                <i class="fas fa-box fa-2x text-info"></i>
                <h6 class="mt-2 mb-0">${p.name}</h6>
                ${p.code ? `<small class="text-muted">${p.code}</small>` : ''}
            </div>
            <hr>
            <div class="mb-2">
                <strong>Stock:</strong>
                <span class="float-right">${p.stock_display}</span>
            </div>
            <div class="mb-2">
                <strong>Selling Price:</strong>
                <span class="float-right">${formatCurrency(p.selling_price)}</span>
            </div>
            ${p.minimum_stock ? `
            <div class="mb-2">
                <strong>Min Stock:</strong>
                <span class="float-right">${p.minimum_stock} ${p.base_unit_symbol}</span>
            </div>
            ` : ''}
        `;
        $('#product-info').html(html);
    }

    function resetRowProduct($row) {
        $row.find('.item-description').val('');
        $row.find('.unit-select').empty().append('<option value="">-</option>');
        $row.find('.stock-display').removeClass('stock-low stock-ok').addClass('badge-secondary').text('-');
        $row.find('.alt-qty-display').text('-');
        $row.find('.line-total').text('0.00');
        calculateTotals();
    }

    // ==========================================
    // Quantity/Price Change - Calculate Alt Qty
    // ==========================================
    $(document).on('input', '.qty-input, .price-input, .discount-input', function() {
        const $row = $(this).closest('tr');
        calculateRowTotal($row);
        calculateAltQty($row);
        calculateTotals();
    });

    $(document).on('change', '.unit-select', function() {
        const $row = $(this).closest('tr');
        calculateAltQty($row);
    });

    function calculateRowTotal($row) {
        const qty = parseFloat($row.find('.qty-input').val()) || 0;
        const price = parseFloat($row.find('.price-input').val()) || 0;
        const discountPercent = parseFloat($row.find('.discount-input').val()) || 0;

        let lineTotal = qty * price;
        if (discountPercent > 0) {
            lineTotal -= (lineTotal * discountPercent / 100);
        }

        $row.find('.line-total').text(formatNumber(lineTotal));
    }

    // ==========================================
    // Calculate Alternative Quantity Display
    // ==========================================
    function calculateAltQty($row) {
        const productId = $row.find('.product-select').val();
        const qty = parseFloat($row.find('.qty-input').val()) || 0;
        const unitId = $row.find('.unit-select').val();

        if (!productId || qty <= 0) {
            $row.find('.alt-qty-display').text('-');
            return;
        }

        $.ajax({
            url: '/sales/calculate-alt-qty',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val(),
                product_id: productId,
                quantity: qty,
                unit_id: unitId || null
            },
            success: function(response) {
                if (response.success) {
                    $row.find('.alt-qty-display')
                        .text(response.display)
                        .attr('title', `Base: ${formatNumber(response.base_quantity)}`);
                }
            },
            error: function() {
                $row.find('.alt-qty-display').text('-');
            }
        });
    }

    // ==========================================
    // Remove Row
    // ==========================================
    $(document).on('click', '.remove-row-btn', function() {
        const $row = $(this).closest('tr');
        
        if ($('#items-body tr').length > 1) {
            $row.remove();
            calculateTotals();
        } else {
            Swal.fire('Warning', 'At least one item is required', 'warning');
        }
    });

    // ==========================================
    // Calculate Totals
    // ==========================================
    function calculateTotals() {
        let subtotal = 0;

        $('#items-body tr').each(function() {
            const lineTotal = parseFloat($(this).find('.line-total').text().replace(/,/g, '')) || 0;
            subtotal += lineTotal;
        });

        const labour = parseFloat($('#labour_amount').val()) || 0;
        const transportation = parseFloat($('#transportation_amount').val()) || 0;
        const discount = parseFloat($('#discount_amount').val()) || 0;
        const roundOff = parseFloat($('#round_off_amount').val()) || 0;

        const grandTotal = subtotal + labour + transportation - discount + roundOff;

        $('#display-subtotal').text(formatNumber(subtotal));
        $('#display-labour').text(formatNumber(labour));
        $('#display-transportation').text(formatNumber(transportation));
        $('#display-discount').text(formatNumber(discount));
        $('#display-roundoff').text(formatNumber(roundOff));
        $('#display-total').text(formatNumber(grandTotal));
    }

    // Trigger calculation on additional charges change
    $('.calc-trigger').on('input', calculateTotals);

    // ==========================================
    // Keyboard Navigation (TAB/ENTER to add row)
    // ==========================================
    $(document).on('keydown', '.price-input, .discount-input', function(e) {
        if (e.key === 'Tab' || e.key === 'Enter') {
            const $row = $(this).closest('tr');
            const isLastRow = $row.is('#items-body tr:last');
            const qty = parseFloat($row.find('.qty-input').val()) || 0;

            if (isLastRow && qty > 0 && $(this).hasClass('price-input')) {
                e.preventDefault();
                addItemRow();
            }
        }
    });

    // ==========================================
    // Form Submission
    // ==========================================
    $('#invoice-form').on('submit', function(e) {
        e.preventDefault();

        // Validate
        if (!$('#customer_id').val()) {
            Swal.fire('Error', 'Please select a customer', 'error');
            $('#customer_id').select2('open');
            return;
        }

        let hasItems = false;
        $('#items-body tr').each(function() {
            const productId = $(this).find('.product-select').val();
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            if (productId && qty > 0) {
                hasItems = true;
                return false;
            }
        });

        if (!hasItems) {
            Swal.fire('Error', 'Please add at least one product item', 'error');
            return;
        }

        // Disable submit button
        const $btn = $('#submit-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

        // Prepare form data
        const formData = $(this).serializeArray();

        $.ajax({
            url: '{{ route("sales.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: `Invoice ${response.invoice_number} created successfully`,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'View Invoice',
                        cancelButtonText: 'Create Another'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to create invoice', 'error');
                }
            },
            error: function(xhr) {
                let message = 'Failed to create invoice';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join('<br>');
                    }
                }
                Swal.fire('Error', message, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Create Invoice');
            }
        });
    });

    // ==========================================
    // Utility Functions
    // ==========================================
    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatCurrency(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});

</script>
@stop