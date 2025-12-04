@extends('adminlte::page')

@section('title', 'Edit Contra Voucher')

@section('content_header')
    <h1>Edit Contra Voucher</h1>
@stop

@section('content')
<form id="contra-voucher-form">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title">Edit Voucher Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Voucher Number</label>
                                <input type="text" class="form-control" value="{{ $contraVoucher->voucher_number }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="contra_date" id="contra_date" 
                                       class="form-control" value="{{ $contraVoucher->contra_date->format('Y-m-d') }}" required>
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
                                        <option value="{{ $contraVoucher->from_account_id }}" selected>
                                            {{ $contraVoucher->fromAccount->code }} - {{ $contraVoucher->fromAccount->name }}
                                        </option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info btn-sm" 
                                                data-toggle="modal" data-target="#quickCreateAccountModal"
                                                data-target-field="from_account_id">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>To Account (Destination) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="to_account_id" id="to_account_id" 
                                            class="form-control select2-account" style="width: 100%;" required>
                                        <option value="{{ $contraVoucher->to_account_id }}" selected>
                                            {{ $contraVoucher->toAccount->code }} - {{ $contraVoucher->toAccount->name }}
                                        </option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info btn-sm" 
                                                data-toggle="modal" data-target="#quickCreateAccountModal"
                                                data-target-field="to_account_id">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
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
                                           value="{{ $contraVoucher->amount }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Transfer Method <span class="text-danger">*</span></label>
                                <select name="transfer_method" id="transfer_method" 
                                        class="form-control" required>
                                    <option value="cash" {{ $contraVoucher->transfer_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ $contraVoucher->transfer_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cheque" {{ $contraVoucher->transfer_method == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="online" {{ $contraVoucher->transfer_method == 'online' ? 'selected' : '' }}>Online Transfer</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="cheque-fields" style="display: {{ $contraVoucher->transfer_method == 'cheque' ? 'block' : 'none' }};">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cheque Number</label>
                                    <input type="text" name="cheque_number" 
                                           class="form-control" value="{{ $contraVoucher->cheque_number }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cheque Date</label>
                                    <input type="date" name="cheque_date" 
                                           class="form-control" value="{{ $contraVoucher->cheque_date?->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" 
                                           class="form-control" value="{{ $contraVoucher->bank_name }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="reference-field" style="display: {{ in_array($contraVoucher->transfer_method, ['bank_transfer', 'online']) ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label>Reference/Transaction Number</label>
                            <input type="text" name="reference_number" 
                                   class="form-control" value="{{ $contraVoucher->reference_number }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" 
                                  class="form-control" rows="2" required>{{ $contraVoucher->description }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" 
                                  rows="2">{{ $contraVoucher->notes }}</textarea>
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
                            <span class="info-box-number" id="summary-from" style="font-size: 14px;">{{ $contraVoucher->fromAccount->name }}</span>
                        </div>
                    </div>

                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-success"><i class="fas fa-plus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">To (Debit)</span>
                            <span class="info-box-number" id="summary-to" style="font-size: 14px;">{{ $contraVoucher->toAccount->name }}</span>
                        </div>
                    </div>

                    <hr>

                    <div class="text-center">
                        <h4>Total Amount</h4>
                        <h2 class="text-primary" id="summary-amount">৳ {{ number_format($contraVoucher->amount, 2) }}</h2>
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
                            <option value="draft" {{ $contraVoucher->status == 'draft' ? 'selected' : '' }}>Save as Draft</option>
                            <option value="posted" {{ $contraVoucher->status == 'posted' ? 'selected' : '' }}>Post Entry</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-warning btn-block" id="submit-btn">
                        <i class="fas fa-save"></i> Update Voucher
                    </button>
                    <a href="{{ route('vouchers.contra.show', $contraVoucher->id) }}" class="btn btn-secondary btn-block">
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
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
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
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    var targetField = null;

    $('.select2-account').select2({
        theme: 'bootstrap4',
        placeholder: 'Type to search...',
        allowClear: true,
        ajax: {
            url: '{{ route("vouchers.contra.ajax.search-accounts") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term, page: params.page || 1 };
            },
            processResults: function (data) {
                return { results: data.results, pagination: data.pagination };
            }
        }
    });

    $('#transfer_method').change(function() {
        var method = $(this).val();
        $('#cheque-fields, #reference-field').hide();
        if (method === 'cheque') $('#cheque-fields').show();
        else if (method === 'bank_transfer' || method === 'online') $('#reference-field').show();
    });

    function updateSummary() {
        var fromText = $('#from_account_id').select2('data')[0]?.text || '-';
        var toText = $('#to_account_id').select2('data')[0]?.text || '-';
        var amount = parseFloat($('#amount').val()) || 0;

        $('#summary-from').text(fromText);
        $('#summary-to').text(toText);
        $('#summary-amount').text('৳ ' + amount.toLocaleString('en-BD', {minimumFractionDigits: 2}));
    }

    $('#from_account_id, #to_account_id, #amount').on('change keyup', updateSummary);

    $('[data-target="#quickCreateAccountModal"]').click(function() {
        targetField = $(this).data('target-field');
    });

    $('#quick-create-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("vouchers.contra.ajax.quick-create-account") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    var newOption = new Option(
                        response.data.code + ' - ' + response.data.name,
                        response.data.id, true, true
                    );
                    $('#' + targetField).append(newOption).trigger('change');
                    $('#quickCreateAccountModal').modal('hide');
                    $('#quick-create-form')[0].reset();
                    Swal.fire('Success!', response.message, 'success');
                    updateSummary();
                }
            }
        });
    });

    $('#contra-voucher-form').submit(function(e) {
        e.preventDefault();

        var fromId = $('#from_account_id').val();
        var toId = $('#to_account_id').val();

        if (fromId === toId) {
            Swal.fire('Error', 'Accounts must be different!', 'error');
            return;
        }

        var submitBtn = $('#submit-btn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: '{{ route("vouchers.contra.update", $contraVoucher->id) }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        window.location.href = '/vouchers/contra/{{ $contraVoucher->id }}';
                    });
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Voucher');
            }
        });
    });
});
</script>
@stop
