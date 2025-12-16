<div class="btn-group btn-group-sm">
    <a href="{{ route('sales.show', $invoice->id) }}" class="btn btn-info" title="View">
        <i class="fas fa-eye"></i>
    </a>
    <a href="{{ route('sales.print', $invoice->id) }}" class="btn btn-secondary" target="_blank" title="Print">
        <i class="fas fa-print"></i>
    </a>
    @if($invoice->delivery_status === 'pending')
        <a href="{{ route('sales.edit', $invoice->id) }}" class="btn btn-warning" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
    @endif
    @if($invoice->delivery_status === 'pending' && $invoice->payments->isEmpty())
        <button type="button" class="btn btn-danger delete-invoice-btn" data-id="{{ $invoice->id }}" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    @endif
</div>