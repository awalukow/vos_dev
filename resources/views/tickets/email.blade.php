@extends('tickets.layout')
@section('title','Change email')
@section('content')
<section class="panel"><h1>Change email</h1><p>Current email: {{ $customer->email }}</p><p>Your current email stays on your account until you verify the new address.</p>
<form method="post" action="{{ route('tickets.email.send') }}">@csrf
<label for="new-email">New email</label><input id="new-email" type="email" name="email" maxlength="190" autocomplete="email" value="{{ old('email',$customer->pending_email) }}" required>
<button class="btn btn-primary">Send verification code</button></form>
@if($customer->pending_email)
<p>Enter the code sent to {{ $customer->pending_email }}. It expires after 10 minutes.</p>
<form method="post" action="{{ route('tickets.email.confirm') }}">@csrf
<label for="email-otp">Verification code</label><input id="email-otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
<button class="btn btn-primary">Verify and save email</button></form>
@endif
</section>
@endsection
