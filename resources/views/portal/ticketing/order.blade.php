@extends('portal.ticketing.layout')
@section('ticket-page-title','Order Details')
@section('ticket-content')
@include('tickets._promo-summary')
<a href="{{ route('portal.ticketing.orders') }}">← Order List</a>
<div class="page-head"><h1 class="admin-title">{{ $order->event->title }}</h1><p>{{ $order->customer->name }} · {{ $order->customer->email }}</p><p>Booking code: <strong>{{ $order->booking_code }}</strong><br>Reference: {{ $order->reference }}<br>Referral: {{ $order->singer_name ?? "—" }} @if($order->referral_code)({{ $order->referral_code }})@endif</p><span class="badge">{{ str_replace('_',' ',$order->status==='awaiting_payment' && $order->expires_at?->isPast()?'expired':$order->status) }}</span></div>
<section class="panel"><h2>Booking details</h2>@include('tickets._booking-items')
@foreach(['processing'=>'Processing Fee','platform'=>'Platform Fee'] as $fee=>$label)
@if(($order->payment_snapshot['fees'][$fee]??0)>0)<div class="row"><span>{{ $label }}</span><strong>Rp {{ number_format($order->payment_snapshot['fees'][$fee],0,',','.') }}</strong></div>@endif
@endforeach
<div class="row"><strong>Total</strong><strong>Rp {{ number_format($order->total,0,',','.') }}</strong></div></section>
@if($order->status==='paid')
<section class="panel"><h2>Booking QR barcode</h2><p><strong>{{ $order->booking_code }}</strong></p><img width="180" style="height:auto" src="{{ route('portal.ticketing.orders.qr',$order) }}" alt="Booking QR barcode"><p><button class="btn btn-secondary" onclick="window.print()">Print / save tickets</button></p></section>
@foreach($order->items as $item)<section class="panel"><h3>{{ $item->booking_label }} · {{ $item->class_name }} · {{ $item->seat_label?'Seat '.$item->seat_label:'Free seating' }}</h3><p>{{ $order->event->starts_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB · {{ $order->event->location }}</p><img width="180" style="height:auto" src="{{ route('portal.ticketing.orders.ticket-qr',[$order,$item]) }}" alt="QR barcode for ticket {{ $loop->iteration }}"><p>Admit one</p></section>@endforeach
@else<div class="notice">Tickets and QR barcodes are available after payment approval.</div>@endif
@endsection
