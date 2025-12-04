@extends('adminlte::page')

@section('title', 'Edit Vendor')

@section('content_header')
    <h1>Edit Vendor</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Update Vendor Information</h3>
        <div class="card-tools">
            <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('vendors.update', $vendor->id) }}" method="POST" id="vendor-form">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control border @error('name') is-invalid @enderror" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $vendor->name) }}"
                               required 
                               autofocus>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control border @error('description') is-invalid @enderror" 
                                  name="description" 
                                  id="description" 
                                  rows="1">{{ old('description', $vendor->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="mb-3"><i class="fas fa-balance-scale"></i> Opening Balance</h5>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Note:</strong> Changing opening balance will update the accounting ledger. Existing transactions remain unchanged.
            </div>

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
                                   value="{{ old('opening_balance', $vendor->opening_balance) }}"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00">
                        </div>
                        @error('opening_balance')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="opening_balance_type">Balance Type <span class="text-danger">*</span></label>
                        <select class="form-control border select2 @error('opening_balance_type') is-invalid @enderror" 
                                name="opening_balance_type" 
                                id="opening_balance_type"
                                required>
                            <option value="credit" {{ old('opening_balance_type', $vendor->opening_balance_type) == 'credit' ? 'selected' : '' }}>
                                Credit (We Owe Vendor)
                            </option>
                            <option value="debit" {{ old('opening_balance_type', $vendor->opening_balance_type) == 'debit' ? 'selected' : '' }}>
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
                               value="{{ old('opening_balance_date', $vendor->opening_balance_date ? $vendor->opening_balance_date->format('Y-m-d') : date('Y-m-d')) }}">
                        @error('opening_balance_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Vendor
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
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Vendor updated successfully!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = "{{ route('vendors.index') }}";
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Vendor');
                
                let errorMessage = 'Failed to update vendor.';
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
});
</script>
@stop
