<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher - {{ $paymentVoucher->voucher_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1a1a1a;
            background: white;
            padding: 15px;
        }
        
        .receipt-container {
            max-width: 750px;
            margin: 0 auto;
            background: white;
            border: 2px solid #2c3e50;
            padding: 20px;
            position: relative;
        }
        
        /* Header with Logo */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .company-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border: 2px solid #34495e;
            border-radius: 6px;
            padding: 5px;
            background: #f8f9fa;
        }
        
        .company-info-block {
            flex: 1;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .company-info {
            font-size: 9px;
            color: #555;
            line-height: 1.5;
        }
        
        .header-right {
            text-align: right;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 5px;
        }
        
        .status-posted {
            background: #27ae60;
            color: white;
            border: 2px solid #229954;
        }
        
        .status-draft {
            background: #95a5a6;
            color: white;
            border: 2px solid #7f8c8d;
        }
        
        .status-cancelled {
            background: #e74c3c;
            color: white;
            border: 2px solid #c0392b;
        }
        
        /* Voucher Title */
        .voucher-title {
            text-align: center;
            margin: 12px 0;
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 8px;
            background: linear-gradient(to right, #ecf0f1, #bdc3c7, #ecf0f1);
            border-left: 4px solid #3498db;
            border-right: 4px solid #3498db;
        }
        
        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 2px solid #34495e;
        }
        
        .info-table th {
            background: linear-gradient(to bottom, #5d6d7e, #34495e);
            color: white;
            padding: 6px 10px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #2c3e50;
            width: 22%;
        }
        
        .info-table td {
            padding: 6px 10px;
            font-size: 11px;
            color: #1a1a1a;
            border: 1px solid #95a5a6;
            background: white;
            width: 28%;
        }
        
        .info-table tr:nth-child(even) td {
            background: #f8f9fa;
        }
        
        /* Payee Box */
        .payee-box {
            border: 2px solid #3498db;
            padding: 12px;
            margin: 12px 0;
            background: linear-gradient(135deg, #e8f4f8 0%, #d6eaf8 100%);
            border-radius: 6px;
            position: relative;
        }
        
        .payee-box::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: #3498db;
        }
        
        .payee-box .title {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 6px;
            color: #2471a3;
            letter-spacing: 0.5px;
        }
        
        .payee-box .payee-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a5490;
            margin-bottom: 4px;
        }
        
        .payee-box .payee-code {
            font-size: 11px;
            color: #34495e;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .payee-box .payee-details {
            font-size: 10px;
            color: #555;
            line-height: 1.6;
        }
        
        /* Balance Summary Table */
        .balance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            border: 2px solid #34495e;
        }
        
        .balance-table thead th {
            background: linear-gradient(to bottom, #5d6d7e, #34495e);
            color: white;
            padding: 7px 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .balance-table td {
            padding: 8px 10px;
            font-size: 12px;
            border: 1px solid #95a5a6;
        }
        
        .balance-table .label-col {
            width: 60%;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .balance-table .amount-col {
            width: 40%;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }
        
        .balance-table .previous-row {
            background: #fef5e7;
        }
        
        .balance-table .previous-row .amount-col {
            color: #d68910;
        }
        
        .balance-table .payment-row {
            background: #eafaf1;
        }
        
        .balance-table .payment-row .amount-col {
            color: #27ae60;
        }
        
        .balance-table .outstanding-row {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .balance-table .outstanding-row td {
            border-top: 2px solid #34495e;
            padding: 9px 10px;
            font-size: 13px;
        }
        
        .balance-table .outstanding-row .amount-col {
            color: #e74c3c;
            font-size: 15px;
        }
        
        /* Description Box */
        .description-box {
            margin: 12px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            border: 1px solid #bdc3c7;
            border-left: 4px solid #3498db;
            font-size: 10px;
        }
        
        .description-box strong {
            color: #2c3e50;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Accounting Table */
        .accounting-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            border: 2px solid #34495e;
        }
        
        .accounting-table thead {
            background: linear-gradient(to bottom, #5d6d7e, #34495e);
        }
        
        .accounting-table th {
            color: white;
            padding: 7px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-right: 1px solid #2c3e50;
        }
        
        .accounting-table th:last-child {
            border-right: none;
        }
        
        .accounting-table td {
            padding: 6px 8px;
            font-size: 10px;
            border: 1px solid #bdc3c7;
            background: white;
        }
        
        .accounting-table tbody tr:nth-child(even) td {
            background: #f8f9fa;
        }
        
        .accounting-table .text-right {
            text-align: right;
        }
        
        .accounting-table tfoot {
            font-weight: bold;
            background: linear-gradient(to bottom, #ecf0f1, #d5dbdb);
        }
        
        .accounting-table tfoot td {
            border-top: 2px solid #34495e;
            padding: 7px 8px;
            font-size: 11px;
            color: #2c3e50;
        }
        
        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-block {
            text-align: center;
            width: 30%;
        }
        
        .signature-line {
            border-top: 2px solid #34495e;
            margin-top: 40px;
            padding-top: 6px;
            font-size: 10px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .signature-name {
            font-size: 9px;
            color: #7f8c8d;
            margin-top: 3px;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
            border-top: 2px solid #bdc3c7;
            padding-top: 10px;
        }
        
        .footer p {
            margin: 3px 0;
        }
        
        .footer strong {
            color: #34495e;
        }
        
        /* Print Actions */
        .print-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px dashed #bdc3c7;
        }
        
        .print-btn {
            padding: 10px 25px;
            border: 2px solid #34495e;
            background: linear-gradient(to bottom, #ecf0f1, #bdc3c7);
            color: #2c3e50;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .print-btn:hover {
            background: linear-gradient(to bottom, #bdc3c7, #95a5a6);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .print-btn.primary {
            background: linear-gradient(to bottom, #5dade2, #3498db);
            color: white;
            border-color: #2980b9;
        }
        
        .print-btn.primary:hover {
            background: linear-gradient(to bottom, #3498db, #2980b9);
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .receipt-container {
                max-width: none;
                border: none;
                padding: 10px;
            }
            
            .payee-box,
            .balance-table thead th,
            .accounting-table thead,
            .info-table th,
            .status-badge {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .voucher-title {
                background: #e0e0e0 !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    @php
        // Use balance info from controller
        $previousBalance = $balanceInfo['balance_before'] ?? 0;
        $paidAmount = $paymentVoucher->amount;
        $outstandingBalance = $balanceInfo['balance_after'] ?? 0;
    @endphp

    <div class="receipt-container">
        <!-- Header with Logo and Company Info -->
        <div class="header">
            <div class="header-left">
                @if(company_logo())
                    <img src="{{ company_logo() }}" alt="{{ company_name() }}" class="company-logo">
                @else
                    <div style="width: 70px; height: 70px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #999; text-align: center; background: #f8f9fa; border-radius: 6px;">
                        No Logo
                    </div>
                @endif
                
                <div class="company-info-block">
                    <div class="company-name">{{ company_name() }}</div>
                    <div class="company-info">
                        @if(company()->address)
                            <div>{{ company()->address }}</div>
                        @endif
                        <div>
                            @if(company()->phone)Phone: {{ company()->phone }}@endif
                            @if(company()->phone && company()->email) | @endif
                            @if(company()->email)Email: {{ company()->email }}@endif
                        </div>
                        @if(company()->website)
                            <div>{{ company()->website }}</div>
                        @endif
                        @if(company()->bin)
                            <div>BIN: {{ company()->bin }}</div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="header-right">
                <div class="status-badge status-{{ $paymentVoucher->status }}">
                    {{ strtoupper($paymentVoucher->status) }}
                </div>
                <div style="font-size: 9px; color: #7f8c8d; margin-top: 5px;">
                    Created: {{ $paymentVoucher->created_at->format('d-M-Y h:i A') }}
                </div>
            </div>
        </div>

        <!-- Voucher Title -->
        <div class="voucher-title">Payment Voucher</div>

        <!-- Voucher Information Table -->
        <table class="info-table">
            <tr>
                <th>Voucher No.</th>
                <td><strong>{{ $paymentVoucher->voucher_number }}</strong></td>
                <th>Payment Date</th>
                <td>{{ $paymentVoucher->payment_date->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <th>Payment Method</th>
                <td>{{ strtoupper(str_replace('_', ' ', $paymentVoucher->payment_method)) }}</td>
                <th>Amount</th>
                <td><strong style="color: #27ae60; font-size: 13px;">{{ format_currency($paymentVoucher->amount) }}</strong></td>
            </tr>
            @if($paymentVoucher->payment_method === 'cheque' && $paymentVoucher->cheque_number)
            <tr>
                <th>Cheque No.</th>
                <td>{{ $paymentVoucher->cheque_number }}</td>
                <th>Cheque Date</th>
                <td>{{ $paymentVoucher->cheque_date ? $paymentVoucher->cheque_date->format('d-M-Y') : '-' }}</td>
            </tr>
            @if($paymentVoucher->bank_name)
            <tr>
                <th>Bank Name</th>
                <td colspan="3">{{ $paymentVoucher->bank_name }}</td>
            </tr>
            @endif
            @endif
        </table>

        <!-- Payee Details -->
        @if($payeeDetails)
        <div class="payee-box">
            <div class="title">
                {{ $paymentVoucher->payee_type === 'vendor' ? '💼 Paid To Vendor' : '👤 Paid To Customer' }}
            </div>
            
            @if($paymentVoucher->payee_type === 'vendor')
                <div class="payee-name">{{ $payeeDetails->name }}</div>
                <div class="payee-code">Vendor Code: {{ $payeeDetails->vendor_code }}</div>
                <div class="payee-details">
                    @if($payeeDetails->company_name)<strong>Company:</strong> {{ $payeeDetails->company_name }}<br>@endif
                    @if($payeeDetails->phone)<strong>Phone:</strong> {{ $payeeDetails->phone }} @endif
                    @if($payeeDetails->email) | <strong>Email:</strong> {{ $payeeDetails->email }}@endif
                    @if($payeeDetails->address)<br><strong>Address:</strong> {{ $payeeDetails->address }}
                        @if($payeeDetails->city), {{ $payeeDetails->city }}@endif
                        @if($payeeDetails->state), {{ $payeeDetails->state }}@endif
                    @endif
                </div>
            @else
                <div class="payee-name">{{ $payeeDetails->name }}</div>
                <div class="payee-code">Customer Code: {{ $payeeDetails->customer_code ?? 'N/A' }}</div>
                <div class="payee-details">
                    @if($payeeDetails->phone)<strong>Phone:</strong> {{ $payeeDetails->phone }} @endif
                    @if($payeeDetails->email) | <strong>Email:</strong> {{ $payeeDetails->email }}@endif
                    @if($payeeDetails->address)<br><strong>Address:</strong> {{ $payeeDetails->address }}@endif
                </div>
            @endif
        </div>

        <!-- Balance Summary Table -->
        <table class="balance-table">
            <thead>
                <tr>
                    <th colspan="2">📊 BALANCE SUMMARY</th>
                </tr>
            </thead>
            <tbody>
                <tr class="previous-row">
                    <td class="label-col">
                        {{ $paymentVoucher->payee_type === 'vendor' ? 'Previous Payable Balance' : 'Previous Receivable Balance' }}
                    </td>
                    <td class="amount-col">{{ format_currency($previousBalance) }}</td>
                </tr>
                <tr class="payment-row">
                    <td class="label-col">
                        {{ $paymentVoucher->payee_type === 'vendor' ? 'Less: Payment Made' : 'Less: Payment Received' }}
                    </td>
                    <td class="amount-col">({{ format_currency($paidAmount) }})</td>
                </tr>
                <tr class="outstanding-row">
                    <td class="label-col">
                        {{ $paymentVoucher->payee_type === 'vendor' ? 'Outstanding Payable Balance' : 'Outstanding Receivable Balance' }}
                    </td>
                    <td class="amount-col">{{ format_currency($outstandingBalance) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        <!-- Description -->
        @if($paymentVoucher->description)
        <div class="description-box">
            <strong>📝 Purpose:</strong> {{ $paymentVoucher->description }}
            @if($paymentVoucher->notes)
            <br><strong>Note:</strong> {{ $paymentVoucher->notes }}
            @endif
        </div>
        @endif

        <!-- Accounting Entries -->
        @if($paymentVoucher->transaction && $paymentVoucher->transaction->entries->isNotEmpty())
        <table class="accounting-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Code</th>
                    <th style="width: 48%;">Account Name</th>
                    <th style="width: 20%;" class="text-right">Debit</th>
                    <th style="width: 20%;" class="text-right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp
                
                @foreach($paymentVoucher->transaction->entries as $entry)
                <tr>
                    <td><strong>{{ $entry->account->code ?? '-' }}</strong></td>
                    <td>{{ $entry->account->name ?? '-' }}</td>
                    <td class="text-right">
                        @if($entry->type === 'debit')
                            {{ format_currency($entry->amount) }}
                            @php $totalDebit += $entry->amount; @endphp
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($entry->type === 'credit')
                            {{ format_currency($entry->amount) }}
                            @php $totalCredit += $entry->amount; @endphp
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ format_currency($totalDebit) }}</strong></td>
                    <td class="text-right"><strong>{{ format_currency($totalCredit) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @else
        <!-- Fallback Accounting Entries -->
        <table class="accounting-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Account</th>
                    <th style="width: 20%;" class="text-right">Debit</th>
                    <th style="width: 20%;" class="text-right">Credit</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $paymentVoucher->paidToAccount->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ format_currency($paymentVoucher->amount) }}</td>
                    <td class="text-right">-</td>
                </tr>
                <tr>
                    <td>{{ $paymentVoucher->paidFromAccount->name ?? 'N/A' }}</td>
                    <td class="text-right">-</td>
                    <td class="text-right">{{ format_currency($paymentVoucher->amount) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ format_currency($paymentVoucher->amount) }}</strong></td>
                    <td class="text-right"><strong>{{ format_currency($paymentVoucher->amount) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">Prepared By</div>
                <div class="signature-name">{{ $paymentVoucher->createdBy->name ?? 'System User' }}</div>
            </div>
            
            <div class="signature-block">
                <div class="signature-line">Verified By</div>
                <div class="signature-name">Accounts Department</div>
            </div>
            
            <div class="signature-block">
                <div class="signature-line">Approved By</div>
                <div class="signature-name">Management</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>This is a computer-generated document.</strong></p>
            <p>Printed on: {{ now()->format('d-M-Y h:i A') }} | <strong>{{ company_name() }}</strong></p>
            @if(company()->website)
                <p>{{ company()->website }}</p>
            @endif
        </div>

        <!-- Print Actions -->
        <div class="print-actions no-print">
            <button class="print-btn primary" onclick="window.print()">🖨️ Print Voucher</button>
            <button class="print-btn" onclick="window.close()">✕ Close</button>
        </div>
    </div>
</body>
</html>
