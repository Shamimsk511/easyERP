@extends('adminlte::page')

@section('title', 'Edit Invoice - ' . $invoice->invoice_number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Invoice {{ $invoice->invoice_number }}</h3>
                </div>

                <form id="invoice-form">
                    @csrf
                    <div class="card-body">
                        <!-- Customer Selection -->
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

                        <!-- Invoice Date -->
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" id="invoice_date" name="invoice_date" class="form-control" 
                                       value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="due_date">Due Date</label>
                                <input type="date" id="due_date" name="due_date" class="form-control"
                                       value="{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>

                        <!-- Sales Account -->
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

                        <!-- Line Items -->
                        <hr>
                        <h5>Line Items <span class="text-danger">*</span></h5>
                        <table class="table table-sm" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%">Product</th>
                                    <th style="width: 15%">Qty</th>
                                    <th style="width: 15%">Unit Price</th>
                                    <th style="width: 15%">Discount %</th>
                                    <th style="width: 15%">Total</th>
                                    <th style="width: 10%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->items as $index => $item)
                                <tr class="item-row">
                                    <td>
                                        <select name="items[{{ $index }}][product_id]" class="form-control product-select" style="width: 100%;">
                                            <option value="">-- Select Product --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control quantity" 
                                               value="{{ $item->quantity }}" step="0.001" min="0"></td>
                                    <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control unit_price" 
                                               value="{{ $item->unit_price }}" step="0.01" min="0"></td>
                                    <td><input type="number" name="items[{{ $index }}][discount_percent]" class="form-control discount_percent" 
                                               value="{{ $item->discount_percent }}" step="0.01" min="0" max="100"></td>
                                    <td><span class="item-total">{{ number_format($item->line_total, 2) }}</span></td>
                                    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-success" id="add-item">
                            <i class="fas fa-plus"></i> Add Item
                        </button>

                        <hr>

                        <!-- Totals -->
                        <div class="form-row">
                            <div class="col-md-6"></div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subtotal: <strong id="subtotal">{{ number_format($invoice->subtotal, 2) }}</strong></label>
                                </div>
                                <div class="form-group">
                                    <label for="tax_amount">Tax Amount</label>
                                    <input type="number" id="tax_amount" name="tax_amount" class="form-control" 
                                           value="{{ $invoice->tax_amount }}" step="0.01" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Total Amount: <strong id="total-amount" style="font-size: 18px;">{{ number_format($invoice->total_amount, 2) }}</strong></label>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="form-group">
                            <label for="customer_notes">Customer Notes</label>
                            <textarea id="customer_notes" name="customer_notes" class="form-control" rows="3">{{ $invoice->customer_notes }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
                        <a href="{{ route('sales.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Customer Info</h3>
                </div>
                <div class="card-body" id="customer-info">
                    <!-- Auto-loaded -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let itemCounter = {{ count($invoice->items) }};

    $('.select2').select2({ allowClear: true });
    $('.product-select').select2({ allowClear: true, placeholder: '-- Select Product --' });

    // Load customer info on page load
    const customerId = $('#customer_id').val();
    if (customerId) loadCustomerInfo(customerId);

    $('#customer_id').on('change', function() {
        if (this.value) loadCustomerInfo(this.value);
        else $('#customer-info').html('<p class="text-muted">Select a customer</p>');
    });

    function loadCustomerInfo(id) {
        $.ajax({
            url: '/sales/' + id + '/profile',
            success: function(response) {
                if (response.success) {
                    let html = `
                        <p><strong>${response.customer.name}</strong></p>
                        <p>${response.customer.code}</p>
                        <hr>
                        <p><strong>Phone:</strong> ${response.customer.phone}</p>
                        <p><strong>Outstanding:</strong> <span class="text-danger">৳ ${parseFloat(response.balance.outstanding).toFixed(2)}</span></p>
                        <p><strong>Credit Remaining:</strong> <span class="text-success">৳ ${parseFloat(response.balance.credit_remaining).toFixed(2)}</span></p>
                    `;
                    $('#customer-info').html(html);
                }
            }
        });
    }

    $('#add-item').on('click', function() {
        const newRow = `
            <tr class="item-row">
                <td>
                    <select name="items[${itemCounter}][product_id]" class="form-control product-select" style="width: 100%;">
                        <option value="">-- Select Product --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="items[${itemCounter}][quantity]" class="form-control quantity" step="0.001" min="0"></td>
                <td><input type="number" name="items[${itemCounter}][unit_price]" class="form-control unit_price" step="0.01" min="0"></td>
                <td><input type="number" name="items[${itemCounter}][discount_percent]" class="form-control discount_percent" step="0.01" min="0" max="100" value="0"></td>
                <td><span class="item-total">0.00</span></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
        $('#items-table tbody').append(newRow);
        $('.product-select').last().select2({ allowClear: true, placeholder: '-- Select Product --' });
        itemCounter++;
        calculateTotals();
    });

    $(document).on('click', '.remove-row', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        calculateTotals();
    });

    $(document).on('input', '.quantity, .unit_price, .discount_percent', function() {
        const row = $(this).closest('tr');
        const qty = parseFloat(row.find('.quantity').val()) || 0;
        const price = parseFloat(row.find('.unit_price').val()) || 0;
        const discount = parseFloat(row.find('.discount_percent').val()) || 0;
        const lineTotal = (qty * price) * (1 - discount / 100);
        row.find('.item-total').text(lineTotal.toFixed(2));
        calculateTotals();
    });

    $('#tax_amount').on('input', calculateTotals);

    function calculateTotals() {
        let subtotal = 0;
        $('#items-table tbody tr').each(function() {
            const total = parseFloat($(this).find('.item-total').text()) || 0;
            subtotal += total;
        });
        const tax = parseFloat($('#tax_amount').val()) || 0;
        const grandTotal = subtotal + tax;
        $('#subtotal').text(subtotal.toFixed(2));
        $('#total-amount').text(grandTotal.toFixed(2));
    }

    $('#invoice-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serializeArray();
        
        $.ajax({
            url: '{{ route('sales.update', $invoice) }}',
            type: 'PUT',
            data: formData,
            success: function(response) {
                Swal.fire('Success!', response.message, 'success');
                window.location.href = response.redirect_url;
            },
            error: function(xhr) {
                Swal.fire('Error!', 'Failed to update invoice', 'error');
            }
        });
    });
});
</script>
@endpush
