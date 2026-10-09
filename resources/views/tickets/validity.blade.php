@extends('tickets.layout')
@section('title',__("Ticket verification"))
@section('content')
<div class="page-head"><div class="eyebrow">{{ __("VOS · Ticket verification") }}</div><h1>{{ $order->status==='paid'?__("Valid ticket."):__("Not valid for entry.") }}</h1></div>
<div class="panel"><span class="badge">{{ $order->status==='paid'?__("PAYMENT CONFIRMED"):__("NOT PAID") }}</span><h2 style="margin-top:20px">{{ $order->event->title }}</h2><p>{{ $order->event->starts_at->timezone('Asia/Jakarta')->translatedFormat('d F Y · H:i') }} WIB<br>{{ $order->event->location }}</p>
<p>{{ __('Booking code') }}: <strong>{{ $order->booking_code }}</strong></p>
@if($order->status==='paid')
@if($item)<p><strong>{{ $item->booking_label }}</strong></p><strong>{{ $item->class_name }} · {{ $item->seat_label?__("Seat ").$item->seat_label:__("Free seating") }}</strong><p class="tiny muted">{{ __("Admit one.") }}</p>@else<p>{{ __('Booking contains :count tickets. Present each individual ticket QR for admission.', ['count'=>$order->items->count()]) }}</p>@endif
<p class="tiny muted">{{ __("This page verifies payment validity. Entry is subject to venue admission checks.") }}</p>
@endif</div>
@endsection