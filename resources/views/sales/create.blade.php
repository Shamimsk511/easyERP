{{-- resources/views/sales/create.blade.php --}}
@extends('adminlte::page')

@section('title', 'Create Invoice')

@section('content_header')
    <h1>Create New Invoice</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-9">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                </div>

                <form id="invoiceForm">
                    @csrf
                    <div class="card-body">
                        <!-- Customer and Date Row -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select name="customer_id" id="customer_id" class="form-control" style="width: 100%;" required></select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#quickAddCustomerModal" title="Add new customer">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" id="invoice_date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Sales Account -->
                        <div class="form-group">
                            <label for="sales_account_id">Sales Account</label>
                            <select id="sales_account_id" name="sales_account_id" class="form-control select2" style="width: 100%;">
                                <option value="">Default Account 4100 - Sales</option>
                                @foreach($salesAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Invoice Items (PHASE 2: Base Unit + Alternative Unit Selector) -->
                        <div class="form-group">
                            <label>Products <span class="text-danger">*</span></label>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="itemsTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="25%">Product</th>
                                            <th width="15%">Base Unit</th>
                                            <th width="12%">Alt Unit</th>
                                            <th width="10%">Qty (Base)</th>
                                            <th width="12%">Alt Display</th>
                                            <th width="12%">Rate</th>
                                            <th width="12%">Discount %</th>
                                            <th width="12%">Amount</th>
                                            <th width="5%">
                                                <button type="button" class="btn btn-success btn-xs add-item">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control form-control-sm product-select" name="items[0][product_id]" style="width: 100%;" required></select>
                                            </td>
                                            <td>
                                                <span class="base-unit-display badge badge-info"></span>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm alt-unit-select" name="items[0][alt_unit_id]">
                                                    <option value="">Base Unit</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm qty" name="items[0][quantity]" min="0.01" step="0.01" required>
                                            </td>
                                            <td>
                                                <span class="alt-display badge badge-secondary" style="font-size: 0.8em;"></span>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm rate" name="items[0][unit_price]" min="0.01" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm discount" name="items[0][discount_percent]" min="0" max="100" step="0.01" value="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm amount" readonly style="background-color: #f0f0f0;">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-xs remove-item">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="6" class="text-right"><strong>Subtotal</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm" id="subtotal" readonly style="background-color: #f0f0f0;">
                                            </td>
                                        </tr>
                                        <tr class="table-active">
                                            <td colspan="6" class="text-right"><strong>Discount</strong></td>
                                            <td colspan="2">
                                                <input type="text" class="form-control form-control-sm" id="display-discount" readonly style="background-color: #f0f0f0;">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- PAYMENT PANEL (NEW) -->
                        <div class="card card-info collapsed-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Record Payment Now
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" id="paymentPanel" style="display: none;">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Payment Date</label>
                                            <input type="date" id="payment_date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Amount <span class="text-danger">*</span></label>
                                            <input type="number" id="payment_amount" name="payment_amount" class="form-control" step="0.01" min="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Method <span class="text-danger">*</span></label>
                                            <select id="payment_method" name="payment_method" class="form-control">
                                                <option value="cash">Cash</option>
                                                <option value="bank">Bank</option>
                                                <option value="cheque">Cheque</option>
                                                <option value="online">Online</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Account <span class="text-danger">*</span></label>
                                            <select id="payment_account_id" name="payment_account_id" class="form-control select2" style="width: 100%;"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Cheque Number</label>
                                            <input type="text" id="cheque_number" name="cheque_number" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Cheque Date</label>
                                            <input type="date" id="cheque_date" name="cheque_date" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Bank Name</label>
                                            <input type="text" id="bank_name" name="bank_name" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea id="payment_notes" name="payment_notes" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="record_payment_now">
                                    <label class="form-check-label" for="record_payment_now">
                                        Record payment immediately after invoice creation
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="internal_notes">Internal Notes</label>
                                    <textarea id="internal_notes" name="internal_notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_notes">Customer Notes</label>
                                    <textarea id="customer_notes" name="customer_notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Invoice
                        </button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar - Customer Info & Totals -->
        <div class="col-lg-3">
            <!-- Customer Info Card -->
            <div class="card card-info" id="customerInfoCard" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">Customer Details</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong id="cust-name"></strong></p>
                    <p class="mb-2"><small><i class="fas fa-phone"></i> <span id="cust-phone"></span></small></p>
                    <p class="mb-2"><small><i class="fas fa-map-marker-alt"></i> <span id="cust-city"></span></small></p>
                    <hr>
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-warning"><i class="fas fa-balance-scale"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Outstanding</span>
                            <span class="info-box-number" id="cust-outstanding">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totals Card -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Invoice Total</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">Subtotal</div>
                        <div class="col-6 text-right"><strong id="display-subtotal">0.00</strong></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">Discount</div>
                        <div class="col-6 text-right"><strong id="display-discount-total">0.00</strong></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6"><h5>Grand Total</h5></div>
                        <div class="col-6 text-right"><h4 id="display-total" class="text-success">0.00</h4></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .select2-container--default .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px) !important;
        border: 1px solid #ced4da !important;
        padding: 0.375rem 0.75rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + 0.75rem) !important;
        padding-left: 0;
    }
    .table-sm td { padding: 0.3rem; vertical-align: middle; }
    .form-control-sm { height: calc(1.5em + 0.5rem + 2px); }
    .amount { background-color: #f8f9fa !important; font-weight: 600; }
    .alt-display { min-height: 25px; line-height: 25px; }
</style>
@endpush

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    
    // ✅ FIXED: $p not $product in JSON
    const productsData = {!! json_encode($products->mapWithKeys(function($p) {
        return [$p->id => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code ?? '',
            'base_unit_id' => $p->base_unit_id,
            'base_unit_symbol' => $p->baseUnit ? $p->baseUnit->symbol : 'pc',
            'selling_price' => $p->selling_price ?? 0,
            'stock' => $p->current_stock ?? $p->currentstock ?? 0,
            'alternative_units' => $p->alternativeUnits ? $p->alternativeUnits->map(function($u) {
                return [
                    'id' => $u->id, 'name' => $u->name, 'symbol' => $u->symbol,
                    'conversion_factor' => $u->pivot->conversion_factor,
                    'is_sales_unit' => (bool) $u->pivot->is_sales_unit
                ];
            })->toArray() : [],
        ],];
    })) !!};

    // ===== SELECT2 INIT (AdminLTE ready) =====
    function initSelect2(selector, options = {}) {
        $(selector).each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: options.placeholder || 'Select...',
                ...options
            });
        });
    }

    // ===== COMPOUND UNITS =====
    function calculateCompoundDisplay(row, baseQty) {
        const productId = row.find('.product-select').val();
        const altUnitId = row.find('.alt-unit-select').val();
        if (!productId || !altUnitId || baseQty <= 0) return;

        const product = productsData[productId];
        if (!product) return;

        const altUnit = product.alternative_units.find(u => u.id == altUnitId);
        if (!altUnit) return;

        const pcsUnit = product.alternative_units.find(u => u.symbol.toLowerCase() === 'pcs');
        const boxUnit = product.alternative_units.find(u => u.symbol.toLowerCase() === 'box');

        if (!pcsUnit || !boxUnit) {
            row.find('.alt-display').html((baseQty / altUnit.conversion_factor).toFixed(2) + ' ' + altUnit.symbol).addClass('badge-success');
            return;
        }

        // YOUR 0.8+ ROUNDING
        const totalPcsFloat = baseQty / pcsUnit.conversion_factor;
        const decimalPart = totalPcsFloat - Math.floor(totalPcsFloat);
        const totalPcs = decimalPart >= 0.8 ? Math.ceil(totalPcsFloat) : Math.floor(totalPcsFloat);

        const pcsPerBox = Math.round(boxUnit.conversion_factor / pcsUnit.conversion_factor);
        const boxes = Math.floor(totalPcs / pcsPerBox);
        const remainingPcs = totalPcs % pcsPerBox;

        let display = boxes > 0 ? `${boxes} ${boxUnit.symbol}` : '';
        if (remainingPcs > 0) display += display ? ` + ${remainingPcs} ${pcsUnit.symbol}` : `${remainingPcs} ${pcsUnit.symbol}`;

        row.find('.alt-display')
            .html(display || '0')
            .removeClass('badge-secondary')
            .addClass(boxes > 0 ? 'badge-success' : 'badge-warning');
    }

    function calculateTotals() {
        let subtotal = 0, totalDiscount = 0;
        $('#itemsTable tbody tr').each(function() {
            const qty = parseFloat($(this).find('.qty').val()) || 0;
            const rate = parseFloat($(this).find('.rate').val()) || 0;
            const discount = parseFloat($(this).find('.discount').val()) || 0;
            subtotal += qty * rate;
            totalDiscount += (qty * rate * discount / 100);
        });
        const grandTotal = subtotal - totalDiscount;
        $('#subtotal, #display-subtotal').text(subtotal.toFixed(2));
        $('#display-discount, #display-discount-total').text(totalDiscount.toFixed(2));
        $('#display-total').text(grandTotal.toFixed(2));
        $('#payment_amount').val(grandTotal.toFixed(2));
    }

    // ===== INIT CUSTOMER SELECT2 (SINGLE HANDLER) =====
    initSelect2('#customer_id', {
        ajax: {
            url: '{{ route("sales.search-customers") }}',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: data => ({
                results: data.results || [],
                pagination: { more: data.pagination?.more || false }
            })
        },
        placeholder: 'Search customer...',
        minimumInputLength: 0,
        templateResult: item => item.customercode ? 
            `${item.customercode} - ${item.name}<br><small>${item.phone}</small>` : item.text
    });

    // ✅ SINGLE CUSTOMER HANDLER (REMOVED DUPLICATE)
    $('#customer_id').on('select2:select', function(e) {
        const customerId = e.params.data.id;
        if (customerId) {
            $.get(`/sales/customer/${customerId}`).done(response => {
                if (response.success && response.data) {
                    $('#cust-name').text(response.data.name);
                    $('#cust-phone').text(response.data.phone || '');
                    $('#cust-city').text(response.data.city || '');
                    $('#cust-outstanding').text(parseFloat(response.data.outstandingbalance || 0).toFixed(2));
                    $('#customerInfoCard').slideDown();
                }
            }).fail(() => $('#customerInfoCard').slideUp());
        }
    });

    // Sales account
    initSelect2('#sales_account_id', { placeholder: 'Default Sales (4100)' });

    // Product selects
    initSelect2('.product-select', {
        ajax: {
            url: '{{ route("sales.search-products") }}',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: data => ({ results: data.results || [], pagination: { more: false } })
        },
        placeholder: 'Search products...',
        minimumInputLength: 0,
        templateResult: item => {
            if (!item.data) return item.text;
            return `<strong>${item.data.name}</strong> ${item.data.code}<br>
                    <small>Stock: ${item.data.stock} ${item.data.unit} | ৳${item.data.price}</small>`;
        }
    });

    // ===== EVENTS =====
    $(document).on('select2:select', '.product-select', function() {
        const row = $(this).closest('tr'), productId = $(this).val(), product = productsData[productId];
        if (product) {
            row.find('.base-unit-display').text(product.base_unit_symbol);
            const altSelect = row.find('.alt-unit-select');
            altSelect.html('<option value="">Base Unit</option>');
            (product.alternative_units || []).forEach(unit => {
                if (unit.is_sales_unit) altSelect.append(`<option value="${unit.id}">${unit.symbol}</option>`);
            });
            initSelect2(altSelect);
            row.find('.rate').val((product.selling_price || 0).toFixed(2));
        }
    });

    $(document).on('input change', '.qty, .rate, .discount, .alt-unit-select', function() {
        const row = $(this).closest('tr');
        calculateCompoundDisplay(row, parseFloat(row.find('.qty').val()) || 0);
        calculateRowAmount(row);
    });

    $(document).on('click', '.add-item', function() {
        const firstRow = $('#itemsTable tbody tr:first').clone(true);
        firstRow.find('input,select').val('').end().find('.discount').val('0');
        firstRow.find('.base-unit-display,.alt-display').empty().removeClass('badge-success badge-warning badge-info');
        firstRow.find('[name*="items[0]"]').each(function() {
            $(this).attr('name', $(this).attr('name').replace('items[0]', `items[${rowIndex}]`));
        });
        $('#itemsTable tbody').append(firstRow);
        initSelect2('.product-select, .alt-unit-select');
        rowIndex++;
    });

    $(document).on('click', '.remove-item', function() {
        if ($('#itemsTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });

    $('#record_payment_now').change(function() {
        $('#paymentPanel').toggle(this.checked);
        if (this.checked) loadPaymentAccounts();
    });

    function loadPaymentAccounts() {
        $.get('{{ route("payments.create") }}').done(res => {
            const $select = $('#payment_account_id');
            $select.empty();
            (res.accounts || []).forEach(acc => $select.append(`<option value="${acc.id}">${acc.code}-${acc.name}</option>`));
            initSelect2('#payment_account_id');
        });
    }

    $('#invoiceForm').on('submit', function(e) {
        e.preventDefault();
        if (!$('#customer_id').val() || !$('#itemsTable tbody tr .qty').first().val()) {
            return Swal.fire('Error', 'Add customer & products', 'warning');
        }

        Swal.fire({ title: 'Creating...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        $.post('{{ route("sales.store") }}', $(this).serialize())
            .done(res => {
                Swal.close();
                if (res.success) {
                    Swal.fire('Success!', res.message, 'success', 2000).then(() => {
                        if ($('#record_payment_now:checked').length && res.invoice_id) {
                            recordPaymentImmediately(res.invoice_id);
                        } else {
                            window.location.href = res.redirect || '/sales';
                        }
                    });
                }
            })
            .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });

function recordPaymentImmediately(invoiceId) {
    const paymentData = {
        invoice_id: invoiceId,
        payment_date: $('#payment_date').val() || '{{ date("Y-m-d") }}',
        amount: $('#payment_amount').val(),
        payment_method: $('#payment_method').val(),
        account_id: $('#payment_account_id').val(),
        cheque_number: $('#cheque_number').val() || '',
        cheque_date: $('#cheque_date').val() || '',
        bank_name: $('#bank_name').val() || '',
        notes: $('#payment_notes').val() || '',
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    // Validation
    if (!paymentData.amount || parseFloat(paymentData.amount) <= 0) {
        Swal.fire('Error', 'Payment amount required', 'error');
        return;
    }
    if (!paymentData.account_id) {
        Swal.fire('Error', 'Please select payment account', 'error');
        return;
    }

    Swal.fire({
        title: 'Recording Payment...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: '{{ route("payments.store") }}',
        method: 'POST',
        data: paymentData,
        success: function(paymentResponse) {
            Swal.fire({
                icon: 'success',
                title: 'Complete!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h4>Invoice & Payment Created!</h4>
                        <p>${paymentResponse.message || 'Payment recorded successfully'}</p>
                    </div>
                `,
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = `/sales/${invoiceId}`;
            });
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Payment Failed',
                text: xhr.responseJSON?.message || 'Payment recording failed. Invoice created successfully.',
                confirmButtonText: 'View Invoice'
            }).then(() => {
                window.location.href = `/sales/${invoiceId}`;
            });
        }
    });
    }


    calculateTotals();
});

function calculateRowAmount(row) {
    const qty = parseFloat(row.find('.qty').val()) || 0;
    const rate = parseFloat(row.find('.rate').val()) || 0;
    const discountPercent = parseFloat(row.find('.discount').val()) || 0;

    const lineTotal = qty * rate;
    const discountAmount = lineTotal * (discountPercent / 100);
    const finalAmount = lineTotal - discountAmount;

    row.find('.amount').val(finalAmount > 0 ? finalAmount.toFixed(2) : '');
    calculateTotals();
}
</script>
@endpush
