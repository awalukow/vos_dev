@extends('portal.ticketing.layout')
@section('ticket-page-title','Payment Methods')
@section('ticket-content')
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
