<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.5; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; }
        .company-address { font-size: 12px; color: #666; }
        .invoice-title { font-size: 18px; font-weight: bold; margin: 20px 0; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
        .info-block { }
        .info-label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .totals { width: 50%; margin-left: auto; margin-right: 0; margin-top: 20px; }
        .totals-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; }
        .totals-row.total { border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; margin: 10px 0; }
        .footer { text-align: center; margin-top: 40px; font-size: 11px; color: #666; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $company->company_name ?? 'Your Company' }}</div>
            <div class="company-address">{{ $company->address ?? 'Address' }} | Phone: {{ $company->phone ?? '' }}</div>
        </div>

        <!-- Invoice Title & Info -->
        <div class="invoice-title">INVOICE</div>
        
        <div class="invoice-info">
            <div class="info-block">
                <p><span class="info-label">Invoice #:</span> {{ $invoice->invoice_number }}</p>
                <p><span class="info-label">Date:</span> {{ $invoice->invoice_date->format('d M Y') }}</p>
                <p><span class="info-label">Due Date:</span> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A' }}</p>
            </div>
            <div class="info-block">
                <p><span class="info-label">Bill To:</span></p>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                <p>{{ $invoice->customer->phone }}</p>
                <p>{{ $invoice->customer->address }}</p>
            </div>
        </div>

        <!-- Line Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">৳ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $item->discount_percent }}%</td>
                    <td class="text-right">৳ {{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>৳ {{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->tax_amount > 0)
            <div class="totals-row">
                <span>Tax:</span>
                <span>৳ {{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>TOTAL:</span>
                <span>৳ {{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>Paid:</span>
                <span>৳ {{ number_format($invoice->total_paid, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>Outstanding:</span>
                <span>৳ {{ number_format($invoice->outstanding_balance, 2) }}</span>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->customer_notes)
        <div style="margin-top: 30px; border: 1px solid #ddd; padding: 10px; font-size: 12px;">
            <strong>Notes:</strong><br>
            {{ $invoice->customer_notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Printed on {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
