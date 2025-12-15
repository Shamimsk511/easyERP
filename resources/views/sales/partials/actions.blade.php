<div class="btn-group">
    <a href="{{ route('sales.show', $invoice) }}" class="btn btn-info btn-sm" title="View">
        <i class="fas fa-eye"></i>
    </a>
    
    @if($invoice->delivery_status === 'pending' && !$invoice->trashed())
        <a href="{{ route('sales.edit', $invoice) }}" class="btn btn-primary btn-sm" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
    @endif
    
    <a href="{{ route('sales.print', $invoice) }}" class="btn btn-secondary btn-sm" title="Print" target="_blank">
        <i class="fas fa-print"></i>
    </a>
    
    @if($invoice->delivery_status === 'pending' && !$invoice->trashed())
        <button type="button" class="btn btn-danger btn-sm delete-invoice" data-id="{{ $invoice->id }}" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    @endif
</div>