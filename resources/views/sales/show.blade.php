@extends('adminlte::page')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-invoice"></i> Invoice {{ $invoice->invoice_number }}</h1>
        <div>
            <a href="{{ route('sales.print', $invoice) }}" class="btn btn-secondary" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
            @if($invoice->delivery_status === 'pending')
                <a href="{{ route('sales.edit', $invoice) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('sales.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Invoice Details -->
        <div class="col-md-8">
            <!-- Header Info -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-primary">{{ $invoice->customer->name }}</h5>
                            <p class="mb-1">{{ $invoice->customer->phone }}</p>
                            <p class="mb-1 text-muted">{{ $invoice->customer->address }}</p>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                            <p class="mb-1"><strong>Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}</p>
                            @if($invoice->due_date)
                                <p class="mb-1"><strong>Due:</strong> {{ $invoice->due_date->format('d M Y') }}</p>
                            @endif
                            <p class="mb-0">
                                <strong>Status:</strong>
                                @switch($invoice->delivery_status)
                                    @case('pending')
                                        <span class="badge badge-warning">Pending</span>
                                        @break
                                    @case('partial')
                                        <span class="badge badge-info">Partial Delivery</span>
                                        @break
                                    @case('delivered')
                                        <span class="badge badge-success">Delivered</span>
                                        @break
                                @endswitch
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Items</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Alt Qty (Box + Pcs)</th>
                                <th class="text-right">Price</th>
                                <th class="text-center">Disc %</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Delivered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->productItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product->name ?? $item->description }}</strong>
                                        @if($item->product?->code)
                                            <br><small class="text-muted">{{ $item->product->code }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($item->quantity, 2) }}
                                        {{ $item->unit->symbol ?? $item->product->baseUnit->symbol ?? '' }}
                                    </td>
                                    <td class="text-center">
                                        @if($item->alt_qty_display)
                                            <span class="badge badge-info">{{ $item->alt_qty_display }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-center">
                                        {{ $item->discount_percent > 0 ? number_format($item->discount_percent, 1) . '%' : '-' }}
                                    </td>
                                    <td class="text-right font-weight-bold">{{ number_format($item->line_total, 2) }}</td>
                                    <td class="text-center">
                                        @if($item->delivered_quantity >= $item->quantity)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Full
                                            </span>
                                        @elseif($item->delivered_quantity > 0)
                                            <span class="badge badge-warning">
                                                {{ number_format($item->delivered_quantity, 2) }} / {{ number_format($item->quantity, 2) }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            @foreach($invoice->passiveItems as $item)
                                <tr class="table-info">
                                    <td colspan="5">
                                        <i class="fas fa-concierge-bell"></i>
                                        {{ $item->description }}
                                        @if($item->passiveAccount)
                                            <small class="text-muted">({{ $item->passiveAccount->name }})</small>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold">{{ number_format($item->line_total, 2) }}</td>
                                    <td class="text-center">-</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Accounting Entries -->
            @if($invoice->transaction && $invoice->transaction->entries->count() > 0)
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book"></i> Ledger Entries</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->transaction->entries as $entry)
                                    <tr>
                                        <td>
                                            {{ $entry->account->code }} - {{ $entry->account->name }}
                                            @if($entry->memo)
                                                <br><small class="text-muted">{{ $entry->memo }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            {{ $entry->type === 'debit' ? number_format($entry->amount, 2) : '' }}
                                        </td>
                                        <td class="text-right">
                                            {{ $entry->type === 'credit' ? number_format($entry->amount, 2) : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar: Summary & Actions -->
        <div class="col-md-4">
            <!-- Totals Card -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator"></i> Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($invoice->labour_amount > 0)
                            <tr>
                                <td>
                                    <i class="fas fa-hard-hat text-warning"></i> Labour:
                                    @if($invoice->labourAccount)
                                        <br><small class="text-muted">{{ $invoice->labourAccount->name }}</small>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($invoice->labour_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($invoice->transportation_amount > 0)
                            <tr>
                                <td>
                                    <i class="fas fa-truck text-info"></i> Transportation:
                                    @if($invoice->transportationAccount)
                                        <br><small class="text-muted">{{ $invoice->transportationAccount->name }}</small>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($invoice->transportation_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($invoice->discount_amount > 0)
                            <tr class="text-danger">
                                <td>Discount:</td>
                                <td class="text-right">-{{ number_format($invoice->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($invoice->round_off_amount != 0)
                            <tr>
                                <td>Round Off:</td>
                                <td class="text-right">{{ number_format($invoice->round_off_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-top">
                            <td><strong>Grand Total:</strong></td>
                            <td class="text-right"><h4 class="mb-0 text-primary">{{ number_format($invoice->total_amount, 2) }}</h4></td>
                        </tr>
                        <tr>
                            <td>Paid:</td>
                            <td class="text-right text-success">{{ number_format($invoice->total_paid, 2) }}</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Balance Due:</strong></td>
                            <td class="text-right">
                                <h5 class="mb-0 {{ $invoice->outstanding_balance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($invoice->outstanding_balance, 2) }}
                                </h5>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Accounts Used -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book"></i> Accounts</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Sales A/c:</strong><br>
                        <span class="text-muted">{{ $invoice->salesAccount?->code }} - {{ $invoice->salesAccount?->name ?? 'Default Sales' }}</span>
                    </p>
                    @if($invoice->labourAccount)
                        <p class="mb-2">
                            <strong>Labour A/c:</strong><br>
                            <span class="text-muted">{{ $invoice->labourAccount->code }} - {{ $invoice->labourAccount->name }}</span>
                        </p>
                    @endif
                    @if($invoice->transportationAccount)
                        <p class="mb-2">
                            <strong>Transportation A/c:</strong><br>
                            <span class="text-muted">{{ $invoice->transportationAccount->code }} - {{ $invoice->transportationAccount->name }}</span>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt"></i> Actions</h3>
                </div>
                <div class="card-body">
                    @if($invoice->outstanding_balance > 0)
                        <a href="{{ route('payments.create', $invoice) }}" class="btn btn-success btn-block mb-2">
                            <i class="fas fa-money-bill"></i> Record Payment
                        </a>
                    @endif
                    
                    @if($invoice->delivery_status !== 'delivered')
                        <a href="{{ route('deliveries.create', ['invoice' => $invoice->id]) }}" class="btn btn-info btn-block mb-2">
                            <i class="fas fa-truck"></i> Create Delivery
                        </a>
                    @endif

                    @if($invoice->delivery_status === 'pending' && $invoice->payments->isEmpty())
                        <button type="button" class="btn btn-danger btn-block" id="delete-invoice-btn" 
                                data-id="{{ $invoice->id }}">
                            <i class="fas fa-trash"></i> Delete Invoice
                        </button>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($invoice->customer_notes || $invoice->internal_notes)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note"></i> Notes</h3>
                    </div>
                    <div class="card-body">
                        @if($invoice->customer_notes)
                            <p class="mb-2"><strong>Customer Notes:</strong><br>{{ $invoice->customer_notes }}</p>
                        @endif
                        @if($invoice->internal_notes)
                            <p class="mb-0"><strong>Internal Notes:</strong><br>{{ $invoice->internal_notes }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#delete-invoice-btn').on('click', function() {
        const invoiceId = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Invoice?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/sales/${invoiceId}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success')
                                .then(() => window.location.href = '{{ route("sales.index") }}');
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete', 'error');
                    }
                });
            }
        });
    });
});
</script>
@stop