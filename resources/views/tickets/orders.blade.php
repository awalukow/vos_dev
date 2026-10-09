@extends('tickets.layout')
@section('title',__("My tickets"))
@section('content')
<div class="page-head"><div class="eyebrow">{{ __("Your concert diary") }}</div><h1>{{ __("My tickets.") }}</h1><p class="muted">{{ __("Reservations, payment updates, and your passes to the music.") }}</p></div>
@forelse($orders as $order)
@php
    $status = $order->status === 'awaiting_payment' && $order->expires_at?->isPast() ? 'expired' : $order->status;
    $startsAt = $order->event->starts_at->copy()->timezone('Asia/Jakarta');
@endphp
<article class="booking-card" aria-labelledby="booking-{{ $order->id }}">
    <div class="booking-date" aria-hidden="true"><span>{{ $startsAt->translatedFormat('M') }}</span><strong>{{ $startsAt->format('d') }}</strong><span>{{ $startsAt->format('Y') }}</span></div>
    <div class="booking-details">
        <span class="badge booking-status status-{{ $status }}">{{ __(str_replace('_',' ',$status)) }}</span>
        <h2 id="booking-{{ $order->id }}">{{ $order->event->title }}</h2>
        <p class="booking-time"><time datetime="{{ $startsAt->toIso8601String() }}">{{ $startsAt->translatedFormat('d M Y · H:i') }} WIB</time></p>
        <p>{{ __("Booking code") }}: <strong>{{ $order->booking_code }}</strong><br>{{ __("Referral") }}: {{ $order->singer_name ?? __("No singer referral") }} @if($order->referral_code)({{ $order->referral_code }})@endif</p>
        <p class="booking-reference">{{ __('Booking reference') }} <span>{{ $order->reference }}</span></p>
    </div>
    <div class="booking-action"><div><span class="booking-total-label">{{ __('Total') }}</span><strong class="booking-total">Rp {{ number_format($order->total,0,',','.') }}</strong></div><a class="btn" href="{{ route('tickets.order',$order) }}">{{ __("View booking →") }}</a></div>
</article>
@empty<div class="empty"><h2>{{ __("Your first concert is waiting.") }}</h2><p class="muted">{{ __("You haven’t booked any tickets yet.") }}</p><a href="{{ route('tickets.events') }}" class="btn">{{ __("Explore concerts") }}</a></div>@endforelse
{{ $orders->links('portal.components.pagination') }}
@endsection
