<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #1a5276; }
        .company-tagline { font-size: 11px; color: #666; }
        
        /* Invoice Info Row */
        .info-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box h4 { background: #f5f5f5; padding: 5px 10px; margin-bottom: 8px; font-size: 11px; text-transform: uppercase; }
        .info-box p { padding: 0 10px; margin-bottom: 3px; }
        
        /* Invoice Title */
        .invoice-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; padding: 10px; background: #1a5276; color: white; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; font-size: 11px; text-transform: uppercase; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .alt-qty { background: #e3f2fd; font-weight: bold; color: #1565c0; }
        
        /* Totals */
        .totals-section { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; }
        .totals-table td { padding: 5px 10px; }
        .totals-table .total-row { background: #1a5276; color: white; font-weight: bold; font-size: 14px; }
        .totals-table .label { text-align: right; }
        .totals-table .value { text-align: right; width: 100px; }
        
        /* Additional Charges */
        .charges-label { color: #666; font-size: 10px; }
        
        /* Footer */
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; }
        .footer-row { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { width: 200px; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        
        /* Notes */
        .notes { background: #fff9e6; padding: 10px; margin-top: 15px; border-left: 3px solid #ffc107; }
        .notes-title { font-weight: bold; margin-bottom: 5px; }
        
        /* Print styles */
        @media print {
            body { font-size: 11px; }
            .container { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Button -->
        <div class="no-print" style="text-align: right; margin-bottom: 10px;">
            <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
                🖨️ Print Invoice
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-left: 10px;">
                ✕ Close
            </button>
        </div>

        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}</div>
            <div class="company-tagline">Tiles | Sanitary | Building Materials</div>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">TAX INVOICE</div>

        <!-- Info Row -->
        <div class="info-row">
            <div class="info-box">
                <h4>Bill To</h4>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                <p>{{ $invoice->customer->phone }}</p>
                @if($invoice->customer->address)
                    <p>{{ $invoice->customer->address }}</p>
                @endif
            </div>
            <div class="info-box" style="text-align: right;">
                <h4>Invoice Details</h4>
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}</p>
                @if($invoice->due_date)
                    <p><strong>Due Date:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
                @endif
                <p><strong>Previous Balance:</strong> {{ number_format($invoice->outstanding_at_creation, 2) }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Description</th>
                    <th class="text-center" style="width: 12%;">Quantity</th>
                    <th class="text-center" style="width: 15%;">Alt Qty (Box+Pcs)</th>
                    <th class="text-right" style="width: 12%;">Rate</th>
                    <th class="text-center" style="width: 8%;">Disc</th>
                    <th class="text-right" style="width: 13%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $sno = 1; @endphp
                @foreach($invoice->productItems as $item)
                    <tr>
                        <td class="text-center">{{ $sno++ }}</td>
                        <td>
                            <strong>{{ $item->product->name ?? $item->description }}</strong>
                            @if($item->product?->code)
                                <br><small style="color: #666;">{{ $item->product->code }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ number_format($item->quantity, 2) }}
                            {{ $item->unit->symbol ?? $item->product->baseUnit->symbol ?? '' }}
                        </td>
                        <td class="text-center alt-qty">
                            {{ $item->alt_qty_display ?: '-' }}
                        </td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-center">
                            {{ $item->discount_percent > 0 ? number_format($item->discount_percent, 1) . '%' : '-' }}
                        </td>
                        <td class="text-right"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
                    </tr>
                @endforeach

                @foreach($invoice->passiveItems as $item)
                    <tr style="background: #f0f8ff;">
                        <td class="text-center">{{ $sno++ }}</td>
                        <td colspan="4">{{ $item->description }}</td>
                        <td class="text-center">-</td>
                        <td class="text-right"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->labour_amount > 0)
                    <tr>
                        <td class="label">
                            Labour Charges:
                            @if($invoice->labourAccount)
                                <span class="charges-label">({{ $invoice->labourAccount->name }})</span>
                            @endif
                        </td>
                        <td class="value">{{ number_format($invoice->labour_amount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->transportation_amount > 0)
                    <tr>
                        <td class="label">
                            Transportation:
                            @if($invoice->transportationAccount)
                                <span class="charges-label">({{ $invoice->transportationAccount->name }})</span>
                            @endif
                        </td>
                        <td class="value">{{ number_format($invoice->transportation_amount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->discount_amount > 0)
                    <tr style="color: #c0392b;">
                        <td class="label">Discount:</td>
                        <td class="value">-{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if($invoice->round_off_amount != 0)
                    <tr>
                        <td class="label">Round Off:</td>
                        <td class="value">{{ number_format($invoice->round_off_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label" style="border: none;">GRAND TOTAL:</td>
                    <td class="value" style="border: none;">{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Balance Summary -->
        <div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; width: 50%;">
                        <strong>Previous Balance:</strong> {{ number_format($invoice->outstanding_at_creation, 2) }}
                    </td>
                    <td style="border: none; width: 50%; text-align: right;">
                        <strong>Current Invoice:</strong> {{ number_format($invoice->total_amount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border: none; text-align: right; font-size: 14px; padding-top: 10px;">
                        <strong>Total Outstanding: {{ number_format($invoice->outstanding_at_creation + $invoice->total_amount - $invoice->total_paid, 2) }}</strong>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        @if($invoice->customer_notes)
            <div class="notes">
                <div class="notes-title">Notes:</div>
                {{ $invoice->customer_notes }}
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-row">
                <div class="signature-box">
                    <div class="signature-line">Customer Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">Authorized Signature</div>
                </div>
            </div>
            <p style="text-align: center; margin-top: 20px; color: #666; font-size: 10px;">
                Thank you for your business! | Goods once sold will not be taken back.
            </p>
        </div>
    </div>
</body>
</html>