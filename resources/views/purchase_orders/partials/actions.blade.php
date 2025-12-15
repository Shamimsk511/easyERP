<div class="btn-group btn-group-sm" role="group">
    {{-- View Button --}}
    <a href="{{ route('purchase-orders.show', $order->id) }}" 
       class="btn btn-info" 
       title="View Order">
        <i class="fas fa-eye"></i>
    </a>

    {{-- Print Button --}}
    <a href="{{ route('purchase-orders.print', $order->id) }}" 
       class="btn btn-secondary" 
       target="_blank"
       title="Print Order">
        <i class="fas fa-print"></i>
    </a>

    @if($order->status === 'pending')
        {{-- Edit Button (only for pending) --}}
        <a href="{{ route('purchase-orders.edit', $order->id) }}" 
           class="btn btn-primary" 
           title="Edit Order">
            <i class="fas fa-edit"></i>
        </a>

        {{-- Mark as Received Button --}}
        <button type="button" 
                class="btn btn-success receive-btn" 
                data-id="{{ $order->id }}" 
                data-number="{{ $order->order_number }}"
                title="Mark as Received">
            <i class="fas fa-check-circle"></i>
        </button>

        {{-- Delete Button (only for pending) --}}
        <button type="button" 
                class="btn btn-danger delete-btn" 
                data-id="{{ $order->id }}" 
                data-number="{{ $order->order_number }}"
                title="Delete Order">
            <i class="fas fa-trash"></i>
        </button>
    @else
        {{-- View Transaction (for received) --}}
        @if($order->transaction_id)
            <a href="{{ route('transactions.show', $order->transaction_id) }}" 
               class="btn btn-warning" 
               title="View Transaction">
                <i class="fas fa-book"></i>
            </a>
        @endif
    @endif
</div>