<p>{{ $body }}</p>

@if(isset($account_name) && !empty($account_name))
    <p>Account name: {{ $account_name }}</p>
@endif

<p><a href="{{ $link }}">{{ $link }}</a></p>
<img src="https://sql.centerpointgroup.com/images/logo.jpg">