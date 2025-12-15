<div class="btn-group btn-group-sm" role="group">
    {{-- View Button --}}
    <a href="{{ route('vendors.show', $vendor->id) }}" 
       class="btn btn-info" 
       title="View Vendor">
        <i class="fas fa-eye"></i>
    </a>

    {{-- Payment Button --}}
    @if($vendor->current_balance > 0)
        <a href="{{ route('vouchers.payment.create', ['payee_type' => 'vendor', 'vendor_id' => $vendor->id]) }}" 
           class="btn btn-success" 
           title="Make Payment">
            <i class="fas fa-money-bill-wave"></i>
        </a>
    @endif

    {{-- Create PO Button --}}
    <a href="{{ route('purchase-orders.create', ['vendor_id' => $vendor->id]) }}" 
       class="btn btn-primary" 
       title="Create Purchase Order">
        <i class="fas fa-cart-plus"></i>
    </a>

    {{-- Edit Button --}}
    <a href="{{ route('vendors.edit', $vendor->id) }}" 
       class="btn btn-warning" 
       title="Edit Vendor">
        <i class="fas fa-edit"></i>
    </a>

    {{-- Delete/Deactivate Button --}}
    @if($vendor->purchaseOrders->isEmpty())
        <button type="button" 
                class="btn btn-danger delete-btn" 
                data-id="{{ $vendor->id }}" 
                data-name="{{ $vendor->name }}"
                title="Delete Vendor">
            <i class="fas fa-trash"></i>
        </button>
    @else
        <button type="button" 
                class="btn btn-secondary deactivate-btn" 
                data-id="{{ $vendor->id }}" 
                data-name="{{ $vendor->name }}"
                title="Deactivate Vendor (has orders)">
            <i class="fas fa-ban"></i>
        </button>
    @endif
</div>