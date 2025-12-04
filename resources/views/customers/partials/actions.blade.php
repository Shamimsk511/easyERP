<div class="btn-group">
    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-info" title="View">
        <i class="fas fa-eye"></i>
    </a>
    <a href="{{ route('customers.ledger', $customer->id) }}" class="btn btn-sm btn-success" title="Ledger">
        <i class="fas fa-book"></i>
    </a>
    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-primary" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    @if($customer->current_due_date && $customer->current_due_date->isPast() && $customer->current_balance > 0)
        <button type="button" class="btn btn-sm btn-warning btn-extend-due" 
                data-customer-id="{{ $customer->id }}" 
                data-current-due-date="{{ $customer->current_due_date->format('Y-m-d') }}"
                title="Extend Due Date">
            <i class="fas fa-calendar-plus"></i>
        </button>
    @endif
    
    @php
        // Check if customer has any transactions
        $hasTransactions = \DB::table('transaction_entries')
            ->where('account_id', $customer->ledger_account_id)
            ->exists();
    @endphp
    
    @if($hasTransactions)
        <!-- Show deactivate button if has transactions -->
        <button type="button" class="btn btn-sm btn-warning btn-deactivate" 
                data-customer-id="{{ $customer->id }}" 
                data-customer-name="{{ $customer->name }}"
                title="Deactivate (Has Transactions)">
            <i class="fas fa-ban"></i>
        </button>
    @else
        <!-- Show delete button if no transactions -->
        <button type="button" class="btn btn-sm btn-danger btn-delete" 
                data-customer-id="{{ $customer->id }}" 
                data-customer-name="{{ $customer->name }}"
                title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    @endif
</div>
