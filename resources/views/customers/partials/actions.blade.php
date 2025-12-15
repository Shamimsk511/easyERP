<div class="btn-group">
    <a href="{{ route('customers.show', $customer) }}" class="btn btn-info btn-sm" title="View">
        <i class="fas fa-eye"></i>
    </a>
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    <a href="{{ route('customers.ledger', $customer) }}" class="btn btn-secondary btn-sm" title="Ledger">
        <i class="fas fa-book"></i>
    </a>
    @if($customer->current_due_date && $customer->current_balance > 0)
        <button type="button" class="btn btn-warning btn-sm extend-due" 
                data-id="{{ $customer->id }}" 
                data-name="{{ $customer->name }}"
                data-due="{{ $customer->current_due_date ? \Carbon\Carbon::parse($customer->current_due_date)->format('d M Y') : '' }}"
                title="Extend Due Date">
            <i class="fas fa-calendar-plus"></i>
        </button>
    @endif
    <button type="button" class="btn btn-danger btn-sm delete-customer" 
            data-id="{{ $customer->id }}" 
            data-name="{{ $customer->name }}" 
            title="Delete">
        <i class="fas fa-trash"></i>
    </button>
</div>