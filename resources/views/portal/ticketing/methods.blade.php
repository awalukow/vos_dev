@extends('portal.ticketing.layout')
@section('ticket-page-title','Payment Method & Fees')
@section('ticket-content')
<div class="page-head"><div class="eyebrow">Ticketing / Payments</div><h1 class="admin-title">Payment Method &amp; Fees</h1></div>
@if(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2() || auth('portal')->user()->hasRole('pengurus'))
<form class="panel" method="post" action="{{ route('portal.ticketing.fees.update') }}">@csrf
<h2>Fees</h2><p class="muted">Apply these fees to every new booking, regardless of payment method. Existing bookings retain their saved rates.</p>
<label for="tax-percent">Tax (%)</label><input type="number" id="tax-percent" name="tax_percent" min="0" max="100" step="0.01" value="{{ old('tax_percent',$feeSettings->tax_percent) }}" required>
<p class="tiny muted">Set tax to 0 to hide it from booking details and payment pages.</p>
<label for="platform-fee-type">Platform Fee type</label><select id="platform-fee-type" name="platform_fee_type"><option value="fixed" @selected(old('platform_fee_type',$feeSettings->platform_fee_type)==='fixed')>Fixed amount (Rp)</option><option value="percent" @selected(old('platform_fee_type',$feeSettings->platform_fee_type)==='percent')>Percentage (%)</option></select>
<label for="platform-fee-value">Platform Fee</label><input type="number" id="platform-fee-value" name="platform_fee_value" min="0" step="0.01" value="{{ old('platform_fee_value',$feeSettings->platform_fee_value) }}" required>
<p class="tiny muted">Percentages apply separately to the ticket total after discounts, rounded to whole rupiah. The fixed platform fee is charged once per booking. Free bookings have no fees.</p>
<button class="btn btn-primary">Save fees</button></form>
@endif
<div class="page-head"><div class="eyebrow">Ticketing / Payments</div><h1 class="admin-title">Ways to pay.</h1><p class="muted">Add your real bank details and merchant QRIS image, then enable the methods you want to offer.</p></div>
<div class="form-grid">@foreach($methods as $method)
@if($method->type==='midtrans')
@include('portal.ticketing._midtrans')
@continue
@endif
<form class="panel" method="post" enctype="multipart/form-data" action="{{ route('portal.ticketing.methods.update',$method) }}">@csrf<span class="badge">{{ strtoupper($method->type) }}</span><label for="method-name-{{ $method->id }}">Display name</label><input id="method-name-{{ $method->id }}" name="name" value="{{ $method->name }}" required>
<label for="instructions-{{ $method->id }}">Payment instructions</label><textarea id="instructions-{{ $method->id }}" name="instructions" rows="6" required>{{ $method->instructions }}</textarea>
@if($method->type==='qris')@if($method->qr_image)<img class="qr-image" src="{{ route('tickets.payment-image',$method) }}" alt="Current merchant QRIS code">@endif<label for="qr-{{ $method->id }}">Merchant QRIS image</label><input id="qr-{{ $method->id }}" name="qr_image" type="file" accept="image/jpeg,image/png">@endif
<label class="check"><input type="checkbox" name="active" value="1" @checked($method->active)> Enable this payment method</label><button class="btn btn-primary" style="margin-top:20px">Save payment method</button>
</form>@endforeach</div>
@endsection
