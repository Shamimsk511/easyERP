@extends('adminlte::page')

@section('title', 'Create Receipt Voucher')

@section('content_header')
    <h1>Create Receipt Voucher</h1>
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
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Voucher Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" value="{{ $voucherNumber }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Receipt Date <span class="text-danger">*</span></label>
                                    <input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label>Customer <span class="text-danger">*</span></label>
            <select name="customer_id" id="customerId" class="form-control select2-ajax" required style="width: 100%;">
                @if($preselectedCustomer)
                    <option value="{{ $preselectedCustomer->id }}" selected>
                        {{ $preselectedCustomer->customer_code }} - {{ $preselectedCustomer->name }} | {{ $preselectedCustomer->phone }}
                    </option>
                @endif
            </select>
            <small class="text-muted">Search by customer name, code, or phone number</small>
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
                                            <option value="{{ $account->id }}">
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="paymentMethod" class="form-control select2" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="mobile_banking">Mobile Banking</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control select2" required>
                                        <option value="posted" selected>Posted</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Cheque Details (conditional) -->
                        <div id="chequeDetails" style="display: none;">
                            <hr>
                            <h5>Cheque Details</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Cheque Number</label>
                                        <input type="text" name="cheque_number" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Cheque Date</label>
                                        <input type="date" name="cheque_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Description <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="2" required placeholder="e.g., Payment received against invoice"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Receipt Voucher
                        </button>
                        <a href="{{ route('vouchers.receipt.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customer Details Sidebar -->
        <div class="col-md-4">
            <div class="card card-info" id="customerDetailsCard" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Customer Details</h3>
                </div>
                <div class="card-body" id="customerDetailsContent">
                    <p class="text-center text-muted">Select a customer to view details</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
/* Select2 Container Styling */
.select2-container .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 12px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}

/* Dropdown Styling with proper borders */
.select2-dropdown {
    border: 1px solid #ced4da !important;
    z-index: 9999 !important;
}

.select2-results__option {
    border-bottom: 1px solid #e9ecef !important;
    padding: 8px 12px !important;
}

/* Focus State */
.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--open .select2-selection--single {
    outline: none !important;
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
}

/* Search Field with cursor ready */
.select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da !important;
    padding: 8px !important;
    width: 100% !important;
}

.select2-search--dropdown .select2-search__field:focus {
    outline: none !important;
    border-color: #80bdff !important;
    caret-color: auto !important;
}

/* Highlighted option */
.select2-results__option--highlighted[aria-selected] {
    background-color: #007bff !important;
    color: white !important;
}

/* Ensure dropdown appears */
.select2-container--open .select2-dropdown--below {
    border-top: none;
}

.select2-container--open .select2-dropdown--above {
    border-bottom: none;
}
</style>
@stop


@section('js')
<script>
$(document).ready(function() {
    // Initialize regular Select2 (non-AJAX for simple dropdowns)
    $('.select2').not('.select2-ajax').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });

    // Initialize AJAX Select2 for Customer Search (with phone number)
    $('#customerId').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Search customer by name, code, or phone...',
        allowClear: true,
        ajax: {
            url: '{{ route("vouchers.receipt.ajax.search-customers") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 0, // Show results immediately
        templateResult: formatCustomer,
        templateSelection: formatCustomerSelection
    });

    // Format customer display in dropdown
    function formatCustomer(customer) {
        if (customer.loading) {
            return customer.text;
        }

        var $container = $(
            '<div class="select2-result-customer clearfix" style="padding: 5px; border-bottom: 1px solid #e9ecef;">' +
                '<div class="select2-result-customer__meta">' +
                    '<div class="select2-result-customer__title" style="font-weight: bold; color: #007bff;"></div>' +
                    '<div class="select2-result-customer__description" style="font-size: 0.9em; color: #6c757d;"></div>' +
                '</div>' +
            '</div>'
        );

        $container.find('.select2-result-customer__title').text(customer.customer_code + ' - ' + customer.name);
        $container.find('.select2-result-customer__description').text('Phone: ' + customer.phone + ' | Balance: ' + customer.balance);

        return $container;
    }

    // Format selected customer
    function formatCustomerSelection(customer) {
        return customer.text || customer.customer_code + ' - ' + customer.name;
    }

    // Ensure cursor is ready when Select2 opens
    $(document).on('select2:open', '.select2, .select2-ajax', function(e) {
        setTimeout(function() {
            const searchField = document.querySelector('.select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 50);
    });

    // Show/hide cheque details
    $('#paymentMethod').on('change', function() {
        if ($(this).val() === 'cheque') {
            $('#chequeDetails').slideDown();
        } else {
            $('#chequeDetails').slideUp();
        }
    });

    // Customer selection - Load details via AJAX
    $('#customerId').on('select2:select', function(e) {
        const customerId = e.params.data.id;
        
        if (!customerId) {
            $('#customerDetailsCard').slideUp();
            return;
        }

        // Show loading state
        $('#customerDetailsContent').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');
        $('#customerDetailsCard').slideDown();

        // Fetch customer details via AJAX
        $.ajax({
            url: '{{ route("vouchers.receipt.ajax.customer-details", ":id") }}'.replace(':id', customerId),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = `
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
                                <strong>Group:</strong><br>
                                ${data.group}
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
                            <li class="list-group-item bg-danger text-white">
                                <strong><i class="fas fa-exclamation-triangle"></i> OVERDUE!</strong><br>
                                Due Date: ${data.current_due_date}
                            </li>
                        `;
                    }

                    if (data.outstanding_invoices && data.outstanding_invoices.length > 0) {
                        html += `
                            <li class="list-group-item">
                                <strong>Outstanding Invoices:</strong>
                                <table class="table table-sm mt-2 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th class="text-right">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.outstanding_invoices.forEach(function(invoice) {
                            const overdueClass = invoice.is_overdue ? 'text-danger font-weight-bold' : '';
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
                console.error('Customer details error:', xhr);
                $('#customerDetailsContent').html(
                    '<div class="alert alert-danger m-2">Error loading customer details</div>'
                );
            }
        });
    });

    // Trigger customer details if preselected
    @if($preselectedCustomer)
        setTimeout(function() {
            $('#customerId').trigger('select2:select', {
                params: {
                    data: {
                        id: {{ $preselectedCustomer->id }}
                    }
                }
            });
        }, 500);
    @endif

    // Form submission with validation
    $('#receiptForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route('vouchers.receipt.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route('vouchers.receipt.index') }}';
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Receipt Voucher');
                
                let errorMessage = '';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else {
                    errorMessage = 'An error occurred while saving the receipt voucher.';
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
