<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payables Report - {{ $startDate }} to {{ $endDate }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #d9534f;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 18px;
            color: #555;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 11px;
            color: #777;
        }
        
        .summary-boxes {
            display: flex;
            justify-content: space-around;
            margin-bottom: 25px;
            gap: 15px;
        }
        
        .summary-box {
            flex: 1;
            border: 2px solid #ddd;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
        }
        
        .summary-box.payable {
            border-color: #d9534f;
            background-color: #fef5f5;
        }
        
        .summary-box.advance {
            border-color: #5cb85c;
            background-color: #f5fef5;
        }
        
        .summary-box.accounts {
            border-color: #5bc0de;
            background-color: #f5fbfe;
        }
        
        .summary-box.vendors {
            border-color: #f0ad4e;
            background-color: #fefaf5;
        }
        
        .summary-box h3 {
            font-size: 22px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .summary-box p {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        
        .summary-box.payable h3 {
            color: #d9534f;
        }
        
        .summary-box.advance h3 {
            color: #5cb85c;
        }
        
        .summary-box.accounts h3 {
            color: #5bc0de;
        }
        
        .summary-box.vendors h3 {
            color: #f0ad4e;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background-color: #f5f5f5;
        }
        
        table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        table tbody tr:hover {
            background-color: #f0f0f0;
        }
        
        table tfoot {
            background-color: #e9ecef;
            font-weight: bold;
        }
        
        table tfoot td {
            padding: 12px 8px;
            font-size: 12px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-danger {
            color: #d9534f;
        }
        
        .text-success {
            color: #5cb85c;
        }
        
        .text-info {
            color: #5bc0de;
        }
        
        .text-warning {
            color: #f0ad4e;
        }
        
        .text-muted {
            color: #999;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
        }
        
        .badge-warning {
            background-color: #f0ad4e;
            color: white;
        }
        
        .badge-info {
            background-color: #5bc0de;
            color: white;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
            font-style: italic;
        }
        
        @media print {
            body {
                padding: 10px;
            }
            
            .summary-boxes {
                page-break-inside: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
            
            @page {
                margin: 1.5cm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-money-check-alt"></i> PAYABLES REPORT</h1>
        <h2>{{ config('app.name', 'ERP System') }}</h2>
        <p>
            <strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            &nbsp;|&nbsp;
            <strong>Generated:</strong> {{ now()->format('d M Y h:i A') }}
        </p>
    </div>

    <!-- Summary Boxes -->
    <div class="summary-boxes">
        <div class="summary-box payable">
            <h3>৳ {{ number_format($totals['total_payable'], 2) }}</h3>
            <p>Total Payables</p>
        </div>
        <div class="summary-box advance">
            <h3>৳ {{ number_format($totals['total_advance'], 2) }}</h3>
            <p>Total Advance Paid</p>
        </div>
        <div class="summary-box accounts">
            <h3>{{ $accounts->count() }}</h3>
            <p>Liability Accounts</p>
        </div>
        <div class="summary-box vendors">
            <h3>{{ $accounts->filter(fn($a) => $a['vendor'] !== null)->count() }}</h3>
            <p>Vendors with Balance</p>
        </div>
    </div>

    <!-- Payables Table -->
    @if($accounts->count() > 0)
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="10%">Code</th>
                <th width="20%">Account Name</th>
                <th width="12%" class="text-right">Opening</th>
                <th width="12%" class="text-right">Debits</th>
                <th width="12%" class="text-right">Credits</th>
                <th width="15%" class="text-right">Current Balance</th>
                <th width="7%" class="text-center">Txns</th>
                <th width="15%">Vendor</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDebits = 0;
                $totalCredits = 0;
                $totalOpeningBalance = 0;
                $netBalance = 0;
            @endphp
            
            @foreach($accounts as $index => $item)
                @php
                    $account = $item['account'];
                    $vendor = $item['vendor'];
                    $currentBalance = $item['current_balance'];
                    
                    $totalDebits += $item['total_debits'];
                    $totalCredits += $item['total_credits'];
                    $totalOpeningBalance += $item['opening_balance'];
                    
                    if ($currentBalance > 0) {
                        $netBalance += $currentBalance;
                    } else {
                        $netBalance += $currentBalance;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $account->code }}</span>
                    </td>
                    <td>
                        <strong>{{ $account->name }}</strong>
                        @if($account->parentAccount)
                            <br><small class="text-muted">Parent: {{ $account->parentAccount->name }}</small>
                        @endif
                    </td>
                    <td class="text-right">
                        <span class="{{ $item['opening_balance'] > 0 ? 'text-success' : ($item['opening_balance'] < 0 ? 'text-danger' : 'text-muted') }}">
                            ৳ {{ number_format(abs($item['opening_balance']), 2) }}
                        </span>
                    </td>
                    <td class="text-right text-danger">
                        ৳ {{ number_format($item['total_debits'], 2) }}
                    </td>
                    <td class="text-right text-success">
                        ৳ {{ number_format($item['total_credits'], 2) }}
                    </td>
                    <td class="text-right">
                        <strong class="{{ $currentBalance > 0 ? 'text-danger' : ($currentBalance < 0 ? 'text-success' : 'text-muted') }}">
                            ৳ {{ number_format(abs($currentBalance), 2) }}
                            @if($currentBalance > 0)
                                (Payable)
                            @elseif($currentBalance < 0)
                                (Advance)
                            @endif
                        </strong>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $account->transactionEntries->count() }}</span>
                    </td>
                    <td>
                        @if($vendor)
                            <span class="badge badge-warning">{{ $vendor->name }}</span>
                            @if($vendor->phone)
                                <br><small class="text-muted">{{ $vendor->phone }}</small>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right">
                    ৳ {{ number_format($totalOpeningBalance, 2) }}
                </td>
                <td class="text-right text-danger">
                    ৳ {{ number_format($totalDebits, 2) }}
                </td>
                <td class="text-right text-success">
                    ৳ {{ number_format($totalCredits, 2) }}
                </td>
                <td class="text-right">
                    <strong class="{{ $netBalance > 0 ? 'text-danger' : 'text-success' }}">
                        ৳ {{ number_format(abs($netBalance), 2) }}
                    </strong>
                </td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="6" class="text-right"><strong>NET PAYABLE AMOUNT:</strong></td>
                <td class="text-right">
                    <strong class="text-danger" style="font-size: 14px;">
                        ৳ {{ number_format($totals['total_payable'], 2) }}
                    </strong>
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-data">
        <p>No payables data found for the selected period.</p>
    </div>
    @endif

    <div class="footer">
        <p><strong>{{ config('app.name', 'ERP System') }}</strong> | Payables Report</p>
        <p>This is a system-generated report. No signature required.</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
