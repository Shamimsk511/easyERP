@extends('adminlte::page')
@section('title', 'Create Customer')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1>Create Customer</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <form id="customerForm">
            @csrf
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Basic Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required autofocus>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Customer Group</label>
                                <select name="customer_group_id" id="customer_group_id" class="form-control select2" style="width: 100%;">
                                    <option value="">-- Select Group --</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" id="city" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" id="state" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-balance-scale"></i> Accounting Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Opening Balance <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">৳</span></div>
                                    <input type="number" name="opening_balance" id="opening_balance" class="form-control" step="0.01" min="0" value="0" required>
                                </div>
                                <small class="text-muted">Enter 0 if no opening balance</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Balance Type <span class="text-danger">*</span></label>
                                <select name="opening_balance_type" id="opening_balance_type" class="form-control" required>
                                    <option value="debit" selected>Debit (Receivable/Outstanding)</option>
                                    <option value="credit">Credit (Advance Payment)</option>
                                </select>
                                <small class="text-muted">Debit = Customer owes you</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Opening Balance Date</label>
                                <input type="date" name="opening_balance_date" id="opening_balance_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Credit Limit</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">৳</span></div>
                                    <input type="number" name="credit_limit" id="credit_limit" class="form-control" step="0.01" min="0" value="0">
                                </div>
                                <small class="text-muted">Maximum outstanding allowed (0 = No limit)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Credit Period (Days)</label>
                                <input type="number" name="credit_period_days" id="credit_period_days" class="form-control" min="0" value="0">
                                <small class="text-muted">Default payment due period (0 = Immediate)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note"></i> Additional Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Internal notes about this customer..."></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Create Customer
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
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
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@stop

@push('js')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({ allowClear: true, placeholder: '-- Select --' });

    // Form submission
    $('#customerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        const formData = {
            _token: '{{ csrf_token() }}',
            name: $('#name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            customer_group_id: $('#customer_group_id').val(),
            address: $('#address').val(),
            city: $('#city').val(),
            state: $('#state').val(),
            postal_code: $('#postal_code').val(),
            opening_balance: $('#opening_balance').val(),
            opening_balance_type: $('#opening_balance_type').val(),
            opening_balance_date: $('#opening_balance_date').val(),
            credit_limit: $('#credit_limit').val(),
            credit_period_days: $('#credit_period_days').val(),
            notes: $('#notes').val()
        };

        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route("customers.store") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Customer created successfully',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = response.redirect_url || '{{ route("customers.index") }}';
                });
            },
            error: function(xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Create Customer');
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(field => {
                        const input = $(`#${field}`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                    Swal.fire('Validation Error', 'Please check the highlighted fields.', 'error');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to create customer', 'error');
                }
            }
        });
    });
});
</script>
@endpush