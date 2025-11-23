@extends('adminlte::page')

@section('title', 'Edit Transaction')

@section('content_header')
    <h1>Edit Transaction</h1>
@stop

@section('content')
    <form action="{{ route('transactions.update', $transaction) }}" method="POST" id="transactionForm">
        @csrf
        @method('PUT')
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Transaction Details</h3>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $transaction->date->format('Y-m-d')) }}" required>
                            @error('date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reference">Reference</label>
                            <input type="text" name="reference" id="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference', $transaction->reference) }}">
                            @error('reference')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="posted" {{ old('status', $transaction->status) == 'posted' ? 'selected' : '' }}>Posted</option>
                                <option value="draft" {{ old('status', $transaction->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="text-danger">*</span></label>
                    <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $transaction->description) }}" required>
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $transaction->notes) }}</textarea>
                    @error('notes')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Journal Entries</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-success" id="addEntry">
                        <i class="fas fa-plus"></i> Add Entry
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                @error('entries')
                    <div class="alert alert-danger m-2">{{ $message }}</div>
                @enderror
                
                <table class="table table-bordered" id="entriesTable">
                    <thead>
                        <tr>
                            <th width="35%">Account</th>
                            <th width="25%">Memo</th>
                            <th width="15%">Debit</th>
                            <th width="15%">Credit</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="entriesBody">
                        <!-- Entries will be added here dynamically -->
                    </tbody>
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <td colspan="2" class="text-right">Total:</td>
                            <td class="text-right" id="totalDebit">0.00</td>
                            <td class="text-right" id="totalCredit">0.00</td>
                            <td></td>
                        </tr>
                        <tr id="balanceWarning" class="bg-warning" style="display: none;">
                            <td colspan="5" class="text-center text-danger font-weight-bold">
                                <i class="fas fa-exclamation-triangle"></i> Debits and Credits must be equal!
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Transaction
                </button>
                <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        let entryIndex = 0;
        const accounts = @json($accounts);
        const existingEntries = @json($transaction->entries);
        
        // Define updateTotals in global scope
        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;
            
            $('.entry-debit').each(function() {
                const val = parseFloat($(this).val()) || 0;
                totalDebit += val;
            });
            
            $('.entry-credit').each(function() {
                const val = parseFloat($(this).val()) || 0;
                totalCredit += val;
            });
            
            $('#totalDebit').text(totalDebit.toFixed(2));
            $('#totalCredit').text(totalCredit.toFixed(2));
            
            // Show warning if not balanced
            if (Math.abs(totalDebit - totalCredit) > 0.01 && (totalDebit > 0 || totalCredit > 0)) {
                $('#balanceWarning').show();
            } else {
                $('#balanceWarning').hide();
            }
        }
        
        function addEntry(accountId = '', memo = '', amount = '', type = 'debit') {
            const row = `
                <tr data-index="${entryIndex}">
                    <td>
                        <select name="entries[${entryIndex}][account_id]" class="form-control form-control-sm entry-account" required>
                            <option value="">Select Account</option>
                            ${accounts.map(acc => `<option value="${acc.id}" ${acc.id == accountId ? 'selected' : ''}>${acc.code} - ${acc.name}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="entries[${entryIndex}][memo]" class="form-control form-control-sm" value="${memo || ''}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-right entry-amount entry-debit" 
                            data-type="debit" data-index="${entryIndex}" value="${type === 'debit' ? amount : ''}">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-right entry-amount entry-credit" 
                            data-type="credit" data-index="${entryIndex}" value="${type === 'credit' ? amount : ''}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-entry">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            $('#entriesBody').append(row);
            
            // Add hidden inputs for type and amount if amount exists
            if (amount) {
                const hiddenInputs = `
                    <input type="hidden" name="entries[${entryIndex}][type]" value="${type}">
                    <input type="hidden" name="entries[${entryIndex}][amount]" value="${amount}">
                `;
                $(`tr[data-index="${entryIndex}"]`).append(hiddenInputs);
            }
            
            entryIndex++;
            updateTotals();
        }
        
        $(document).ready(function() {
            // Load existing entries
            existingEntries.forEach(entry => {
                addEntry(entry.account_id, entry.memo, entry.amount, entry.type);
            });
            
            // If no entries exist, add 2 blank ones
            if (existingEntries.length === 0) {
                addEntry();
                addEntry();
            }
            
            // Add entry button
            $('#addEntry').click(function() {
                addEntry();
            });
            
            // Remove entry
            $(document).on('click', '.remove-entry', function() {
                if ($('#entriesBody tr').length <= 2) {
                    alert('A transaction must have at least 2 entries.');
                    return;
                }
                $(this).closest('tr').remove();
                updateTotals();
            });
            
            // Handle amount input - ensure only one column has value
            $(document).on('input', '.entry-amount', function() {
                const index = $(this).data('index');
                const type = $(this).data('type');
                const value = $(this).val();
                
                if (value) {
                    const otherType = type === 'debit' ? 'credit' : 'debit';
                    $(`.entry-${otherType}[data-index="${index}"]`).val('');
                    
                    // Add/update hidden input for the selected type
                    const row = $(this).closest('tr');
                    row.find('input[name*="[type]"]').remove();
                    row.find('input[name*="[amount]"]').remove();
                    
                    row.append(`
                        <input type="hidden" name="entries[${index}][type]" value="${type}">
                        <input type="hidden" name="entries[${index}][amount]" value="${value}">
                    `);
                }
                
                updateTotals();
            });
        });
    </script>
@stop
