@extends('adminlte::page')
@section('title', 'Edit Invoice - ' . $invoice->invoice_number)

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1>Edit Invoice {{ $invoice->invoice_number }}</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('sales.show', $invoice) }}" class="btn btn-info"><i class="fas fa-eye"></i> View</a>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
                    @method('PUT')
                    <div class="card-body">

                        <div class="form-group">
                            <label for="customer_id">Customer <span class="text-danger">*</span></label>
                            <select id="customer_id" name="customer_id" class="form-control select2" style="width: 100%;" required>
                                <option value="">-- Select Customer --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $invoice->customer_id == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} ({{ $customer->phone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" id="invoice_date" name="invoice_date" class="form-control" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control" value="{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sales_account_id">Sales Account</label>
                            <select id="sales_account_id" name="sales_account_id" class="form-control select2" style="width: 100%;">
                                <option value="">-- Default (Sales) --</option>
                                @foreach($salesAccounts as $account)
                                    <option value="{{ $account->id }}" {{ $invoice->sales_account_id == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
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
                                @foreach($invoice->items as $index => $item)
                                <tr class="item-row" data-index="{{ $index }}">
                                    <td>
                                        <select name="items[{{ $index }}][product_id]" class="form-control product-select select2" style="width: 100%;">
                                            <option value="">-- Select Product --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity" step="0.001" min="0.001" value="{{ $item->quantity }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit_price" step="0.01" min="0" value="{{ $item->unit_price }}"></td>
                                    <td><input type="number" name="items[{{ $index }}][discount_percent]" class="form-control discount_percent" step="0.01" min="0" max="100" value="{{ $item->discount_percent ?? 0 }}"></td>
                                    <td><span class="item-total font-weight-bold">{{ number_format($item->line_total, 2) }}</span></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                @endforeach
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
                                    <input type="number" id="tax_amount" name="tax_amount" class="form-control" step="0.01" min="0" value="{{ $invoice->tax_amount ?? 0 }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Grand Total</label>
                                    <h3 id="grand-total" class="text-primary mb-0">৳ {{ number_format($invoice->total_amount, 2) }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="customer_notes">Customer Notes</label>
                            <textarea id="customer_notes" name="customer_notes" class="form-control" rows="2">{{ $invoice->customer_notes }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save"></i> Update Invoice
                        </button>
                        <a href="{{ route('sales.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Customer Info</h3>
                </div>
                <div class="card-body" id="customer-info">
                    <p class="text-muted">Loading...</p>
                </div>
            </div>

            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator"></i> Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr><td>Subtotal:</td><td class="text-right" id="summary-subtotal">৳ {{ number_format($invoice->subtotal, 2) }}</td></tr>
                        <tr><td>Tax:</td><td class="text-right" id="summary-tax">৳ {{ number_format($invoice->tax_amount ?? 0, 2) }}</td></tr>
                        <tr class="border-top"><th>Total:</th><th class="text-right" id="summary-total">৳ {{ number_format($invoice->total_amount, 2) }}</th></tr>
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
    #items-table .form-control { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    .item-total { display: block; padding: 0.5rem; }
</style>
@stop

@push('js')
<script>
$(document).ready(function() {
    let itemIndex = {{ count($invoice->items) }};

    // Initialize Select2
    $('.select2').select2({ allowClear: true, placeholder: '-- Select --' });

    // Load customer info on page load
    const customerId = $('#customer_id').val();
    if (customerId) loadCustomerInfo(customerId);

    $('#customer_id').on('change', function() {
        if (this.value) loadCustomerInfo(this.value);
        else $('#customer-info').html('<p class="text-muted">Select a customer</p>');
    });

    function loadCustomerInfo(id) {
        $.get('{{ url("sales/get-customer") }}/' + id, function(response) {
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
            }
        });
    }

    // Add new item row
    $('#add-item').on('click', function() {
        const newRow = `
            <tr class="item-row" data-index="${itemIndex}">
                <td>
                    <select name="items[${itemIndex}][product_id]" class="form-control product-select" style="width: 100%;">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control quantity" step="0.001" min="0.001"></td>
                <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control unit_price" step="0.01" min="0"></td>
                <td><input type="number" name="items[${itemIndex}][discount_percent]" class="form-control discount_percent" step="0.01" min="0" max="100" value="0"></td>
                <td><span class="item-total font-weight-bold">0.00</span></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#items-body').append(newRow);
        $('#items-body tr:last .product-select').select2({ allowClear: true, placeholder: '-- Select Product --' });
        itemIndex++;
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            Swal.fire('Warning', 'At least one item is required', 'warning');
        }
    });

    // Calculate on input
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
            _method: 'PUT',
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
            url: '{{ route("sales.update", $invoice) }}',
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
                    $('#submit-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Update Invoice');
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
                $('#submit-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Update Invoice');
            }
        });
    });

    // Initial calculation
    calculateTotals();
});
</script>
@endpush