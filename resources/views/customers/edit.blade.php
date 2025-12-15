@extends('adminlte::page')
@section('title', 'Edit Customer - ' . $customer->name)

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1>Edit Customer</h1></div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-info"><i class="fas fa-eye"></i> View</a>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <form id="customerForm">
            @csrf
            @method('PUT')
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user"></i> Basic Information</h3>
                    <div class="card-tools">
                        <span class="badge badge-light">{{ $customer->customer_code }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $customer->email) }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Customer Group</label>
                                <select name="customer_group_id" id="customer_group_id" class="form-control select2" style="width: 100%;">
                                    <option value="">-- Select Group --</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('customer_group_id', $customer->customer_group_id) == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" id="address" class="form-control" rows="2">{{ old('address', $customer->address) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $customer->city) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" id="state" class="form-control" value="{{ old('state', $customer->state) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code', $customer->postal_code) }}">
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
                    {{-- Current Balance Display --}}
                    <div class="alert alert-{{ $customer->current_balance >= 0 ? 'warning' : 'success' }}">
                        <i class="fas fa-info-circle"></i>
                        <strong>Current Balance:</strong> 
                        ৳ {{ number_format(abs($customer->current_balance), 2) }} 
                        {{ $customer->current_balance >= 0 ? 'Dr (Receivable)' : 'Cr (Advance)' }}
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Opening Balance</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">৳</span></div>
                                    <input type="text" class="form-control" value="{{ number_format($customer->opening_balance, 2) }}" disabled>
                                </div>
                                <small class="text-muted">
                                    {{ ucfirst($customer->opening_balance_type) }} - Set on {{ $customer->opening_balance_date ? \Carbon\Carbon::parse($customer->opening_balance_date)->format('d/m/Y') : 'N/A' }}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Credit Limit</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">৳</span></div>
                                    <input type="number" name="credit_limit" id="credit_limit" class="form-control" step="0.01" min="0" value="{{ old('credit_limit', $customer->credit_limit) }}">
                                </div>
                                <small class="text-muted">Maximum outstanding allowed (0 = No limit)</small>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Credit Period (Days)</label>
                                <input type="number" name="credit_period_days" id="credit_period_days" class="form-control" min="0" value="{{ old('credit_period_days', $customer->credit_period_days) }}">
                                <small class="text-muted">Default payment due period (0 = Immediate)</small>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog"></i> Status & Notes</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">Active Customer</label>
                                </div>
                                <small class="text-muted">Inactive customers won't appear in selection lists</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Update Customer
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Details
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
            _method: 'PUT',
            name: $('#name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            customer_group_id: $('#customer_group_id').val(),
            address: $('#address').val(),
            city: $('#city').val(),
            state: $('#state').val(),
            postal_code: $('#postal_code').val(),
            credit_limit: $('#credit_limit').val(),
            credit_period_days: $('#credit_period_days').val(),
            notes: $('#notes').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0
        };

        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route("customers.update", $customer) }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Customer updated successfully',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = response.redirect_url || '{{ route("customers.show", $customer) }}';
                });
            },
            error: function(xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Update Customer');
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(field => {
                        const input = $(`#${field}`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(errors[field][0]);
                    });
                    Swal.fire('Validation Error', 'Please check the highlighted fields.', 'error');
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update customer', 'error');
                }
            }
        });
    });
});
</script>
@endpush