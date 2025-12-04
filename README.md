for company data 
<!-- Anywhere in your views -->

<!-- Display company name -->
<h1>{{ company_name() }}</h1>

<!-- Display company logo -->
@if(company_logo())
    <img src="{{ company_logo() }}" alt="Company Logo">
@endif

<!-- Display formatted currency -->
<p>Total: {{ format_currency(1500.50) }}</p>
<!-- Output: ৳ 1,500.50 -->

<!-- Access full company object -->
<p>Email: {{ company()->email }}</p>
<p>Phone: {{ company()->phone }}</p>
<p>BIN: {{ company()->bin }}</p>

<!-- Display bank accounts -->
@foreach(company_banks() as $bank)
    <p>{{ $bank['bank_name'] }} - {{ $bank['account_number'] }}</p>
@endforeach


-------------------------
