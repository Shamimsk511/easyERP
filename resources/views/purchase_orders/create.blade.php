@extends('adminlte::page')
@section('title', 'Add Purchase Order')

@section('content_header')
    <h1>Add Purchase Order</h1>
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
                    <form id="purchase-form">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="vendor_id">Vendor <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="vendor_id" id="vendor_id" required style="width: 100%;">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" data-ledger-id="{{ $vendor->ledger_account_id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Order Date <span class="text-danger">*</span></label>
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
                                <option value="{{ $account->id }}" 
                                    {{ old('purchase_account_id', $defaultPurchaseAccount->id ?? '') == $account->id ? 'selected' : '' }}
                                    data-code="{{ $account->code }}">
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Select the expense account for this purchase (default: 5100 - Purchases)
                        </small>
                    </div>
                </div>
</div>
</div>
                        <!-- Products Table -->
                        <div class="form-group">
                            <label>Products <span class="text-danger">*</span></label>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="po-items">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="35%">Product</th>
                                            <th width="15%">Qty</th>
                                            <th width="10%">Unit</th>
                                            <th width="15%">Rate</th>
                                            <th width="20%">Amount</th>
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
                                                            data-unit="{{ $product->baseUnit->symbol ?? '' }}" 
                                                            data-rate="{{ $product->purchase_price ?? 0 }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-link btn-xs p-0 mt-1 quick-add-product" type="button">
                                                    <i class="fa fa-plus-circle"></i> Quick Add
                                                </button>
                                            </td>
                                            <td><input type="number" class="form-control form-control-sm qty" name="items[0][quantity]" min="1" step="0.01" required></td>
                                            <td><input type="text" class="form-control form-control-sm unit-display" readonly></td>
                                            <td><input type="number" class="form-control form-control-sm rate" name="items[0][rate]" min="0.01" step="0.01" required></td>
                                            <td><input type="text" class="form-control form-control-sm amount" readonly></td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-xs remove-row" title="Remove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                            <td colspan="2"><input type="text" class="form-control form-control-sm" id="grand-total" readonly></td>
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
                        <p class="mb-2"><strong id="vendor-name-display">-</strong></p>
                        <hr>
                        <div class="info-box mb-0">
                            <span class="info-box-icon bg-warning"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Balance Due</span>
                                <span class="info-box-number" id="vendor-balance">৳ 0.00</span>
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

<!-- Product Quick Add Modal -->
<div class="modal fade" id="quickAddProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="quick-add-product-form">
                @csrf
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">Quick Add Product</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="quick-product-name" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Base Unit <span class="text-danger">*</span></label>
                        <input type="text" name="base_unit" class="form-control" id="quick-product-unit" required placeholder="e.g., PCS, KG, LTR">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Add Product
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
/* Select2 Enhancement */
.select2-container--default .select2-selection--single {
    height: calc(2.25rem + 2px) !important;
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(2.25rem) !important;
    padding-left: 0;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(2.25rem) !important;
}

.select2-container--default .select2-results__option {
    padding: 8px 12px;
    border-bottom: 1px solid #f0f0f0;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #007bff !important;
    color: white;
}

.select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da !important;
    padding: 6px 12px;
}

.select2-search--dropdown .select2-search__field:focus {
    border-color: #80bdff !important;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.select2-dropdown {
    border: 1px solid #ced4da !important;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

/* Table Improvements */
#po-items tbody tr td {
    vertical-align: middle !important;
}

#po-items .form-control-sm {
    height: calc(1.8rem + 2px);
    font-size: 0.875rem;
}

.table-sm td, .table-sm th {
    padding: 0.3rem;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .btn-xs {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
    }
    
    #po-items th:nth-child(3),
    #po-items td:nth-child(3) {
        display: none; /* Hide unit column on mobile */
    }
}

/* Quick Add Button */
.quick-add-product {
    font-size: 0.75rem;
    color: #28a745;
    text-decoration: none;
}

