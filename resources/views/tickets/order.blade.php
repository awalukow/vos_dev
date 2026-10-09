@extends('tickets.layout')
@section('title',__("Your booking"))
@section('content')
@php($expired=$order->status==='expired'||($order->status==='awaiting_payment' && $order->expires_at?->isPast()))
<div class="page-head"><a class="tiny muted" href="{{ route('tickets.orders') }}">{{ __("← My tickets") }}</a><h1>{{ $order->status==='paid'?__("You’re on the guest list."):__("Your concert, one step closer.") }}</h1><span class="badge">{{ __(str_replace('_',' ',$expired?'expired':$order->status)) }}</span></div>
<div class="split"><div>
@if($order->status==='awaiting_payment' && !$expired)
<section class="panel"><div class="eyebrow">{{ __("03 · Complete your payment") }}</div><h2 style="margin-top:12px">{{ __("Make it official.") }}</h2><div class="notice">{{ __('Pay and upload proof before') }} <strong>{{ $order->expires_at->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</strong>{{ __('. After that, your tickets are released.') }}</div>
@if($methods->isEmpty() && $order->total>0)<div class="notice error">{{ __("No payment methods are currently available. Please contact the organizer.") }}</div>
@else
<form method="post" action="{{ $order->total===0?route('tickets.free',$order):route('tickets.proof',$order) }}" enctype="multipart/form-data">@csrf
<label for="singer-id">{{ __('Singer name') }}</label><select name="singer_id" id="singer-id"><option value="">{{ __('No singer referral') }}</option>@foreach($singers as $singer)<option value="{{ $singer->id }}" @selected((string)old('singer_id',$order->portal_singer_id)===(string)$singer->id)>{{ $singer->name }}</option>@endforeach</select>
@if($order->total>0)
<label for="payment-method">{{ __("Payment method") }}</label><select name="method" id="payment-method" required>@foreach($methods as $method)<option value="{{ $method->id }}" @selected((string)old('method')===(string)$method->id)>{{ $method->name }}</option>@endforeach</select>
@foreach($methods as $method)<div class="payment-details notice" data-method="{{ $method->id }}"><strong>{{ $method->name }}</strong><p style="white-space:pre-line">{{ $method->instructions }}</p>@if($method->type==='qris' && $method->qr_image)<img class="qr-image" src="{{ route('tickets.payment-image',$method) }}" alt="{{ __('Merchant QRIS payment code') }}">@endif<p class="tiny">{{ __('Transfer exactly') }} <strong>Rp {{ number_format($order->total,0,',','.') }}</strong> {{ __('and use your booking reference where possible.') }}</p></div>@endforeach
<label for="proof">{{ __("Upload payment proof") }}</label><input type="file" id="proof" name="proof" accept="image/jpeg,image/png,application/pdf" required><p class="tiny muted">{{ __("JPG, PNG or PDF, up to 5 MB. Staff verify receipt before issuing tickets.") }}</p><button class="btn">{{ __("Submit for approval →") }}</button>@else<button class="btn">{{ __('Confirm free tickets') }}</button>@endif</form>
@endif
<form class="actions" action="{{ route('tickets.cancel',$order) }}" method="post">@csrf<button class="link-button" onclick="return confirm(this.dataset.confirm)" data-confirm="{{ __('Cancel this reservation and release your tickets?') }}">{{ __("Cancel reservation") }}</button></form>
</section>
@elseif($order->status==='payment_review')
<div class="panel"><h2>{{ __("We’re checking your payment.") }}</h2><p class="muted">{{ __("Your tickets remain reserved. Once approved, your QR tickets will appear here and arrive by email.") }}</p><p class="tiny">{{ __('Submitted') }} {{ $order->proof_uploaded_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB. {{ __('Payment method') }}: {{ $order->payment_snapshot['name']??'' }}</p><a class="btn secondary" href="{{ route('tickets.order',$order) }}">{{ __("Refresh status") }}</a></div>
@elseif($order->status==='paid')
<div class="panel"><h2>{{ __("Your booking QR") }}</h2><p><strong>{{ $order->booking_code }}</strong></p><p class="muted">{{ __("Keep your individual tickets below ready at the venue. Each ticket has its own QR code.") }}</p><img width="180" style="height:auto" src="{{ route('tickets.booking-qr',$order) }}" alt="{{ __('Booking QR code') }}"><div class="actions"><button class="btn secondary" onclick="window.print()">{{ __("Print / save tickets") }}</button></div></div>
@foreach($order->items as $item)<div class="ticket-strip"><img src="{{ route('tickets.qr',$item) }}" alt="{{ __('QR for ticket :number', ['number'=>$loop->iteration]) }}"><div><strong>{{ $item->booking_label }}</strong><div class="eyebrow">{{ __('Admit one') }} · {{ $item->class_name }}</div><h3>{{ $item->seat_label?__("Seat ").$item->seat_label:__("Free seating") }}</h3><p class="tiny muted">{{ $order->event->title }}<br>{{ $order->event->starts_at->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') }} WIB</p></div></div>@endforeach
@else
<div class="panel"><h2>{{ $expired?__("Your reservation expired."):($order->status==='rejected'?__("Payment was not approved."):__("Reservation cancelled.")) }}</h2><p class="muted">{{ $order->review_note??__("The reserved tickets have been released. You can make another booking if tickets remain available.") }}</p><a class="btn" href="{{ route('tickets.events') }}">{{ __("Explore concerts") }}</a></div>
@endif
</div><aside class="panel summary"><div class="eyebrow">{{ __("Booking details") }}</div><h3>{{ $order->event->title }}</h3><p class="muted tiny">{{ $order->event->location }}<br>{{ $order->event->starts_at->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') }} WIB</p>
@include('tickets._booking-items')
<div class="row"><strong>{{ __("Total") }}</strong><strong>Rp {{ number_format($order->total,0,',','.') }}</strong></div><p>{{ __("Booking code") }}: <strong>{{ $order->booking_code }}</strong><br>{{ __("Referral") }}: {{ $order->singer_name ?? __("No singer referral") }} @if($order->referral_code)({{ $order->referral_code }})@endif</p>@if($order->discount)<div class="notice success">{{ __('Promo Code') }}: <strong>{{ $order->promo_snapshot['code'] }}</strong><br>{{ __('Discount') }}: −Rp {{ number_format($order->discount,0,',','.') }}@if($order->promo_snapshot['free_tickets']??0)<br>{{ __('Free tickets') }}: {{ $order->promo_snapshot['free_tickets'] }}@endif</div>@endif
@if($order->status==='awaiting_payment' && !$expired)
<form class="promo-form" method="post" action="{{ route('tickets.promo',$order) }}">@csrf
<label for="promo-code">{{ __('Promo Code') }}</label>
@if(session('promo_success'))<p id="promo-success" class="promo-feedback" role="status">{{ session('promo_success') }}</p>@endif
<input id="promo-code" @if($order->ticket_promo_id) readonly @else name="promo_code" @endif maxlength="64" autocomplete="off" value="{{ $order->ticket_promo_id?($order->promo_snapshot['code']??''):old('promo_code','') }}">
<div class="promo-actions">
@if($order->ticket_promo_id)
<button class="btn secondary" type="submit" name="promo_code" value="">{{ __('Remove promo') }}</button>
@else
<button class="btn" type="submit">{{ __('Apply promo') }}</button>
@endif
</div>
</form>
@endif
<p class="tiny muted">{{ __("Reference") }}<br><code>{{ $order->reference }}</code></p></aside></div>
@endsection
@push('scripts')
<script>
const method=document.getElementById('payment-method');
if(method){const show=()=>document.querySelectorAll('.payment-details').forEach(p=>p.hidden=p.dataset.method!==method.value);method.addEventListener('change',show);show();}
</script>
@endpush
