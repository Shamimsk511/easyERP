<div class="btn-group btn-group-sm" role="group">
    {{-- View Button --}}
    <a href="{{ route('sales.show', $invoice->id) }}" 
       class="btn btn-info" 
       title="View Invoice">
        <i class="fas fa-eye"></i>
    </a>

    {{-- Print Button --}}
    <a href="{{ route('sales.print', $invoice->id) }}" 
       class="btn btn-secondary" 
       target="_blank" 
       title="Print Invoice">
        <i class="fas fa-print"></i>
    </a>

    {{-- Edit Button (only if pending) --}}
    @if($invoice->delivery_status === 'pending')
        <a href="{{ route('sales.edit', $invoice->id) }}" 
           class="btn btn-primary" 
           title="Edit Invoice">
            <i class="fas fa-edit"></i>
        </a>
    @endif

    {{-- Delivery Button (if not fully delivered) --}}
    @if($invoice->delivery_status !== 'delivered')
        <a href="{{ route('deliveries.create', ['invoice_id' => $invoice->id]) }}" 
           class="btn btn-success" 
           title="Create Delivery">
            <i class="fas fa-truck"></i>
        </a>
    @endif

    {{-- Payment Button (if outstanding balance) --}}
    @if($invoice->outstanding_balance > 0)
        <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" 
           class="btn btn-warning" 
           title="Record Payment">
            <i class="fas fa-money-bill-wave"></i>
        </a>
    @endif

    {{-- Delete Button (only if no deliveries or payments) --}}
    @if($invoice->delivery_status === 'pending' && $invoice->payments->isEmpty())
        <button type="button" 
                class="btn btn-danger delete-btn" 
                data-id="{{ $invoice->id }}" 
                data-invoice="{{ $invoice->invoice_number }}"
                title="Delete Invoice">
            <i class="fas fa-trash"></i>
        </button>
    @endif
</div>