.quick-add-product:hover {
    color: #218838;
    text-decoration: underline;
}

/* Amount field highlight */
.amount {
    background-color: #f8f9fa !important;
    font-weight: 600;
}

#grand-total {
    background-color: #e9ecef !important;
    font-weight: bold;
    font-size: 1.1rem;
}
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    let rowIndex = 1;
    let currentQuickAddRow = null;

    // Initialize Select2 with proper configuration
    function initSelect2($element) {
        // Destroy existing Select2 instance if any
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }

        $element.select2({
            width: '100%',
            theme: 'default',
            placeholder: 'Select an option',
            allowClear: true,
            closeOnSelect: true,
            dropdownAutoWidth: false
        });

        // Auto-focus search field when dropdown opens
        $element.on('select2:open', function() {
            setTimeout(function() {
                document.querySelector('.select2-search__field').focus();
            }, 50);
        });
    }

    // Initialize all select2 elements on page load
    initSelect2($('#vendor_id'));
    initSelect2($('#purchase_account_id')); // ← ADD THIS LINE
    
    $('.product-select').each(function() {
        initSelect2($(this));
    });

    // Vendor Selection - Show Balance
    $('#vendor_id').on('select2:select', function(e) {
        const vendorId = $(this).val();
        const vendorName = $(this).find('option:selected').text();
        const ledgerAccountId = $(this).find('option:selected').data('ledger-id');

        if (vendorId) {
            $('#no-vendor-selected').hide();
            $('#vendor-info').show();
            $('#vendor-name-display').text(vendorName);

            // Fetch vendor balance via AJAX
            $.ajax({
                url: '/vendors/' + vendorId + '/balance',
                method: 'GET',
                success: function(response) {
                    const balance = parseFloat(response.balance || 0);
                    const formattedBalance = '৳ ' + Math.abs(balance).toFixed(2);
                    
                    if (balance > 0) {
                        $('#vendor-balance').html(formattedBalance + ' <small class="text-danger">(You owe)</small>');
                        $('.info-box-icon').removeClass('bg-success bg-warning').addClass('bg-danger');
                    } else if (balance < 0) {
                        $('#vendor-balance').html(formattedBalance + ' <small class="text-success">(Advance)</small>');
                        $('.info-box-icon').removeClass('bg-danger bg-warning').addClass('bg-success');
                    } else {
                        $('#vendor-balance').text(formattedBalance);
                        $('.info-box-icon').removeClass('bg-success bg-danger').addClass('bg-warning');
                    }
                },
                error: function(xhr) {
                    console.error('Vendor balance error:', xhr);
                    $('#vendor-balance').text('৳ 0.00');
                }
            });
        } else {
            $('#vendor-info').hide();
            $('#no-vendor-selected').show();
        }
    });

    // Purchase Account Selection - Show account details (optional)
    $('#purchase_account_id').on('select2:select', function(e) {
        const accountCode = $(this).find('option:selected').data('code');
        const accountName = $(this).find('option:selected').text();
        console.log('Selected Purchase Account:', accountCode, '-', accountName);
    });

    // Product Selection - Auto-fill Unit & Rate
    $(document).on('select2:select', '.product-select', function(e) {
        const $row = $(this).closest('tr');
        const selectedOption = $(this).find('option:selected');
        const unit = selectedOption.data('unit');
        const rate = selectedOption.data('rate');

        $row.find('.unit-display').val(unit || '');
        
        if (rate > 0) {
            $row.find('.rate').val(parseFloat(rate).toFixed(2));
        }

        // Trigger calculation
        calculateRowAmount($row);
        
        // Focus on quantity field after selection
        setTimeout(function() {
            $row.find('.qty').focus();
        }, 100);
    });

    // Calculate Amount per Row
    $(document).on('input', '.qty, .rate', function() {
        const $row = $(this).closest('tr');
        calculateRowAmount($row);
    });

    function calculateRowAmount($row) {
        const qty = parseFloat($row.find('.qty').val()) || 0;
        const rate = parseFloat($row.find('.rate').val()) || 0;
        const amount = qty * rate;
        
        $row.find('.amount').val(amount > 0 ? amount.toFixed(2) : '');
        calculateGrandTotal();
    }

    // Calculate Grand Total
    function calculateGrandTotal() {
        let total = 0;
        $('#po-items tbody tr').each(function() {
            const amount = parseFloat($(this).find('.amount').val()) || 0;
            total += amount;
        });
        $('#grand-total').val('৳ ' + total.toFixed(2));
    }

    // Add New Row
    $(document).on('click', '.add-row', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $firstRow = $('#po-items tbody tr:first');
        const $newRow = $firstRow.clone();

        // Clear values
        $newRow.find('input, textarea').val('');
        $newRow.find('.unit-display').val('');
        
        // Completely remove Select2
        $newRow.find('.select2-container').remove();
        const $select = $newRow.find('select');
        $select.removeClass('select2-hidden-accessible')
               .removeAttr('data-select2-id aria-hidden tabindex')
               .show();
        $select.find('option:first').prop('selected', true);

        // Update names with new index
        $newRow.find('[name]').each(function() {
            const name = $(this).attr('name');
            if (name && name.includes('items[')) {
                $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']'));
            }
        });

        $('#po-items tbody').append($newRow);
        
        // Initialize Select2 on new row
        initSelect2($newRow.find('.product-select'));
        
        rowIndex++;
    });

    // Remove Row
    $(document).on('click', '.remove-row', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if ($('#po-items tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateGrandTotal();
            updateRowIndexes();
        } else {
            Swal.fire('Warning', 'At least one product row is required.', 'warning');
        }
    });

    // Update Row Indexes after removal
    function updateRowIndexes() {
        $('#po-items tbody tr').each(function(index) {
            $(this).find('[name]').each(function() {
                const name = $(this).attr('name');
                if (name && name.includes('items[')) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + index + ']'));
                }
            });
        });
        rowIndex = $('#po-items tbody tr').length;
    }

    // Quick Add Product
    $(document).on('click', '.quick-add-product', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        currentQuickAddRow = $(this).closest('tr');
        $('#quickAddProductModal').modal('show');
        $('#quick-add-product-form')[0].reset();
        
        setTimeout(function() {
            $('#quick-product-name').focus();
        }, 500);
    });

    // Quick Add Product Form Submit
    $('#quick-add-product-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '/products/quick-add',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response && response.id && response.name) {
                    // Add to all product selects
                    $('.product-select').each(function() {
                        const $option = $('<option></option>')
                            .val(response.id)
                            .text(response.name)
                            .attr('data-unit', response.unit || '')
                            .attr('data-rate', 0);
                        $(this).append($option);
                    });

                    // Select in current row
                    if (currentQuickAddRow) {
                        currentQuickAddRow.find('.product-select').val(response.id).trigger('change');
                    }

                    $('#quickAddProductModal').modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Product added successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to add product', 'error');
            }
        });
    });

    // Submit Purchase Order Form
    $('#purchase-form').on('submit', function(e) {
        e.preventDefault();

        // Validate at least one product
        if ($('#po-items tbody tr').length === 0) {
            Swal.fire('Warning', 'Please add at least one product.', 'warning');
            return false;
        }

        // Validate vendor is selected
        if (!$('#vendor_id').val()) {
            Swal.fire('Warning', 'Please select a vendor.', 'warning');
            return false;
        }

        // Validate purchase account is selected
        if (!$('#purchase_account_id').val()) {
            Swal.fire('Warning', 'Please select a purchase account.', 'warning');
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we save your purchase order.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/purchase-orders',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Purchase order created successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '/purchase-orders';
                });
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to save purchase order', 'error');
            }
        });
    });
});
</script>
@endpush


@endsection
