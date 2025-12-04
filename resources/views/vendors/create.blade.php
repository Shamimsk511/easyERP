@extends('adminlte::page')

@section('title', 'Add Vendor')

@section('content_header')
    <h1>Add Vendor</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Vendor Information</h3>
        <div class="card-tools">
            <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('vendors.store') }}" method="POST" id="vendor-form">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control border @error('name') is-invalid @enderror" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}"
                               required 
                               autofocus>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">Active</label>
    </div>
    <small class="form-text text-muted">Inactive vendors won't appear in selection lists</small>
</div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control border @error('description') is-invalid @enderror" 
                                  name="description" 
                                  id="description" 
                                  rows="1">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="fas fa-balance-scale"></i> Opening Balance</h5>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="opening_balance">Opening Balance Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="number" 
                                   class="form-control border @error('opening_balance') is-invalid @enderror" 
                                   name="opening_balance" 
                                   id="opening_balance" 
                                   value="{{ old('opening_balance', 0) }}"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00">
                        </div>
                        @error('opening_balance')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Leave as 0 if no opening balance</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="opening_balance_type">Balance Type <span class="text-danger">*</span></label>
                        <select class="form-control border select2 @error('opening_balance_type') is-invalid @enderror" 
                                name="opening_balance_type" 
                                id="opening_balance_type"
                                required>
                            <option value="credit" {{ old('opening_balance_type', 'credit') == 'credit' ? 'selected' : '' }}>
                                Credit (We Owe Vendor)
                            </option>
                            <option value="debit" {{ old('opening_balance_type') == 'debit' ? 'selected' : '' }}>
                                Debit (Vendor Owes Us)
                            </option>
                        </select>
                        @error('opening_balance_type')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            <strong>Credit:</strong> Amount payable to vendor<br>
                            <strong>Debit:</strong> Amount receivable from vendor
                        </small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="opening_balance_date">Opening Balance Date</label>
                        <input type="date" 
                               class="form-control border @error('opening_balance_date') is-invalid @enderror" 
                               name="opening_balance_date" 
                               id="opening_balance_date" 
                               value="{{ old('opening_balance_date', date('Y-m-d')) }}">
                        @error('opening_balance_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Vendor
                    </button>
                    <a href="{{ route('vendors.index') }}" class="btn btn-secondary">
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
        height: 38px;
        border: 1px solid #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-container--open .select2-dropdown {
        border: 1px solid #ced4da;
    }
    .select2-results__option {
        border-bottom: 1px solid #f0f0f0;
        padding: 8px;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2 with proper focus behavior
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Select an option',
        allowClear: false
    }).on('select2:open', function() {
        // Focus on search input when opened
        setTimeout(function() {
            $('.select2-search__field').focus();
        }, 100);
    });

    // Set cursor focus on name field
    $('#name').focus();

    // Handle form submission with AJAX
    $('#vendor-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Vendor created successfully!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = "{{ route('vendors.index') }}";
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Vendor');
                
                let errorMessage = 'Failed to create vendor.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            }
        });
    });

    // Show/hide opening balance fields based on amount
    $('#opening_balance').on('input', function() {
        const amount = parseFloat($(this).val()) || 0;
        if (amount > 0) {
            $('#opening_balance_type').closest('.col-md-4').show();
            $('#opening_balance_date').closest('.col-md-4').show();
        }
    });
});
</script>
@stop
