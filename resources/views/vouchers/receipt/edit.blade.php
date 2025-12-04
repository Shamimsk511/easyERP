@extends('adminlte::page')

@section('title', 'Edit Receipt Voucher')

@section('content_header')
    <h1>Edit Receipt Voucher</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Receipt Information</h3>
                </div>
                <form id="receiptForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Voucher Number</label>
                                    <input type="text" class="form-control" value="{{ $receiptVoucher->voucher_number }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Receipt Date <span class="text-danger">*</span></label>
                                    <input type="date" name="receipt_date" class="form-control" value="{{ $receiptVoucher->receipt_date->format('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Customer <span class="text-danger">*</span></label>
            <select name="customer_id" id="customerId" class="form-control select2-customer" required style="width: 100%;">
                <option value="{{ $receiptVoucher->customer_id }}" selected>
                    {{ $receiptVoucher->customer->customer_code }} - {{ $receiptVoucher->customer->name }}
                </option>
            </select>
            <small class="text-muted">Start typing customer name, code, or phone number</small>
        </div>
    </div>
</div>


                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Received In Account <span class="text-danger">*</span></label>
                                    <select name="received_in_account_id" class="form-control select2" required>
                                        <option value="">Select Cash/Bank Account</option>
                                        @foreach($receiptAccounts as $account)
                                            <option value="{{ $account->id }}" {{ $receiptVoucher->received_in_account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" value="{{ $receiptVoucher->amount }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="paymentMethod" class="form-control select2" required>
                                        <option value="cash" {{ $receiptVoucher->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="bank" {{ $receiptVoucher->payment_method == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="cheque" {{ $receiptVoucher->payment_method == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        <option value="mobile_banking" {{ $receiptVoucher->payment_method == 'mobile_banking' ? 'selected' : '' }}>Mobile Banking</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control select2" required>
                                        <option value="posted" {{ $receiptVoucher->status == 'posted' ? 'selected' : '' }}>Posted</option>
                                        <option value="draft" {{ $receiptVoucher->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Cheque Details (conditional) -->
                        <div id="chequeDetails" style="display: {{ $receiptVoucher->payment_method == 'cheque' ? 'block' : 'none' }};">
                            <hr>
                            <h5>Cheque Details</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Cheque Number</label>
                                        <input type="text" name="cheque_number" class="form-control" value="{{ $receiptVoucher->cheque_number }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Cheque Date</label>
                                        <input type="date" name="cheque_date" class="form-control" value="{{ $receiptVoucher->cheque_date ? $receiptVoucher->cheque_date->format('Y-m-d') : '' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control" value="{{ $receiptVoucher->bank_name }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Description <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="2" required>{{ $receiptVoucher->description }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ $receiptVoucher->notes }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Receipt Voucher
                        </button>
                        <a href="{{ route('vouchers.receipt.show', $receiptVoucher->id) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="{{ route('vouchers.receipt.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customer Details Sidebar -->
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Customer Details</h3>
                </div>
                <div class="card-body" id="customerDetailsContent">
                    <p class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
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
    line-height: 38px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}
.select2-container--open .select2-dropdown {
    border: 1px solid #ced4da;
}
.select2-results__option {
    border-bottom: 1px solid #f0f0f0;
    padding: 8px;
}
.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--open .select2-selection--single {
    outline: none;
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
    padding: 6px;
}
.select2-search--dropdown .select2-search__field:focus {
    outline: none;
    border-color: #80bdff;
    caret-color: auto;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2 with enhanced focus
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select an option'
    });

    // Ensure cursor is ready when Select2 opens
    $('.select2').on('select2:open', function(e) {
        setTimeout(function() {
            $('.select2-search__field').focus();
        }, 100);
    });

    // Show/hide cheque details
    $('#paymentMethod').on('change', function() {
        if ($(this).val() === 'cheque') {
            $('#chequeDetails').slideDown();
        } else {
            $('#chequeDetails').slideUp();
        }
    });

    // Customer selection - Load details
    $('#customerId').on('change', function() {
        var customerId = $(this).val();
        
        if (!customerId) {
            $('#customerDetailsContent').html('<p class="text-center text-muted">Select a customer to view details</p>');
            return;
        }

        $('#customerDetailsContent').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');

        $.ajax({
            url: '/vouchers/receipt/ajax/customer/' + customerId,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = `
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Code:</strong><br>
                                <span class="badge badge-info">${data.customer_code}</span>
                            </li>
                            <li class="list-group-item">
                                <strong>Phone:</strong><br>
                                ${data.phone || 'N/A'}
                            </li>
                            <li class="list-group-item">
                                <strong>Email:</strong><br>
                                ${data.email || 'N/A'}
                            </li>
                            <li class="list-group-item">
                                <strong>Address:</strong><br>
                                ${data.address || 'N/A'}
                            </li>
                            <li class="list-group-item">
                                <strong>Current Balance:</strong><br>
                                <h4 class="${data.balance_class}">
                                    ${data.current_balance_formatted} ${data.balance_type}
                                </h4>
                            </li>
                            <li class="list-group-item">
                                <strong>Credit Limit:</strong> ${data.credit_limit}<br>
                                <strong>Credit Period:</strong> ${data.credit_period_days} days
                            </li>
                    `;

                    if (data.is_overdue) {
                        html += `
                            <li class="list-group-item bg-danger">
                                <strong><i class="fas fa-exclamation-triangle"></i> OVERDUE!</strong><br>
                                Due Date: ${data.current_due_date}
                            </li>
                        `;
                    }

                    if (data.outstanding_invoices && data.outstanding_invoices.length > 0) {
                        html += `
                            <li class="list-group-item">
                                <strong>Outstanding Invoices:</strong>
                                <table class="table table-sm mt-2">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.outstanding_invoices.forEach(function(invoice) {
                            var overdueClass = invoice.is_overdue ? 'text-danger' : '';
                            html += `
                                <tr class="${overdueClass}">
                                    <td>
                                        <small>${invoice.voucher_number}</small><br>
                                        <small class="text-muted">${invoice.date}</small>
                                    </td>
                                    <td class="text-right">
                                        <strong>${invoice.balance}</strong>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </li>
                        `;
                    }

                    html += '</ul>';
                    $('#customerDetailsContent').html(html);
                }
            },
            error: function(xhr) {
                $('#customerDetailsContent').html(
                    '<p class="text-center text-danger">Error loading customer details</p>'
                );
            }
        });
    });

    // Trigger customer details on load
    $('#customerId').trigger('change');

    // Form submission
    $('#receiptForm').on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: '{{ route('vouchers.receipt.update', $receiptVoucher->id) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000
                }).then(() => {
                    window.location.href = '{{ route('vouchers.receipt.show', $receiptVoucher->id) }}';
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Receipt Voucher');
                
                var errors = xhr.responseJSON?.errors;
                var errorMessage = '';
                
                if (errors) {
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                } else {
                    errorMessage = xhr.responseJSON?.message || 'An error occurred';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            }
        });
    });
});
</script>
@stop
