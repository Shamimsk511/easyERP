@extends('adminlte::page')

@section('title', 'Edit Journal Voucher')

@section('content_header')
    <h1>Edit Journal Voucher</h1>
@stop

@section('content')
<form id="journal-voucher-form">
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
                                <input type="text" class="form-control" value="{{ $journalVoucher->voucher_number }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date <span class="text-danger">*</span></label>
                                <input type="date" name="journal_date" id="journal_date" 
                                       class="form-control" value="{{ $journalVoucher->journal_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" 
                                  class="form-control" rows="2" required>{{ $journalVoucher->description }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" 
                                  rows="2">{{ $journalVoucher->notes }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Journal Entries -->
            <div class="card">
                <div class="card-header bg-secondary">
                    <h3 class="card-title">Journal Entries</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="add-entry-btn">
                            <i class="fas fa-plus"></i> Add Entry
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="entries-table">
                            <thead class="bg-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="40%">Account <span class="text-danger">*</span></th>
                                    <th width="15%">Type <span class="text-danger">*</span></th>
                                    <th width="20%">Amount (৳) <span class="text-danger">*</span></th>
                                    <th width="15%">Memo</th>
                                    <th width="5%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="entries-container">
                                @foreach($journalVoucher->transaction->entries as $index => $entry)
                                <tr data-index="{{ $index + 1 }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <select name="entries[{{ $index + 1 }}][account_id]" 
                                                class="form-control select2-account entry-account" 
                                                style="width: 100%;" required>
                                            <option value="{{ $entry->account_id }}" selected>
                                                {{ $entry->account->code }} - {{ $entry->account->name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="entries[{{ $index + 1 }}][type]" 
                                                class="form-control entry-type" required>
                                            <option value="debit" {{ $entry->type == 'debit' ? 'selected' : '' }}>Debit</option>
                                            <option value="credit" {{ $entry->type == 'credit' ? 'selected' : '' }}>Credit</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="entries[{{ $index + 1 }}][amount]" 
                                               class="form-control entry-amount" 
                                               step="0.01" min="0.01" 
                                               value="{{ $entry->amount }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="entries[{{ $index + 1 }}][memo]" 
                                               class="form-control" 
                                               value="{{ $entry->memo }}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm remove-entry-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="3" class="text-right">Total:</th>
                                    <th>
                                        <div class="row">
                                            <div class="col-6">
                                                <small>Dr: <span id="total-debit" class="text-danger">0.00</span></small>
                                            </div>
                                            <div class="col-6">
                                                <small>Cr: <span id="total-credit" class="text-success">0.00</span></small>
                                            </div>
                                        </div>
                                    </th>
                                    <th colspan="2">
                                        <span id="balance-status" class="badge badge-secondary">Not Balanced</span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body">
                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-danger"><i class="fas fa-minus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Debit</span>
                            <span class="info-box-number" id="summary-debit">৳ 0.00</span>
                        </div>
                    </div>

                    <div class="info-box bg-light">
                        <span class="info-box-icon bg-success"><i class="fas fa-plus-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Credit</span>
                            <span class="info-box-number" id="summary-credit">৳ 0.00</span>
                        </div>
                    </div>

                    <div class="info-box" id="difference-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Difference</span>
                            <span class="info-box-number" id="summary-difference">৳ 0.00</span>
                        </div>
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
                            <option value="draft" {{ $journalVoucher->status == 'draft' ? 'selected' : '' }}>Save as Draft</option>
                            <option value="posted" {{ $journalVoucher->status == 'posted' ? 'selected' : '' }}>Post Entry</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-warning btn-block" id="submit-btn">
                        <i class="fas fa-save"></i> Update Voucher
                    </button>
                    <a href="{{ route('vouchers.journal.show', $journalVoucher->id) }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
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
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    let entryIndex = {{ $journalVoucher->transaction->entries->count() }};

    // Initialize existing Select2 elements
    $('.select2-account').each(function() {
        initializeSelect2($(this));
    });

    // Initial calculation
    updateCalculations();

    $('#add-entry-btn').click(function() {
        addEntryRow();
    });

    function addEntryRow() {
        entryIndex++;
        
        const row = `
            <tr data-index="${entryIndex}">
                <td class="text-center">${$('#entries-container tr').length + 1}</td>
                <td>
                    <select name="entries[${entryIndex}][account_id]" 
                            class="form-control select2-account entry-account" 
                            style="width: 100%;" required>
                        <option value="">Type to search...</option>
                    </select>
                </td>
                <td>
                    <select name="entries[${entryIndex}][type]" 
                            class="form-control entry-type" required>
                        <option value="debit">Debit</option>
                        <option value="credit">Credit</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="entries[${entryIndex}][amount]" 
                           class="form-control entry-amount" 
                           step="0.01" min="0.01" placeholder="0.00" required>
                </td>
                <td>
                    <input type="text" name="entries[${entryIndex}][memo]" 
                           class="form-control" placeholder="Memo">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-entry-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#entries-container').append(row);
        initializeSelect2($(`tr[data-index="${entryIndex}"] .select2-account`));
        updateCalculations();
    }

    function initializeSelect2($element) {
        $element.select2({
            theme: 'bootstrap4',
            placeholder: 'Type to search...',
            allowClear: true,
            ajax: {
                url: '{{ route("vouchers.journal.ajax.search-accounts") }}',
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
    }

    $(document).on('click', '.remove-entry-btn', function() {
        if ($('#entries-container tr').length > 2) {
            $(this).closest('tr').remove();
            updateRowNumbers();
            updateCalculations();
        } else {
            Swal.fire('Error', 'At least 2 entries are required!', 'error');
        }
    });

    function updateRowNumbers() {
        $('#entries-container tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    $(document).on('keyup change', '.entry-amount, .entry-type', function() {
        updateCalculations();
    });

    function updateCalculations() {
        let totalDebit = 0;
        let totalCredit = 0;

        $('#entries-container tr').each(function() {
            const type = $(this).find('.entry-type').val();
            const amount = parseFloat($(this).find('.entry-amount').val()) || 0;

            if (type === 'debit') {
                totalDebit += amount;
            } else if (type === 'credit') {
                totalCredit += amount;
            }
        });

        const difference = Math.abs(totalDebit - totalCredit);
        const isBalanced = difference < 0.01;

        $('#total-debit, #summary-debit').text('৳ ' + totalDebit.toLocaleString('en-BD', {minimumFractionDigits: 2}));
        $('#total-credit, #summary-credit').text('৳ ' + totalCredit.toLocaleString('en-BD', {minimumFractionDigits: 2}));
        $('#summary-difference').text('৳ ' + difference.toLocaleString('en-BD', {minimumFractionDigits: 2}));

        if (isBalanced && totalDebit > 0) {
            $('#balance-status').removeClass('badge-secondary badge-danger').addClass('badge-success').text('Balanced ✓');
            $('#difference-box').hide();
            $('#submit-btn').prop('disabled', false);
        } else {
            $('#balance-status').removeClass('badge-success').addClass('badge-danger').text('Not Balanced');
            $('#difference-box').show();
            $('#submit-btn').prop('disabled', true);
        }
    }

    $('#journal-voucher-form').submit(function(e) {
        e.preventDefault();

        const submitBtn = $('#submit-btn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: '{{ route("vouchers.journal.update", $journalVoucher->id) }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        window.location.href = '/vouchers/journal/{{ $journalVoucher->id }}';
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
