@php
    $company = company();
    $header = \App\Helpers\CompanyHelper::getPrintHeader();
@endphp

<div class="print-header" style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px;">
    @if($header['show_logo'] && $header['logo_url'])
    <div class="company-logo" style="margin-bottom: 10px;">
        <img src="{{ $header['logo_url'] }}" alt="{{ $header['company_name'] }}" 
             style="max-height: 80px; max-width: 300px;">
    </div>
    @endif

    @if($header['show_company_info'])
    <h2 style="margin: 5px 0; font-size: 24px; font-weight: bold;">
        {{ $header['company_name'] }}
    </h2>

    @if($header['company_name_bangla'])
    <h3 style="margin: 5px 0; font-size: 18px; font-weight: normal;">
        {{ $header['company_name_bangla'] }}
    </h3>
    @endif

    @if($header['tagline'])
    <p style="margin: 5px 0; font-style: italic; color: #666;">
        {{ $header['tagline'] }}
    </p>
    @endif

    <p style="margin: 5px 0; font-size: 14px;">
        {{ $header['address'] }}
    </p>

    <p style="margin: 5px 0; font-size: 14px;">
        @if($header['phone'])
            <strong>Phone:</strong> {{ $header['phone'] }} &nbsp;
        @endif
        @if($header['mobile'])
            <strong>Mobile:</strong> {{ $header['mobile'] }} &nbsp;
        @endif
        @if($header['email'])
            <strong>Email:</strong> {{ $header['email'] }}
        @endif
    </p>

    @if($header['website'])
    <p style="margin: 5px 0; font-size: 14px;">
        <strong>Website:</strong> {{ $header['website'] }}
    </p>
    @endif

    <p style="margin: 5px 0; font-size: 12px;">
        @if($header['bin'])
            <strong>BIN:</strong> {{ $header['bin'] }} &nbsp;
        @endif
        @if($header['tin'])
            <strong>TIN:</strong> {{ $header['tin'] }}
        @endif
    </p>
    @endif
</div>
