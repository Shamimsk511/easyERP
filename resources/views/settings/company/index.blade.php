@extends('adminlte::page')

@section('title', 'Company Settings')

@section('content_header')
    <h1><i class="fas fa-building"></i> Company Settings</h1>
@stop

@section('content')
<form id="company-settings-form" enctype="multipart/form-data">
    @csrf
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            
            <!-- Company Information -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title"><i class="fas fa-building"></i> Company Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Company Name (English) <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="{{ $settings->company_name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Company Name (বাংলা)</label>
                                <input type="text" name="company_name_bangla" class="form-control" 
                                       value="{{ $settings->company_name_bangla }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tagline / Slogan</label>
                        <input type="text" name="tagline" class="form-control" 
                               value="{{ $settings->tagline }}" 
                               placeholder="Your company tagline or motto">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Company Logo</label>
                                <div class="custom-file">
                                    <input type="file" name="logo" class="custom-file-input" 
                                           id="logo-input" accept="image/*">
                                    <label class="custom-file-label" for="logo-input">Choose logo...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Recommended: 200x60px, Max: 2MB (PNG, JPG, SVG)
                                </small>
                                
                                @if($settings->logo_url)
                                <div class="mt-2" id="current-logo">
                                    <img src="{{ $settings->logo_url }}" alt="Logo" 
                                         style="max-height: 60px; border: 1px solid #ddd; padding: 5px;">
                                    <button type="button" class="btn btn-danger btn-xs ml-2" id="delete-logo-btn">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Favicon</label>
                                <div class="custom-file">
                                    <input type="file" name="favicon" class="custom-file-input" 
                                           id="favicon-input" accept=".png,.ico">
                                    <label class="custom-file-label" for="favicon-input">Choose favicon...</label>
                                </div>
                                <small class="form-text text-muted">
                                    Recommended: 32x32px or 64x64px (PNG, ICO)
                                </small>
                                
                                @if($settings->favicon_url)
                                <div class="mt-2" id="current-favicon">
                                    <img src="{{ $settings->favicon_url }}" alt="Favicon" 
                                         style="max-height: 32px; border: 1px solid #ddd; padding: 2px;">
                                    <button type="button" class="btn btn-danger btn-xs ml-2" id="delete-favicon-btn">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title"><i class="fas fa-phone"></i> Contact Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="{{ $settings->email }}" placeholder="info@company.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="{{ $settings->phone }}" placeholder="+880-2-1234567">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control" 
                                       value="{{ $settings->mobile }}" placeholder="+880-1712345678">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fax</label>
                                <input type="text" name="fax" class="form-control" 
                                       value="{{ $settings->fax }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Website</label>
                        <input type="url" name="website" class="form-control" 
                               value="{{ $settings->website }}" placeholder="https://www.company.com">
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" 
                                  required>{{ $settings->address }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" class="form-control" 
                                       value="{{ $settings->city }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>State / Division</label>
                                <input type="text" name="state" class="form-control" 
                                       value="{{ $settings->state }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" 
                                       value="{{ $settings->postal_code }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Country <span class="text-danger">*</span></label>
                                <input type="text" name="country" class="form-control" 
                                       value="{{ $settings->country }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Information -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title"><i class="fas fa-certificate"></i> Business Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>BIN (Business Identification Number)</label>
                                <input type="text" name="bin" class="form-control" 
                                       value="{{ $settings->bin }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>TIN (Tax Identification Number)</label>
                                <input type="text" name="tin" class="form-control" 
                                       value="{{ $settings->tin }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Trade License Number</label>
                                <input type="text" name="trade_license" class="form-control" 
                                       value="{{ $settings->trade_license }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>VAT Registration Number</label>
                                <input type="text" name="vat_registration" class="form-control" 
                                       value="{{ $settings->vat_registration }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Accounts -->
            <div class="card">
                <div class="card-header bg-secondary">
                    <h3 class="card-title"><i class="fas fa-university"></i> Bank Accounts</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="add-bank-btn">
                            <i class="fas fa-plus"></i> Add Bank
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="bank-accounts-container">
                        @forelse($settings->bank_accounts_list as $index => $bank)
                        <div class="bank-account-row card mb-2">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bank Name</label>
                                            <input type="text" class="form-control bank-name" 
                                                   value="{{ $bank['bank_name'] ?? '' }}" 
                                                   placeholder="Bank Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Branch</label>
                                            <input type="text" class="form-control bank-branch" 
                                                   value="{{ $bank['branch'] ?? '' }}" 
                                                   placeholder="Branch Name">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Account Number</label>
                                            <input type="text" class="form-control bank-account-number" 
                                                   value="{{ $bank['account_number'] ?? '' }}" 
                                                   placeholder="Account Number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Account Title</label>
                                            <input type="text" class="form-control bank-account-title" 
                                                   value="{{ $bank['account_title'] ?? '' }}" 
                                                   placeholder="Account Title">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Swift Code</label>
                                            <input type="text" class="form-control bank-swift" 
                                                   value="{{ $bank['swift_code'] ?? '' }}" 
                                                   placeholder="SWIFT Code">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Routing Number</label>
                                            <input type="text" class="form-control bank-routing" 
                                                   value="{{ $bank['routing_number'] ?? '' }}" 
                                                   placeholder="Routing Number">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm remove-bank-btn">
                                    <i class="fas fa-trash"></i> Remove Bank
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted">No bank accounts added yet. Click "Add Bank" to add one.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="card">
                <div class="card-header bg-dark">
                    <h3 class="card-title"><i class="fas fa-file-contract"></i> Terms & Conditions</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>General Terms & Conditions</label>
                        <textarea name="terms_and_conditions" class="form-control" 
                                  rows="5">{{ $settings->terms_and_conditions }}</textarea>
                        <small class="form-text text-muted">
                            Will be displayed on invoices and quotations
                        </small>
                    </div>
                </div>
            </div>

            <!-- Document Footers -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title"><i class="fas fa-file-alt"></i> Document Footers</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Invoice Footer</label>
                        <textarea name="invoice_footer" class="form-control" 
                                  rows="2">{{ $settings->invoice_footer }}</textarea>
                        <small class="form-text text-muted">
                            Footer text for sales invoices
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Quotation Footer</label>
                        <textarea name="quotation_footer" class="form-control" 
                                  rows="2">{{ $settings->quotation_footer }}</textarea>
                        <small class="form-text text-muted">
                            Footer text for quotations
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Receipt Footer</label>
                        <textarea name="receipt_footer" class="form-control" 
                                  rows="2">{{ $settings->receipt_footer }}</textarea>
                        <small class="form-text text-muted">
                            Footer text for receipts and vouchers
                        </small>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            
            <!-- Fiscal Year -->
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Fiscal Year</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="fiscal_year_start" class="form-control" 
                               value="{{ $settings->fiscal_year_start?->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="fiscal_year_end" class="form-control" 
                               value="{{ $settings->fiscal_year_end?->format('Y-m-d') }}">
                    </div>
                </div>
            </div>

            <!-- Currency Settings -->
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Currency Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Currency Code <span class="text-danger">*</span></label>
                        <select name="currency_code" class="form-control" required>
                            <option value="BDT" {{ $settings->currency_code == 'BDT' ? 'selected' : '' }}>BDT - Bangladeshi Taka</option>
                            <option value="USD" {{ $settings->currency_code == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ $settings->currency_code == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ $settings->currency_code == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="INR" {{ $settings->currency_code == 'INR' ? 'selected' : '' }}>INR - Indian Rupee</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Currency Symbol <span class="text-danger">*</span></label>
                        <input type="text" name="currency_symbol" class="form-control" 
                               value="{{ $settings->currency_symbol }}" required>
                    </div>

                    <div class="form-group">
                        <label>Symbol Position <span class="text-danger">*</span></label>
                        <select name="currency_position" class="form-control" required>
                            <option value="left" {{ $settings->currency_position == 'left' ? 'selected' : '' }}>Left (৳ 1,000)</option>
                            <option value="right" {{ $settings->currency_position == 'right' ? 'selected' : '' }}>Right (1,000 ৳)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Print Settings -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title"><i class="fas fa-print"></i> Print Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Paper Size <span class="text-danger">*</span></label>
                        <select name="print_paper_size" class="form-control" required>
                            <option value="A4" {{ $settings->print_paper_size == 'A4' ? 'selected' : '' }}>A4 (210 x 297 mm)</option>
                            <option value="Letter" {{ $settings->print_paper_size == 'Letter' ? 'selected' : '' }}>Letter (8.5 x 11 inch)</option>
                            <option value="Legal" {{ $settings->print_paper_size == 'Legal' ? 'selected' : '' }}>Legal (8.5 x 14 inch)</option>
                        </select>
                    </div>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" 
                               id="show_logo_in_print" name="show_logo_in_print" 
                               {{ $settings->show_logo_in_print ? 'checked' : '' }}>
                        <label class="custom-control-label" for="show_logo_in_print">
                            Show logo in printed documents
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" 
                               id="show_company_info_in_print" name="show_company_info_in_print" 
                               {{ $settings->show_company_info_in_print ? 'checked' : '' }}>
                        <label class="custom-control-label" for="show_company_info_in_print">
                            Show company info in printed documents
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-btn">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <a href="{{ url('/home') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>
@stop

@section('css')
<style>
.bank-account-row {
    background: #f8f9fa;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    
    // Custom file input labels
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Add bank account
    $('#add-bank-btn').click(function() {
        const bankRow = `
            <div class="bank-account-row card mb-2">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" class="form-control bank-name" placeholder="Bank Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Branch</label>
                                <input type="text" class="form-control bank-branch" placeholder="Branch Name">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Number</label>
                                <input type="text" class="form-control bank-account-number" placeholder="Account Number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Title</label>
                                <input type="text" class="form-control bank-account-title" placeholder="Account Title">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Swift Code</label>
                                <input type="text" class="form-control bank-swift" placeholder="SWIFT Code">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Routing Number</label>
                                <input type="text" class="form-control bank-routing" placeholder="Routing Number">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-bank-btn">
                        <i class="fas fa-trash"></i> Remove Bank
                    </button>
                </div>
            </div>
        `;
        
        $('#bank-accounts-container').append(bankRow);
    });

    // Remove bank account
    $(document).on('click', '.remove-bank-btn', function() {
        $(this).closest('.bank-account-row').remove();
    });

    // Delete logo
    $('#delete-logo-btn').click(function() {
        Swal.fire({
            title: 'Delete Logo?',
            text: "This will remove the company logo",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("settings.company.delete-logo") }}',
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            $('#current-logo').remove();
                            Swal.fire('Deleted!', response.message, 'success');
                        }
                    }
                });
            }
        });
    });

    // Delete favicon
    $('#delete-favicon-btn').click(function() {
        Swal.fire({
            title: 'Delete Favicon?',
            text: "This will remove the favicon",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("settings.company.delete-favicon") }}',
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            $('#current-favicon').remove();
                            Swal.fire('Deleted!', response.message, 'success');
                        }
                    }
                });
            }
        });
    });

    // Form submit
    $('#company-settings-form').submit(function(e) {
        e.preventDefault();

        // Collect bank accounts
        const bankAccounts = [];
        $('.bank-account-row').each(function() {
            const bankName = $(this).find('.bank-name').val();
            const branch = $(this).find('.bank-branch').val();
            const accountNumber = $(this).find('.bank-account-number').val();
            const accountTitle = $(this).find('.bank-account-title').val();
            const swiftCode = $(this).find('.bank-swift').val();
            const routingNumber = $(this).find('.bank-routing').val();

            if (bankName || accountNumber) {
                bankAccounts.push({
                    bank_name: bankName,
                    branch: branch,
                    account_number: accountNumber,
                    account_title: accountTitle,
                    swift_code: swiftCode,
                    routing_number: routingNumber
                });
            }
        });

        const formData = new FormData(this);
        formData.append('bank_accounts', JSON.stringify(bankAccounts));

        const submitBtn = $('#submit-btn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '{{ route("settings.company.update") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred', 'error');
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Settings');
            }
        });
    });
});
</script>
@stop
