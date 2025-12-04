@php
    $footerType = $footerType ?? 'invoice'; // invoice, receipt, quotation
    $company = company();
    
    $footerText = match($footerType) {
        'invoice' => $company->invoice_footer,
        'receipt' => $company->receipt_footer,
        'quotation' => $company->quotation_footer,
        default => $company->invoice_footer,
    };
@endphp

@if($footerText)
<div class="print-footer" style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ccc; text-align: center; font-size: 12px; color: #666;">
    <p style="margin: 0;">{{ $footerText }}</p>
</div>
@endif
