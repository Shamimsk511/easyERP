@extends('adminlte::page')

@section('title', 'Edit Customer')

@section('content_header')
    <h1>Edit Customer</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer Information</h3>
                </div>
                <form id="customerForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customer->phone) }}" required>
                                    @error('phone')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}">
                                    @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer Group</label>
                                    <select name="customer_group_id" class="form-control select2 @error('customer_group_id') is-invalid @enderror">
                                        <option value="">Select Group</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ old('customer_group_id', $customer->customer_group_id) == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_group_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $customer->address) }}</textarea>
                                    @error('address')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $customer->city) }}">
                                    @error('city')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>State</label>
                                    <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $customer->state) }}">
                                    @error('state')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $customer->postal_code) }}">
                                    @error('postal_code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Accounting Information</h5>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Note:</strong> Opening balance cannot be edited. Current Balance: 
                            <strong class="{{ $customer->current_balance >= 0 ? 'text-danger' : 'text-success' }}">
                                ৳ {{ number_format(abs($customer->current_balance), 2) }} {{ $customer->current_balance >= 0 ? 'Dr' : 'Cr' }}
                            </strong>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Opening Balance</label>
                                    <input type="text" class="form-control" value="৳ {{ number_format($customer->opening_balance, 2) }}" disabled>
                                    <small class="text-muted">{{ ucfirst($customer->opening_balance_type) }} - Set on {{ \Carbon\Carbon::parse($customer->opening_balance_date)->format('d/m/Y') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Credit Limit</label>
                                    <input type="number" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" step="0.01" value="{{ old('credit_limit', $customer->credit_limit) }}">
                                    <small class="text-muted">Maximum outstanding amount allowed (0 = No limit)</small>
                                    @error('credit_limit')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Credit Period (Days)</label>
                                    <input type="number" name="credit_period_days" class="form-control @error('credit_period_days') is-invalid @enderror" value="{{ old('credit_period_days', $customer->credit_period_days) }}">
                                    <small class="text-muted">Default payment due period (0 = Immediate payment)</small>
                                    @error('credit_period_days')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Active Customer</label>
                                    </div>
                                    <small class="text-muted">Inactive customers will not appear in active lists</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Customer
                        </button>
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </form>
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
        /* Error state for Select2 */
        .select2-container--default .select2-selection--single.is-invalid {
            border-color: #dc3545 !important;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Initialize Select2 with enhanced focus behavior
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Ensure cursor is ready for typing when Select2 opens
            $('.select2').on('select2:open', function(e) {
                setTimeout(function() {
                    $('.select2-search__field').focus();
                }, 100);
            });

            // Handle form submission
            $('#customerForm').on('submit', function(e) {
                e.preventDefault();

                var formData = $(this).serialize();
                var submitBtn = $(this).find('button[type="submit"]');
                
                // Disable submit button to prevent double submission
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: '{{ route("customers.update", $customer->id) }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '{{ route("customers.index") }}';
                        });
                    },
                    error: function(xhr) {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Customer');
                        
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = '';
                        
                        if (errors) {
                            // Clear previous error states
                            $('.form-control').removeClass('is-invalid');
                            $('.invalid-feedback').remove();
                            
                            // Display each error
                            $.each(errors, function(key, value) {
                                errorMessage += value[0] + '<br>';
                                
                                // Add is-invalid class to the field
                                var field = $('[name="' + key + '"]');
                                field.addClass('is-invalid');
                                
                                // Add error message
                                if (field.hasClass('select2')) {
                                    field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                                    field.parent().append('<span class="invalid-feedback d-block">' + value[0] + '</span>');
                                } else {
                                    field.after('<span class="invalid-feedback">' + value[0] + '</span>');
                                }
                            });
                        } else {
                            errorMessage = xhr.responseJSON.message || 'An error occurred while updating the customer';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMessage
                        });
                    }
                });
            });

            // Remove error state on input change
            $('.form-control').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });

            // Remove error state on Select2 change
            $('.select2').on('change', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                $(this).parent().find('.invalid-feedback').remove();
            });
        });
    </script>
@stop
