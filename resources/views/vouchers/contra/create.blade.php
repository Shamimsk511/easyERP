@extends('adminlte::page')

@section('title', 'Create Contra Voucher')

@section('content_header')
    <h1>Create Contra Voucher</h1>
@stop

@section('content')
<form id="contra-voucher-form">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title">Voucher Details</h3>
                </div>
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
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="contra_date" id="contra_date" 
                                       class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>From Account (Source) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="from_account_id" id="from_account_id" 
                                            class="form-control select2-account" style="width: 100%;" required>
                                        <option value="">Type to search account...</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info btn-sm" 
                                                data-toggle="modal" data-target="#quickCreateAccountModal"
                                                data-target-field="from_account_id" 
                                                title="Create New Account (Alt+C)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Cash/Bank account to transfer from</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>To Account (Destination) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="to_account_id" id="to_account_id" 
                                            class="form-control select2-account" style="width: 100%;" required>
                                        <option value="">Type to search account...</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info btn-sm" 
                                                data-toggle="modal" data-target="#quickCreateAccountModal"
                                                data-target-field="to_account_id" 
                                                title="Create New Account (Alt+C)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Cash/Bank account to transfer to</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">৳</span>
                                    </div>
                                    <input type="number" name="amount" id="amount" 
                                           class="form-control" step="0.01" min="0.01" 
                                           placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Transfer Method <span class="text-danger">*</span></label>
                                <select name="transfer_method" id="transfer_method" 
                                        class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online">Online Transfer</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Conditional fields -->
                    <div id="cheque-fields" style="display: none;">
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

                    <div id="reference-field" style="display: none;">
                        <div class="form-group">
                            <label>Reference/Transaction Number</label>
                            <input type="text" name="reference_number" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" 
                                  class="form-control" rows="2" 
                                  placeholder="Purpose of transfer..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" 
                                  rows="2" placeholder="Additional notes (optional)"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">Transaction Summary</h3>
                </div>
                <div class="card-body">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-danger"><i class="fas fa-minus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">From (Credit)</span>
                            <span class="info-box-number" id="summary-from" style="font-size: 14px;">-</span>
                        </div>
                    </div>

                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-success"><i class="fas fa-plus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">To (Debit)</span>
                            <span class="info-box-number" id="summary-to" style="font-size: 14px;">-</span>
                        </div>
                    </div>

                    <hr>

                    <div class="text-center">
                        <h4>Total Amount</h4>
                        <h2 class="text-primary" id="summary-amount">৳ 0.00</h2>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Voucher Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="draft">Save as Draft</option>
                            <option value="posted" selected>Post Entry</option>
                        </select>
                        <small class="form-text text-muted">
                            Draft entries can be edited later
                        </small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-success btn-block" id="submit-btn">
                        <i class="fas fa-save"></i> Save Contra Voucher
                    </button>
                    <a href="{{ route('vouchers.contra.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Quick Create Account Modal -->
<div class="modal fade" id="quickCreateAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Quick Create Account</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="quick-create-form">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="quick_account_name" 
                               class="form-control" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Account Type <span class="text-danger">*</span></label>
                        <select name="account_type" class="form-control" required>
                            <option value="cash">Cash Account</option>
                            <option value="bank">Bank Account</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Opening Balance</label>
                        <input type="number" name="opening_balance" 
                               class="form-control" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create & Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.select2-container--default .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
.select2-container--default .select2-results__option {
    border-bottom: 1px solid #e9ecef;
    padding: 8px;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    var targetField = null;

    // Initialize Select2 with AJAX
    $('.select2-account').select2({
        theme: 'bootstrap4',
        placeholder: 'Type to search account...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: '{{ route("vouchers.contra.ajax.search-accounts") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.results,
                    pagination: data.pagination
                };
            },
            cache: true
        }
    }).on('select2:open', function() {
        setTimeout(function() {
            $('.select2-search__field').focus();
        }, 100);
    });

    // Transfer method change
    $('#transfer_method').change(function() {
        var method = $(this).val();
        $('#cheque-fields, #reference-field').hide();

        if (method === 'cheque') {
            $('#cheque-fields').show();
        } else if (method === 'bank_transfer' || method === 'online') {
            $('#reference-field').show();
        }
    });

    // Update summary
    function updateSummary() {
        var fromText = $('#from_account_id').select2('data')[0]?.text || '-';
        var toText = $('#to_account_id').select2('data')[0]?.text || '-';
        var amount = parseFloat($('#amount').val()) || 0;

        $('#summary-from').text(fromText);
        $('#summary-to').text(toText);
        $('#summary-amount').text('৳ ' + amount.toLocaleString('en-BD', {minimumFractionDigits: 2}));
    }

    $('#from_account_id, #to_account_id').on('change', updateSummary);
    $('#amount').on('keyup change', updateSummary);

    // Quick Create Modal
    $('[data-target="#quickCreateAccountModal"]').click(function() {
        targetField = $(this).data('target-field');
        $('#quick_account_name').focus();
    });

    $('#quick-create-form').submit(function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

        $.ajax({
            url: '{{ route("vouchers.contra.ajax.quick-create-account") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    var newOption = new Option(
                        response.data.code + ' - ' + response.data.name,
                        response.data.id,
                        true,
                        true
                    );
                    
                    $('#' + targetField).append(newOption).trigger('change');
                    $('#quickCreateAccountModal').modal('hide');
                    $('#quick-create-form')[0].reset();
                    
                    Swal.fire('Success!', response.message, 'success');
                    updateSummary();
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Create & Select');
            }
        });
    });

    // Main Form Submit
    $('#contra-voucher-form').submit(function(e) {
        e.preventDefault();

        var fromId = $('#from_account_id').val();
        var toId = $('#to_account_id').val();

        if (!fromId || !toId) {
            Swal.fire('Error', 'Please select both accounts', 'error');
            return;
        }

        if (fromId === toId) {
            Swal.fire('Error', 'Source and destination accounts must be different!', 'error');
            return;
        }

        var submitBtn = $('#submit-btn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route("vouchers.contra.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'View Voucher'
                    }).then((result) => {
                        window.location.href = '/vouchers/contra/' + response.data.id;
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Contra Voucher');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire('Error!', message, 'error');
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Contra Voucher');
            }
        });
    });

    // Keyboard shortcuts
    $(document).keydown(function(e) {
        if (e.altKey && e.keyCode === 83) { // Alt+S
            e.preventDefault();
            $('#contra-voucher-form').submit();
        }
        if (e.altKey && e.keyCode === 67) { // Alt+C
            e.preventDefault();
            $('#quickCreateAccountModal').modal('show');
        }
    });
});
</script>
@stop
