@php
    $banks = company_banks();
@endphp

@if(count($banks) > 0)
<div class="bank-details" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold;">Bank Details</h4>
    
    @foreach($banks as $index => $bank)
    <div style="margin-bottom: {{ $loop->last ? '0' : '15px' }};">
        <p style="margin: 2px 0; font-size: 12px;">
            <strong>Bank {{ count($banks) > 1 ? ($index + 1) : '' }}:</strong> {{ $bank['bank_name'] ?? 'N/A' }}
            @if(!empty($bank['branch']))
                ({{ $bank['branch'] }})
            @endif
        </p>
        @if(!empty($bank['account_title']))
        <p style="margin: 2px 0; font-size: 12px;">
            <strong>Account Title:</strong> {{ $bank['account_title'] }}
        </p>
        @endif
        @if(!empty($bank['account_number']))
        <p style="margin: 2px 0; font-size: 12px;">
            <strong>Account Number:</strong> {{ $bank['account_number'] }}
        </p>
        @endif
        @if(!empty($bank['routing_number']))
        <p style="margin: 2px 0; font-size: 12px;">
            <strong>Routing Number:</strong> {{ $bank['routing_number'] }}
        </p>
        @endif
        @if(!empty($bank['swift_code']))
        <p style="margin: 2px 0; font-size: 12px;">
            <strong>SWIFT Code:</strong> {{ $bank['swift_code'] }}
        </p>
        @endif
    </div>
    @if(!$loop->last)
    <hr style="margin: 10px 0; border: none; border-top: 1px dashed #ccc;">
    @endif
    @endforeach
</div>
@endif
