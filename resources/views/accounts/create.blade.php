@extends('adminlte::page')

@section('title', 'Create Account')

@section('content_header')
    <h1>Create New Account</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
        </div>
        
        <form action="{{ route('accounts.store') }}" method="POST" id="accountForm">
            @csrf
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Account Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                            @error('code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Account Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-control select2 @error('type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                @foreach($accountTypes as $key => $value)
                                    <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="parent_account_id">Parent Account</label>
                            <select name="parent_account_id" id="parent_account_id" class="form-control select2 @error('parent_account_id') is-invalid @enderror">
                                <option value="">None</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_account_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->code }} - {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_account_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="opening_balance">Opening Balance</label>
                            <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}">
                            @error('opening_balance')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="opening_balance_date">Opening Balance Date</label>
                            <input type="date" name="opening_balance_date" id="opening_balance_date" class="form-control @error('opening_balance_date') is-invalid @enderror" value="{{ old('opening_balance_date') }}">
                            @error('opening_balance_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Create Account
                </button>
                <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@stop

@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('css')
<style>
    /* Fix Select2 border visibility */
    .select2-container--bootstrap4 .select2-selection {
        border: 1px solid #ced4da !important;
        min-height: 38px;
    }
    
    /* Focus state */
    .select2-container--bootstrap4.select2-container--focus .select2-selection {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
    }
    
    /* Invalid state */
    .select2-container--bootstrap4 .select2-selection.is-invalid,
    .was-validated .form-control.select2:invalid ~ .select2-container--bootstrap4 .select2-selection {
        border-color: #dc3545 !important;
    }
    
    /* Valid state */
    .select2-container--bootstrap4 .select2-selection.is-valid {
        border-color: #28a745 !important;
    }
    
    /* Dropdown styling */
    .select2-dropdown {
        border: 1px solid #ced4da !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075) !important;
    }
</style>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').each(function() {
            $(this).select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: $(this).data('placeholder') || 'Select an option',
                allowClear: true
            });
        });

        // Auto-open Select2 on focus (Tab key navigation)
        $('.select2').on('select2:open', function(e) {
            // Focus on search box when dropdown opens
            setTimeout(function() {
                document.querySelector('.select2-search__field').focus();
            }, 100);
        });

        // Open dropdown when Select2 container gains focus
        $(document).on('focus', '.select2-selection.select2-selection--single', function(e) {
            $(this).closest('.select2-container').siblings('select:enabled').select2('open');
        });

        // Prevent dropdown from closing immediately after losing focus
        $('select.select2').on('select2:closing', function(e) {
            var $searchfield = $(this).parent().find('.select2-search__field');
            if ($searchfield.prop('focus')) {
                e.preventDefault();
            }
        });

        // Form submission with AJAX
        $('#accountForm').on('submit', function(e) {
            e.preventDefault();
            
            let form = $(this);
            let formData = new FormData(this);
            let submitBtn = form.find('button[type="submit"]');
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
            
            // Remove previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.select2-selection').removeClass('is-invalid');
            $('.invalid-feedback').hide();
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Account created successfully!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '{{ route("accounts.index") }}';
                    });
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Create Account');
                    
                    if (xhr.status === 422) {
                        // Validation errors
                        let errors = xhr.responseJSON.errors;
                        let errorHtml = '<ul style="text-align: left; margin: 0;">';
                        
                        $.each(errors, function(field, messages) {
                            errorHtml += '<li>' + messages[0] + '</li>';
                            
                            // Add is-invalid class to fields
                            let $field = $('[name="' + field + '"]');
                            $field.addClass('is-invalid');
                            
                            // For Select2, also add to the selection element
                            if ($field.hasClass('select2')) {
                                $field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                            }
                        });
                        
                        errorHtml += '</ul>';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: errorHtml
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred. Please try again.'
                        });
                    }
                }
            });
        });

        // Clear validation errors on input/change
        $('input, textarea').on('change input', function() {
            $(this).removeClass('is-invalid');
        });

        // Clear validation errors on Select2 change
        $('.select2').on('change', function() {
            $(this).removeClass('is-invalid');
            $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        });

        // Display server-side validation errors (on page load)
        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<ul style="text-align: left; margin: 0;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>'
            });
            
            // Highlight Select2 fields with errors
            @foreach($errors->keys() as $field)
                let $field_{{ str_replace('.', '_', $field) }} = $('[name="{{ $field }}"]');
                if ($field_{{ str_replace('.', '_', $field) }}.hasClass('select2')) {
                    $field_{{ str_replace('.', '_', $field) }}.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                }
            @endforeach
        @endif

        // Display session messages
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session("success") }}',
                timer: 1500,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session("error") }}'
            });
        @endif
    });
</script>
@stop
