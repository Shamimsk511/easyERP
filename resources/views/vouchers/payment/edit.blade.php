@extends('adminlte::page')

@section('title', 'Edit Payment Voucher')

@section('content_header')
    <h1>Edit Payment Voucher</h1>
@stop

@section('content')
<form id="payment-voucher-form" method="POST" action="{{ route('vouchers.payment.update', $paymentVoucher->id) }}">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="voucher_number">Voucher Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="voucher_number" 
                                       value="{{ $paymentVoucher->voucher_number }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                       id="payment_date" name="payment_date" 
                                       value="{{ old('payment_date', $paymentVoucher->payment_date->format('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">Active</label>
    </div>
    <small class="form-text text-muted">Inactive vendors won't appear in selection lists</small>
</div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control @error('payment_method') is-invalid @enderror" 
                                        id="payment_method" name="payment_method" required>
                                    <option value="cash" {{ old('payment_method', $paymentVoucher->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank" {{ old('payment_method', $paymentVoucher->payment_method) == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cheque" {{ old('payment_method', $paymentVoucher->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="mobile_banking" {{ old('payment_method', $paymentVoucher->payment_method) == 'mobile_banking' ? 'selected' : '' }}>Mobile Banking</option>
                                </select>
                                @error('payment_method')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">৳</span>
                                    </div>
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                           id="amount" name="amount" value="{{ old('amount', $paymentVoucher->amount) }}" 
                                           step="0.01" min="0.01" required>
                                    @error('amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cheque Details -->
                    <div id="cheque-details" style="display: none;">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cheque_number">Cheque Number</label>
                                    <input type="text" class="form-control" id="cheque_number" name="cheque_number" 
                                           value="{{ old('cheque_number', $paymentVoucher->cheque_number) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cheque_date">Cheque Date</label>
                                    <input type="date" class="form-control" id="cheque_date" name="cheque_date" 
                                           value="{{ old('cheque_date', $paymentVoucher->cheque_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bank_name">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                           value="{{ old('bank_name', $paymentVoucher->bank_name) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Payee Selection -->
                    <h5>Payee Information</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payee_type">Payee Type</label>
                                <select class="form-control" id="payee_type" name="payee_type">
                                    <option value="">Select Type</option>
                                    <option value="vendor" {{ old('payee_type', $paymentVoucher->payee_type) == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ old('payee_type', $paymentVoucher->payee_type) == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="employee" {{ old('payee_type', $paymentVoucher->payee_type) == 'employee' ? 'selected' : '' }}>Employee</option>
                                    <option value="other" {{ old('payee_type', $paymentVoucher->payee_type) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group" id="vendor-selection" style="display: none;">
                                <label for="vendor_id">Select Vendor</label>
                                <select class="form-control select2" id="vendor_id" name="vendor_id_temp">
                                    <option value="">-- Select Vendor --</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" 
                                                data-account-id="{{ $vendor->ledger_account_id }}"
                                                {{ old('payee_id', $paymentVoucher->payee_id) == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" id="customer-selection" style="display: none;">
                                <label for="customer_id">Select Customer</label>
                                <select class="form-control select2" id="customer_id" name="customer_id_temp">
                                    <option value="">-- Select Customer --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                                data-account-id="{{ $customer->ledger_account_id }}"
                                                {{ old('payee_id', $paymentVoucher->payee_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="payee_id" name="payee_id" value="{{ old('payee_id', $paymentVoucher->payee_id) }}">

                    <hr>

                    <!-- Account Selection -->
                    <h5>Account Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paid_from_account_id">Paid From (Source Account) <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('paid_from_account_id') is-invalid @enderror" 
                                        id="paid_from_account_id" name="paid_from_account_id" required>
                                    <option value="">-- Select Account --</option>
                                    @foreach($paymentAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('paid_from_account_id', $paymentVoucher->paid_from_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('paid_from_account_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Cash/Bank account to pay from</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paid_to_account_id">Paid To (Destination Account) <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('paid_to_account_id') is-invalid @enderror" 
                                        id="paid_to_account_id" name="paid_to_account_id" required>
                                    <option value="">-- Select Account --</option>
                                    @foreach($allAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('paid_to_account_id', $paymentVoucher->paid_to_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('paid_to_account_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Vendor/Expense account</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="2" required>{{ old('description', $paymentVoucher->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $paymentVoucher->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">Transaction Summary</h3>
                </div>
                <div class="card-body">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-danger"><i class="fas fa-minus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Debit (Dr.)</span>
                            <span class="info-box-number" id="summary-debit-account">-</span>
                            <small class="text-muted">Paid To Account</small>
                        </div>
                    </div>

                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-success"><i class="fas fa-plus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Credit (Cr.)</span>
                            <span class="info-box-number" id="summary-credit-account">-</span>
                            <small class="text-muted">Paid From Account</small>
                        </div>
                    </div>

                    <hr>

                    <div class="text-center">
                        <h4>Total Amount</h4>
                        <h2 class="text-primary" id="summary-total-amount">৳ 0.00</h2>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="status">Voucher Status <span class="text-danger">*</span></label>
                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="draft" {{ old('status', $paymentVoucher->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ old('status', $paymentVoucher->status) == 'posted' ? 'selected' : '' }}>Posted</option>
                        </select>
                        @error('status')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-save"></i> Update Payment Voucher
                    </button>
                    <a href="{{ route('vouchers.payment.show', $paymentVoucher->id) }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@stop

@section('css')
<style>
    .info-box {
        min-height: 80px;
        margin-bottom: 15px;
    }
    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 5px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });

    // Show/hide cheque details
    $('#payment_method').on('change', function() {
        if ($(this).val() === 'cheque') {
            $('#cheque-details').slideDown();
        } else {
            $('#cheque-details').slideUp();
        }
    });

    // Trigger on load
    if ($('#payment_method').val() === 'cheque') {
        $('#cheque-details').show();
    }

    // Show/hide payee selection
    $('#payee_type').on('change', function() {
        const type = $(this).val();
        $('#vendor-selection, #customer-selection').hide();
        
        if (type === 'vendor') {
            $('#vendor-selection').show();
        } else if (type === 'customer') {
            $('#customer-selection').show();
        }
    });

    // Trigger on load
    const initialPayeeType = $('#payee_type').val();
    if (initialPayeeType === 'vendor') {
        $('#vendor-selection').show();
    } else if (initialPayeeType === 'customer') {
        $('#customer-selection').show();
    }

    // Vendor change
    $('#vendor_id').on('change', function() {
        const vendorId = $(this).val();
        const accountId = $(this).find(':selected').data('account-id');
        $('#payee_id').val(vendorId);
        if (accountId) {
            $('#paid_to_account_id').val(accountId).trigger('change');
        }
        updateSummary();
    });

    // Customer change
    $('#customer_id').on('change', function() {
        const customerId = $(this).val();
        const accountId = $(this).find(':selected').data('account-id');
        $('#payee_id').val(customerId);
        if (accountId) {
            $('#paid_to_account_id').val(accountId).trigger('change');
        }
        updateSummary();
    });

    // Update summary
    $('#paid_from_account_id, #paid_to_account_id, #amount').on('change keyup', function() {
        updateSummary();
    });

    function updateSummary() {
        const paidFromText = $('#paid_from_account_id option:selected').text() || '-';
        const paidToText = $('#paid_to_account_id option:selected').text() || '-';
        const amount = parseFloat($('#amount').val()) || 0;
        
        $('#summary-credit-account').text(paidFromText);
        $('#summary-debit-account').text(paidToText);
        $('#summary-total-amount').text('৳ ' + amount.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    // Initial update
    updateSummary();

    // Form validation
    $('#payment-voucher-form').on('submit', function(e) {
        const paidFrom = $('#paid_from_account_id').val();
        const paidTo = $('#paid_to_account_id').val();
        
        if (paidFrom === paidTo) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Selection',
                text: 'Source and destination accounts must be different!'
            });
            return false;
        }
    });
});
</script>
@stop
