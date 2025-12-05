@extends('adminlte::page')
@section('title', 'Sales / Invoices')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Sales / Invoices</h1>
        </div>
        <div class="col-sm-6 text-right">
            <a href="{{ route('sales.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Invoice
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoice List</h3>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDelivery">Delivery Status</label>
                    <select id="filterDelivery" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDeleted">Show Deleted</label>
                    <select id="filterDeleted" class="form-control">
                        <option value="no">Active Only</option>
                        <option value="yes">Deleted Only</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <label>&nbsp;</label>
                <button id="resetFilters" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Reset Filters
                </button>
            </div>
        </div>

        <!-- DataTable -->
        <table id="invoicesTable" class="table table-bordered table-hover table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Outstanding</th>
                    <th>Delivery</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;

    // ===== CUSTOMER SELECT2 - Using searchCustomers endpoint =====
    $('#customer_id').select2({
        theme: 'default',
        placeholder: 'Type to search customer...',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '{{ route("sales.search-customers") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                console.log('Customer search results:', data);
                return {
                    results: data.results || [],
                    pagination: data.pagination || {}
                };
            },
            cache: true,
            error: function(xhr, status, error) {
                console.error('Customer search AJAX error:', {status, error, xhr});
            }
        },
        minimumInputLength: 1,
        templateResult: function(item) {
            if (item.loading) {
                return item.text;
            }
            if (!item.id) {
                return item.text;
            }
            return '<div><strong>' + item.name + '</strong><br><small>' + item.customer_code + ' | ' + item.phone + '</small></div>';
        },
        templateSelection: function(item) {
            return item.name || item.text;
        }
    });

    // When customer is selected, fetch their details
    $('#customer_id').on('select2:select', function(e) {
        const customerId = e.params.data.id;
        console.log('Customer selected:', customerId);
        
        if (!customerId) {
            $('#customerInfoCard').hide();
            return;
        }

        // Fetch customer details using the getCustomerDetails endpoint
        $.ajax({
            url: '/sales/get-customer/' + customerId,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Customer details response:', response);
                
                if (response.success && response.data) {
                    const data = response.data;
                    $('#customerInfoCard').show();
                    $('#cust-name').text(data.name || '-');
                    $('#cust-phone').text(data.phone || '-');
                    $('#cust-city').text(data.city || '-');
                    $('#cust-outstanding').text('৳ ' + parseFloat(data.outstanding_balance || 0).toFixed(2));
                } else {
                    console.error('Invalid response:', response);
                    Swal.fire('Error', 'Failed to load customer data', 'error');
                    $('#customerInfoCard').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Customer details AJAX error:', {status, error, xhr: xhr.responseJSON});
                Swal.fire('Error', 'Failed to load customer details: ' + error, 'error');
                $('#customerInfoCard').hide();
            }
        });
    });

    // Hide customer info when no selection
    $('#customer_id').on('select2:unselect', function() {
        $('#customerInfoCard').hide();
    });

    // ===== PRODUCT SELECT2 - Initialize on page load =====
    function initProductSelect($select) {
        // Destroy existing Select2 if any
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        
        $select.select2({
            theme: 'default',
            placeholder: 'Search product...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '{{ route("sales.search-products") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        customer_id: $('#customer_id').val() || ''
                    };
                },
                processResults: function(data) {
                    console.log('Product search results:', data);
                    return {
                        results: $.map(data, function(item) {
                            return {
                                id: item.id,
                                text: item.text,
                                data: item
                            };
                        })
                    };
                },
                cache: true,
                error: function(xhr, status, error) {
                    console.error('Product search error:', {status, error, xhr});
                }
            },
            minimumInputLength: 1,
            templateResult: function(item) {
                if (item.loading) {
                    return item.text;
                }
                if (!item.data) {
                    return item.text;
                }
                return '<div><strong>' + item.data.name + '</strong> (' + item.data.code + ')<br>' +
                       '<small>Stock: ' + (item.data.stock || 0) + ' ' + item.data.unit + 
                       ' | Price: ৳' + parseFloat(item.data.price || 0).toFixed(2) + '</small></div>';
            },
            templateSelection: function(item) {
                return item.data ? item.data.name : item.text;
            }
        });

        // Auto-fill when product selected
        $select.on('select2:select', function(e) {
            const product = e.params.data.data;
            const $row = $select.closest('tr');
            
            $row.find('.unit-display').val(product.unit || '');
            $row.find('.rate').val(parseFloat(product.price || 0).toFixed(2));
            
            // Trigger calculation
            $row.find('.rate').trigger('input');
            
            // Focus on quantity field
            setTimeout(() => {
                $row.find('.qty').focus();
            }, 100);
        });
    }

    // Initialize all existing product selects
    $('.product-select').each(function() {
        initProductSelect($(this));
    });

    // ===== ADD NEW ROW =====
    $(document).on('click', '.add-row', function(e) {
        e.preventDefault();
        
        const firstRow = $('#po-items tbody tr').first();
        const newRow = firstRow.clone();
        
        // Clear all inputs
        newRow.find('input, textarea').val('');
        newRow.find('.unit-display').val('');
        
        // Destroy Select2 from cloned row
        newRow.find('.select2-container').remove();
        
        // Reset select element
        const select = newRow.find('select');
        select.removeClass('select2-hidden-accessible')
              .removeAttr('data-select2-id')
              .removeAttr('aria-hidden')
              .removeAttr('tabindex')
              .show();
        select.find('option').first().prop('selected', true);
        
        // Update field names with new index
        newRow.find('[name]').each(function() {
            const name = $(this).attr('name');
            if (name && name.includes('items')) {
                const newName = name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']');
                $(this).attr('name', newName);
            }
        });
        
        $('#po-items tbody').append(newRow);
        
        // Initialize Select2 on new row
        initProductSelect(newRow.find('.product-select'));
        rowIndex++;
    });

    // ===== REMOVE ROW =====
    $(document).on('click', '.remove-row', function(e) {
        e.preventDefault();
        
        if ($('#po-items tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateGrandTotal();
        } else {
            Swal.fire('Warning', 'At least one product row is required', 'warning');
        }
    });

    // ===== CALCULATE ROW AMOUNT =====
    $(document).on('input', '.qty, .rate', function() {
        const $row = $(this).closest('tr');
        const qty = parseFloat($row.find('.qty').val()) || 0;
        const rate = parseFloat($row.find('.rate').val()) || 0;
        const amount = qty * rate;
        
        $row.find('.amount').val(amount > 0 ? amount.toFixed(2) : '');
        calculateGrandTotal();
    });

    function calculateGrandTotal() {
        let total = 0;
        $('#po-items tbody tr').each(function() {
            const amount = parseFloat($(this).find('.amount').val()) || 0;
            total += amount;
        });
        $('#grand-total').val(total.toFixed(2));
    }

    // ===== FORM SUBMISSION =====
    $('#purchase-form').on('submit', function(e) {
        e.preventDefault();

        // Validation
        if ($('#po-items tbody tr').length === 0) {
            Swal.fire('Warning', 'Please add at least one product', 'warning');
            return false;
        }

        if (!$('#customer_id').val()) {
            Swal.fire('Warning', 'Please select a customer', 'warning');
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we save your invoice',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: '{{ route("sales.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                console.log('Create response:', response);
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                } else {
                    Swal.fire('Error', response.message || 'Unknown error', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Create AJAX error:', {status, error, xhr: xhr.responseJSON});
                const message = xhr.responseJSON?.message || xhr.responseJSON?.errors || error || 'Failed to create invoice';
                Swal.fire('Error', message, 'error');
            }
        });
    });

    // Initialize calculations on page load
    calculateGrandTotal();
});
</script>
@endpush



@push('css')
<style>
    .table-sm td, .table-sm th {
        padding: 0.4rem;
    }
</style>
@endpush
