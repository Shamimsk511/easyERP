<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delivery Challan {{ $delivery->challan_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.5; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; }
        .challan-title { font-size: 18px; font-weight: bold; margin: 20px 0; text-align: center; }
        .info-row { display: flex; justify-content: space-between; margin: 10px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-name">{{ $company->company_name ?? 'Your Company' }}</div>
            <div style="font-size: 12px; color: #666;">{{ $company->address ?? 'Address' }} | Phone: {{ $company->phone ?? '' }}</div>
        </div>

        <div class="challan-title">DELIVERY CHALLAN</div>

        <div class="info-row">
            <span><strong>Challan #:</strong> {{ $delivery->challan_number }}</span>
            <span><strong>Date:</strong> {{ $delivery->delivery_date->format('d M Y') }}</span>
        </div>

        <div class="info-row">
            <span><strong>Invoice #:</strong> {{ $delivery->invoice->invoice_number }}</span>
            <span><strong>Invoice Date:</strong> {{ $delivery->invoice->invoice_date->format('d M Y') }}</span>
        </div>

        <div style="margin: 20px 0; border: 1px solid #ddd; padding: 10px;">
            <div><strong>Bill To:</strong></div>
            <div>{{ $delivery->invoice->customer->name }}</div>
            <div>{{ $delivery->invoice->customer->phone }}</div>
            <div>{{ $delivery->invoice->customer->address }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>S.L.</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th class="text-right">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($delivery->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->invoiceItem->description }}</td>
                    <td>{{ $item->invoiceItem->unit->symbol ?? 'Unit' }}</td>
                    <td class="text-right">{{ $item->delivered_quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($delivery->notes)
        <div style="margin: 20px 0;">
            <strong>Notes:</strong> {{ $delivery->notes }}
        </div>
        @endif

        <div style="margin-top: 40px; display: flex; justify-content: space-between; font-size: 12px;">
            <div>
                <p style="margin-bottom: 40px;">Prepared By: ___________</p>
                <p style="margin: 0;">Date: ___________</p>
            </div>
            <div>
                <p style="margin-bottom: 40px;">Delivered By: ___________</p>
                <p style="margin: 0;">Signature: ___________</p>
            </div>
            <div>
                <p style="margin-bottom: 40px;">Received By: ___________</p>
                <p style="margin: 0;">Signature: ___________</p>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
