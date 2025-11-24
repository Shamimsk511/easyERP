@extends('adminlte::page')

@section('plugins.Select2', true)

@section('title', 'Create Transaction')

@section('content_header')
    <h1>Create New Transaction</h1>
@stop

@section('content')
    <form action="{{ route('transactions.store') }}" method="POST" id="transactionForm">
        @csrf
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Transaction Details</h3>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="date" 
                                   class="form-control @error('date') is-invalid @enderror" 
                                   value="{{ old('date', date('Y-m-d')) }}" 
                                   required tabindex="1">
                            @error('date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reference">Reference</label>
                            <input type="text" name="reference" id="reference" 
                                   class="form-control @error('reference') is-invalid @enderror" 
                                   value="{{ old('reference') }}" 
                                   placeholder="Invoice #, Check #, etc."
                                   tabindex="2">
                            @error('reference')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" 
                                    class="form-control @error('status') is-invalid @enderror"
                                    tabindex="3">
                                <option value="posted" {{ old('status', 'posted') == 'posted' ? 'selected' : '' }}>Posted</option>
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="text-danger">*</span></label>
                    <input type="text" name="description" id="description" 
                           class="form-control @error('description') is-invalid @enderror" 
                           value="{{ old('description') }}" 
                           placeholder="Transaction description"
                           required tabindex="4">
                    @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" rows="2" 
                              class="form-control @error('notes') is-invalid @enderror" 
                              placeholder="Additional notes (optional)"
                              tabindex="5">{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Account Type Legend -->
        <div class="card bg-light">
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-12">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Quick Guide:</strong>
                            <span class="ml-2">
                                <span class="badge badge-danger">Increase</span> = 
                                Assets↑, Expenses↑ | 
                                Liabilities↓, Income↓, Equity↓
                            </span>
                            <span class="ml-2">|</span>
                            <span class="ml-2">
                                <span class="badge badge-success">Decrease</span> = 
                                Assets↓, Expenses↓ | 
                                Liabilities↑, Income↑, Equity↑
                            </span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Journal Entries</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-success" id="addEntry" tabindex="-1">
                        <i class="fas fa-plus"></i> Add Entry
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                @error('entries')
                    <div class="alert alert-danger m-2">{{ $message }}</div>
                @enderror
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="entriesTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="35%">Account <span class="text-danger">*</span></th>
                                <th width="25%">Memo</th>
                                <th width="15%" class="text-center">
                                    <div>Increase</div>
                                    <small class="text-muted font-weight-normal">(Debit)</small>
                                    <i class="fas fa-question-circle text-info ml-1" 
                                       data-toggle="tooltip" 
                                       title="Increases: Assets, Expenses | Decreases: Liabilities, Income, Equity"></i>
                                </th>
                                <th width="15%" class="text-center">
                                    <div>Decrease</div>
                                    <small class="text-muted font-weight-normal">(Credit)</small>
                                    <i class="fas fa-question-circle text-info ml-1" 
                                       data-toggle="tooltip" 
                                       title="Increases: Liabilities, Income, Equity | Decreases: Assets, Expenses"></i>
                                </th>
                                <th width="10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="entriesBody">
                            <!-- Entries will be added here dynamically -->
                        </tbody>
                        <tfoot class="thead-light">
                            <tr class="font-weight-bold">
                                <td colspan="2" class="text-right">Total:</td>
                                <td class="text-right">
                                    <span class="text-danger" id="totalDebit">0.00</span>
                                    <br><small class="text-muted">(Dr)</small>
                                </td>
                                <td class="text-right">
                                    <span class="text-success" id="totalCredit">0.00</span>
                                    <br><small class="text-muted">(Cr)</small>
                                </td>
                                <td></td>
                            </tr>
                            <tr id="balanceWarning" class="bg-warning" style="display: none;">
                                <td colspan="5" class="text-center text-danger font-weight-bold py-2">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Both sides must be equal! (Double-entry bookkeeping)
                                    <span id="balanceDifference"></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-footer">
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-save"></i> Create Transaction
                </button>
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                
                <div class="float-right">
                    <small class="text-muted">
                        <i class="fas fa-keyboard"></i> 
                        <kbd>Tab</kbd> to navigate | <kbd>Enter</kbd> on amounts to add row | <kbd>Ctrl+Enter</kbd> to submit
                    </small>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Hidden template for account options -->
    <template id="accountOptionsTemplate">
        <option value="">Select Account</option>
        @foreach($accounts as $account)
            <option value="{{ $account->id }}" 
                    data-code="{{ $account->code }}" 
                    data-name="{{ $account->name }}" 
                    data-type="{{ $account->type }}">
                {{ $account->code }} - {{ $account->name }}
            </option>
        @endforeach
    </template>
@stop

@section('css')
    <style>
        /* Select2 improvements */
        .select2-container--bootstrap4 .select2-selection {
            height: calc(2.25rem + 2px) !important;
            border: 1px solid #ced4da;
        }
        
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem) !important;
            padding-left: 12px;
        }
        
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem) !important;
        }
        
        /* Make select2 full width in table */
        .select2-container {
            width: 100% !important;
        }
        
        /* Entry row styling */
        #entriesBody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Amount input styling */
        .entry-amount {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        
        .entry-amount.has-value {
            background-color: #e7f3ff;
        }
        
        /* Account type badges in select2 */
        .account-type-badge {
            font-size: 0.7em;
            padding: 2px 5px;
            margin-left: 5px;
            border-radius: 3px;
        }
        
        .type-asset { background-color: #17a2b8; color: white; }
        .type-liability { background-color: #ffc107; color: #333; }
        .type-equity { background-color: #6c757d; color: white; }
        .type-income { background-color: #28a745; color: white; }
        .type-expense { background-color: #dc3545; color: white; }
        
        /* Dynamic account effect indicator */
        .account-effect {
            display: none;
            font-size: 0.75em;
            margin-top: 2px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .account-effect.show {
            display: inline-block;
        }
        
        .effect-increase {
            background-color: #d4edda;
            color: #155724;
        }
        
        .effect-decrease {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Focus states */
        .entry-account:focus,
        .entry-amount:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Table cell padding */
        #entriesTable td {
            padding: 0.5rem;
            vertical-align: middle;
        }
        
        /* Balance warning animation */
        #balanceWarning {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        /* Keyboard hint */
        kbd {
            background-color: #f4f4f4;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.2);
            color: #333;
            display: inline-block;
            font-size: 11px;
            line-height: 1.4;
            margin: 0 2px;
            padding: 2px 5px;
        }
        
        /* Tooltip styling */
        .tooltip-inner {
            max-width: 300px;
            text-align: left;
        }
        
        /* Select2 results custom styling */
        .select2-results__option {
            padding: 6px 12px;
        }
        
        .select2-container--bootstrap4 .select2-results__option--highlighted {
            background-color: #007bff;
            color: white;
        }
    </style>
@stop

@section('js')
    
    <script>
        let entryIndex = 0;
        let currentTabIndex = 100;
        const accounts = @json($accounts);
        const preselectedAccountId = {{ $preselectedAccountId ?? 'null' }};
        // Account type effect mapping
        const accountEffects = {
            'asset': { debit: 'increase', credit: 'decrease' },
            'expense': { debit: 'increase', credit: 'decrease' },
            'liability': { debit: 'decrease', credit: 'increase' },
            'equity': { debit: 'decrease', credit: 'increase' },
            'income': { debit: 'decrease', credit: 'increase' }
        };
        
        function formatAccountOption(state) {
            if (!state.id) {
                return state.text;
            }
            
            var $state = $(state.element);
            var type = $state.data('type');
            var code = $state.data('code');
            
            if (!type) {
                return state.text;
            }
            
            var typeClass = 'type-' + type;
            var $option = $(
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<span><strong>' + code + '</strong> ' + $state.data('name') + '</span>' +
                    '<span class="account-type-badge ' + typeClass + '">' + type.toUpperCase() + '</span>' +
                '</div>'
            );
            
            return $option;
        }
        
        function formatAccountSelection(state) {
            if (!state.id) {
                return state.text;
            }
            
            var $state = $(state.element);
            var code = $state.data('code');
            var name = $state.data('name');
            
            return $('<span><strong>' + code + '</strong> ' + name + '</span>');
        }
        
        function updateAccountEffect(rowIndex) {
            const row = $(`tr[data-index="${rowIndex}"]`);
            const accountSelect = row.find('.entry-account');
            const selectedOption = accountSelect.find('option:selected');
            const accountType = selectedOption.data('type');
            
            if (!accountType) return;
            
            // Remove existing effect indicators
            row.find('.account-effect').remove();
            
            const effects = accountEffects[accountType];
            if (effects) {
                const debitEffect = `<span class="account-effect effect-${effects.debit}">← ${effects.debit}s this account</span>`;
                const creditEffect = `<span class="account-effect effect-${effects.credit}">← ${effects.credit}s this account</span>`;
                
                row.find('.entry-debit').parent().append(debitEffect);
                row.find('.entry-credit').parent().append(creditEffect);
            }
        }
        
        function showAccountEffectOnInput(input) {
            const row = input.closest('tr');
            const type = input.data('type');
            
            // Show the relevant effect indicator
            row.find('.account-effect').removeClass('show');
            
            if (input.val()) {
                const effectIndicator = type === 'debit' ? 
                    row.find('.entry-debit').parent().find('.account-effect') :
                    row.find('.entry-credit').parent().find('.account-effect');
                
                effectIndicator.addClass('show');
            }
        }
        
        function initSelect2(selectElement) {
            $(selectElement).select2({
                theme: 'bootstrap4',
                placeholder: 'Select an account',
                allowClear: true,
                width: '100%',
                templateResult: formatAccountOption,
                templateSelection: formatAccountSelection,
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }
                    
                    if (typeof data.text === 'undefined') {
                        return null;
                    }
                    
                    const term = params.term.toLowerCase();
                    const text = data.text.toLowerCase();
                    const $option = $(data.element);
                    const code = ($option.data('code') || '').toString().toLowerCase();
                    const name = ($option.data('name') || '').toString().toLowerCase();
                    const type = ($option.data('type') || '').toString().toLowerCase();
                    
                    if (text.indexOf(term) > -1 || code.indexOf(term) > -1 || 
                        name.indexOf(term) > -1 || type.indexOf(term) > -1) {
                        return data;
                    }
                    
                    return null;
                }
            });
            
            $(selectElement).on('select2:select', function(e) {
                const row = $(this).closest('tr');
                const index = row.data('index');
                const memoField = row.find('.entry-memo');
                
                updateAccountEffect(index);
                
                setTimeout(() => memoField.focus(), 100);
            });
            
            $(selectElement).on('select2:open', function(e) {
                setTimeout(function() {
                    document.querySelector('.select2-search__field').focus();
                }, 100);
            });
        }
        
        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;
            
            $('.entry-debit').each(function() {
                const val = parseFloat($(this).val()) || 0;
                totalDebit += val;
                
                if (val > 0) {
                    $(this).addClass('has-value');
                    showAccountEffectOnInput($(this));
                } else {
                    $(this).removeClass('has-value');
                }
            });
            
            $('.entry-credit').each(function() {
                const val = parseFloat($(this).val()) || 0;
                totalCredit += val;
                
                if (val > 0) {
                    $(this).addClass('has-value');
                    showAccountEffectOnInput($(this));
                } else {
                    $(this).removeClass('has-value');
                }
            });
            
            $('#totalDebit').text(totalDebit.toFixed(2));
            $('#totalCredit').text(totalCredit.toFixed(2));
            
            const difference = Math.abs(totalDebit - totalCredit);
            if (difference > 0.01 && (totalDebit > 0 || totalCredit > 0)) {
                $('#balanceWarning').show();
                $('#balanceDifference').text(`(Difference: ${difference.toFixed(2)})`);
            } else {
                $('#balanceWarning').hide();
            }
        }
        
       function addEntry(accountId = '', memo = '', amount = '', type = 'debit') {
    const accountOptions = $('#accountOptionsTemplate').html();
    
    const row = `
        <tr data-index="${entryIndex}">
            <td>
                <select name="entries[${entryIndex}][account_id]" 
                        class="form-control form-control-sm entry-account" 
                        id="account_${entryIndex}"
                        tabindex="${currentTabIndex}"
                        required>
                    ${accountOptions}
                </select>
            </td>
            <td>
                <input type="text" 
                       name="entries[${entryIndex}][memo]" 
                       class="form-control form-control-sm entry-memo" 
                       value="${memo}"
                       placeholder="Optional memo"
                       tabindex="${currentTabIndex + 1}">
            </td>
            <td>
                <input type="number" 
                       step="0.01" 
                       min="0" 
                       class="form-control form-control-sm text-right entry-amount entry-debit" 
                       data-type="debit" 
                       data-index="${entryIndex}" 
                       value="${type === 'debit' ? amount : ''}"
                       placeholder="0.00"
                       tabindex="${currentTabIndex + 2}">
            </td>
            <td>
                <input type="number" 
                       step="0.01" 
                       min="0" 
                       class="form-control form-control-sm text-right entry-amount entry-credit" 
                       data-type="credit" 
                       data-index="${entryIndex}" 
                       value="${type === 'credit' ? amount : ''}"
                       placeholder="0.00"
                       tabindex="${currentTabIndex + 3}">
            </td>
            <td class="text-center">
                <button type="button" 
                        class="btn btn-danger btn-sm remove-entry" 
                        title="Remove entry"
                        tabindex="-1">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#entriesBody').append(row);
    
    // Initialize Select2 on the new select element
    initSelect2(`#account_${entryIndex}`);
    
    if (accountId) {
        // Set the value and trigger change to update Select2
        $(`#account_${entryIndex}`).val(accountId).trigger('change');
        updateAccountEffect(entryIndex);
        
        // Focus on the next field (memo or amount)
        setTimeout(() => {
            if (!memo && !amount) {
                $(`tr[data-index="${entryIndex}"]`).find('.entry-memo').focus();
            }
        }, 100);
    }
    
    entryIndex++;
    currentTabIndex += 4;
    updateTotals();
}

        
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
    // Add initial 2 entries
    if (preselectedAccountId) {
        // If there's a preselected account, add it as the first entry
        addEntry(preselectedAccountId, '', '', 'debit');
        addEntry(); // Add empty second entry
    } else {
        addEntry();
        addEntry();
    }
            
            // Focus on first account after page load
            // setTimeout(() => {
            //     $('#account_0').select2('open');
            // }, 300);
            
            // Add entry button
            $('#addEntry').click(function() {
                addEntry();
                setTimeout(() => {
                    $(`#account_${entryIndex - 1}`).select2('open');
                }, 100);
            });
            
            // Remove entry
            $(document).on('click', '.remove-entry', function() {
                if ($('#entriesBody tr').length <= 2) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Remove',
                        text: 'A transaction must have at least 2 entries (double-entry bookkeeping).',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
                
                const row = $(this).closest('tr');
                Swal.fire({
                    title: 'Remove Entry?',
                    text: 'Are you sure you want to remove this entry?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        row.remove();
                        updateTotals();
                    }
                });
            });
            
            // Handle amount input
            $(document).on('input', '.entry-amount', function() {
                const index = $(this).data('index');
                const type = $(this).data('type');
                const value = $(this).val();
                
                if (value) {
                    const otherType = type === 'debit' ? 'credit' : 'debit';
                    $(`.entry-${otherType}[data-index="${index}"]`).val('');
                    
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
            
            // Enter key on amount fields
            $(document).on('keypress', '.entry-amount', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const currentRow = $(this).closest('tr');
                    const isLastRow = currentRow.is(':last-child');
                    
                    if (isLastRow) {
                        addEntry();
                        setTimeout(() => {
                            $(`#account_${entryIndex - 1}`).select2('open');
                        }, 100);
                    } else {
                        const nextRow = currentRow.next();
                        nextRow.find('.entry-account').select2('open');
                    }
                }
            });
            
            // Form submission
            $('#transactionForm').submit(function(e) {
                const totalDebit = parseFloat($('#totalDebit').text());
                const totalCredit = parseFloat($('#totalCredit').text());
                
                if (Math.abs(totalDebit - totalCredit) > 0.01) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Transaction Not Balanced',
                        html: 'Both sides must be equal in double-entry bookkeeping.<br><small>Increase side must equal Decrease side.</small>',
                        confirmButtonColor: '#d33'
                    });
                    return false;
                }
                
                let allAccountsSelected = true;
                $('.entry-account').each(function() {
                    if (!$(this).val()) {
                        allAccountsSelected = false;
                    }
                });
                
                if (!allAccountsSelected) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Accounts',
                        text: 'Please select an account for all entries.',
                        confirmButtonColor: '#d33'
                    });
                    return false;
                }
                
                Swal.fire({
                    title: 'Creating Transaction...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });
            
            // Ctrl+Enter to submit
            $(document).keydown(function(e) {
                if ((e.ctrlKey || e.metaKey) && e.keyCode == 13) {
                    $('#submitBtn').click();
                }
            });
        });
    </script>
@stop
