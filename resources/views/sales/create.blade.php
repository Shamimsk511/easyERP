@extends('adminlte::page')
@section('title', 'Create Invoice')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1>Create Invoice</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                </div>

                <form id="invoice-form">
                    @csrf
                    <div class="card-body">
                        
                        <div class="form-group">
                            <label for="customer_id">Customer <span class="text-danger">*</span></label>
                            <select id="customer_id" name="customer_id" class="form-control" style="width: 100%;" required></select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" id="invoice_date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sales_account_id">Sales Account</label>
                            <select id="sales_account_id" name="sales_account_id" class="form-control select2" style="width: 100%;">
                                <option value="">-- Default (Sales) --</option>
                                @foreach($salesAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr>
                        <h5>Line Items <span class="text-danger">*</span></h5>
                        <table class="table table-sm table-bordered" id="items-table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 30%">Product</th>
                                    <th style="width: 12%">Qty</th>
                                    <th style="width: 15%">Unit Price</th>
                                    <th style="width: 12%">Disc %</th>
                                    <th style="width: 15%">Total</th>
                                    <th style="width: 8%"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr class="item-row" data-index="0">
                                    <td>
                                        <select name="items[0][product_id]" class="form-control product-select" style="width: 100%;"></select>
                                    </td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control quantity" step="0.001" min="0.001"></td>
                                    <td><input type="number" name="items[0][unit_price]" class="form-control unit_price" step="0.01" min="0"></td>
                                    <td><input type="number" name="items[0][discount_percent]" class="form-control discount_percent" step="0.01" min="0" max="100" value="0"></td>
                                    <td><span class="item-total font-weight-bold">0.00</span></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <button type="button" id="add-item" class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Add Item
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>

                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tax_amount">Tax Amount</label>
                                    <input type="number" id="tax_amount" name="tax_amount" class="form-control" step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Grand Total</label>
                                    <h3 id="grand-total" class="text-primary mb-0">৳ 0.00</h3>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="customer_notes">Customer Notes</label>
                            <textarea id="customer_notes" name="customer_notes" class="form-control" rows="2" placeholder="Notes to appear on invoice..."></textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save"></i> Create Invoice
                        </button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info" id="customer-card" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Customer Info</h3>
                </div>
                <div class="card-body" id="customer-info"></div>
            </div>

            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator"></i> Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr><td>Subtotal:</td><td class="text-right" id="summary-subtotal">৳ 0.00</td></tr>
                        <tr><td>Tax:</td><td class="text-right" id="summary-tax">৳ 0.00</td></tr>
                        <tr class="border-top"><th>Total:</th><th class="text-right" id="summary-total">৳ 0.00</th></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .select2-container--open .select2-dropdown { border: 1px solid #80bdff; }
    .select2-container--focus .select2-selection { border-color: #80bdff !important; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
    #items-table .form-control { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    .item-total { display: block; padding: 0.5rem; }
</style>
@stop

@push('js')
<script>
$(document).ready(function() {
    let itemIndex = 1;

    // Customer Select2 with AJAX
    $('#customer_id').select2({
        placeholder: 'Search customer by name or phone...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '{{ route("sales.search-customers") }}',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term, page: params.page || 1 }),
            processResults: data => ({ results: data.results, pagination: data.pagination }),
            cache: true
        },
        templateResult: item => {
            if (item.loading) return item.text;
            return $('<div><strong>' + item.name + '</strong><br><small class="text-muted">' + item.customer_code + ' | ' + item.phone + ' | Balance: ' + item.balance + '</small></div>');
        },
        templateSelection: item => item.name || item.text
    });

    // On customer select - load profile
    $('#customer_id').on('select2:select', function(e) {
        loadCustomerInfo(e.params.data.id);
    }).on('select2:unselect', function() {
        $('#customer-card').hide();
    });

    function loadCustomerInfo(customerId) {
        $.get('{{ url("sales/get-customer") }}/' + customerId, function(response) {
            if (response.success) {
                const d = response.data;
                const balanceClass = d.outstanding_balance > 0 ? 'text-danger' : 'text-success';
                $('#customer-info').html(`
                    <h5 class="mb-1">${d.name}</h5>
                    <p class="text-muted mb-2">${d.customer_code}</p>
                    <hr>
                    <p class="mb-1"><i class="fas fa-phone fa-fw"></i> ${d.phone}</p>
                    <p class="mb-1"><i class="fas fa-envelope fa-fw"></i> ${d.email}</p>
                    <p class="mb-1"><i class="fas fa-map-marker-alt fa-fw"></i> ${d.address}, ${d.city}</p>
                    <hr>
                    <p class="mb-1"><strong>Outstanding:</strong> <span class="${balanceClass}">৳ ${parseFloat(d.outstanding_balance).toFixed(2)}</span></p>
                    <p class="mb-1"><strong>Credit Limit:</strong> ৳ ${parseFloat(d.credit_limit).toFixed(2)}</p>
                    <p class="mb-0"><strong>Available:</strong> <span class="text-success">৳ ${parseFloat(d.credit_remaining).toFixed(2)}</span></p>
                `);
                $('#customer-card').show();
            }
        });
    }

    // Initialize product select
    function initProductSelect($el) {
        $el.select2({
            placeholder: 'Search product...',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '{{ route("sales.search-products") }}',
                dataType: 'json',
                delay: 300,
                data: params => ({ q: params.term, page: params.page || 1 }),
                processResults: data => ({ results: data.results, pagination: data.pagination }),
                cache: true
            },
            templateResult: item => {
                if (item.loading) return item.text;
                const stockClass = item.stock > 0 ? 'text-success' : 'text-danger';
                return $('<div><strong>' + item.name + '</strong><br><small class="text-muted">' + (item.code || 'N/A') + ' | Stock: <span class="' + stockClass + '">' + item.stock + '</span> ' + item.unit + ' | ৳' + parseFloat(item.price || 0).toFixed(2) + '</small></div>');
            },
            templateSelection: item => item.name || item.text
        });
    }

    initProductSelect($('.product-select'));

    // On product select - fill price
    $(document).on('select2:select', '.product-select', function(e) {
        const row = $(this).closest('tr');
        const customerId = $('#customer_id').val();
        const productId = e.params.data.id;

        $.get('{{ url("sales/product") }}/' + productId + '/details', { customer_id: customerId }, function(response) {
            if (response.success) {
                const price = response.product.last_rate || response.product.selling_price || 0;
                row.find('.unit_price').val(parseFloat(price).toFixed(2));
                row.find('.quantity').val(1).focus();
                calculateRowTotal(row);
            }
        });
    });

    // Add new item row
    $('#add-item').on('click', function() {
        const newRow = `
            <tr class="item-row" data-index="${itemIndex}">
                <td><select name="items[${itemIndex}][product_id]" class="form-control product-select" style="width: 100%;"></select></td>
                <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control quantity" step="0.001" min="0.001"></td>
                <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control unit_price" step="0.01" min="0"></td>
                <td><input type="number" name="items[${itemIndex}][discount_percent]" class="form-control discount_percent" step="0.01" min="0" max="100" value="0"></td>
                <td><span class="item-total font-weight-bold">0.00</span></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#items-body').append(newRow);
        initProductSelect($('#items-body tr:last .product-select'));
        itemIndex++;
    });

    // Remove item row
    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            Swal.fire('Warning', 'At least one item is required', 'warning');
        }
    });

    // Calculate row total on input change
    $(document).on('input', '.quantity, .unit_price, .discount_percent', function() {
        calculateRowTotal($(this).closest('tr'));
    });

    $('#tax_amount').on('input', calculateTotals);

    function calculateRowTotal(row) {
        const qty = parseFloat(row.find('.quantity').val()) || 0;
        const price = parseFloat(row.find('.unit_price').val()) || 0;
        const discPct = parseFloat(row.find('.discount_percent').val()) || 0;
        const subtotal = qty * price;
        const discount = subtotal * (discPct / 100);
        const total = subtotal - discount;
        row.find('.item-total').text(total.toFixed(2));
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        $('.item-total').each(function() {
            subtotal += parseFloat($(this).text()) || 0;
        });
        const tax = parseFloat($('#tax_amount').val()) || 0;
        const grandTotal = subtotal + tax;

        $('#summary-subtotal').text('৳ ' + subtotal.toFixed(2));
        $('#summary-tax').text('৳ ' + tax.toFixed(2));
        $('#summary-total, #grand-total').text('৳ ' + grandTotal.toFixed(2));
    }

    // Form submission
    $('#invoice-form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#customer_id').val()) {
            Swal.fire('Error', 'Please select a customer', 'error');
            return;
        }

        const items = [];
        let hasItems = false;
        $('.item-row').each(function() {
            const productId = $(this).find('.product-select').val();
            const qty = parseFloat($(this).find('.quantity').val()) || 0;
            const price = parseFloat($(this).find('.unit_price').val()) || 0;
            if (productId && qty > 0 && price > 0) {
                hasItems = true;
                items.push({
                    product_id: productId,
                    quantity: qty,
                    unit_price: price,
                    discount_percent: parseFloat($(this).find('.discount_percent').val()) || 0
                });
            }
        });

        if (!hasItems) {
            Swal.fire('Error', 'Please add at least one valid item', 'error');
            return;
        }

        const formData = {
            _token: '{{ csrf_token() }}',
            customer_id: $('#customer_id').val(),
            invoice_date: $('#invoice_date').val(),
            due_date: $('#due_date').val(),
            sales_account_id: $('#sales_account_id').val(),
            tax_amount: parseFloat($('#tax_amount').val()) || 0,
            customer_notes: $('#customer_notes').val(),
            items: items
        };

        $('#submit-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route("sales.store") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.redirect_url;
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                    $('#submit-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Create Invoice');
                }
            },
            error: function(xhr) {
                let msg = 'An error occurred';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    } else if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                }
                Swal.fire('Error', msg, 'error');
                $('#submit-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Create Invoice');
            }
        });
    });
});
</script>
@endpush