<!doctype html><html><body style="font-family:Arial,sans-serif;background:#f7f5fa;color:#251e36;padding:25px">
<div style="max-width:600px;margin:auto;background:white;padding:30px;border-radius:16px">
<p style="color:#713cce;letter-spacing:2px">VOS TICKETS</p><h1>You're on the guest list.</h1>
<p>Hello {{ $order->customer->name }}, your payment has been approved.</p><h2>{{ $order->event->title }}</h2>
<p>{{ $order->event->starts_at->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB<br>{{ $order->event->location }}</p>
<p>Booking code: <strong>{{ $order->booking_code }}</strong><br>Referral: {{ $order->singer_name ?? "No singer referral" }} @if($order->referral_code)({{ $order->referral_code }})@endif<br>Booking reference: {{ $order->reference }}<br>Total paid: Rp {{ number_format($order->total,0,',','.') }}</p>
@foreach(['processing'=>'Processing Fee','platform'=>'Platform Fee'] as $fee=>$label)
@if(($order->payment_snapshot['fees'][$fee]??0)>0)<p>{{ $label }}: Rp {{ number_format($order->payment_snapshot['fees'][$fee],0,',','.') }}</p>@endif
@endforeach
<p><a href="{{ route('tickets.order',$order) }}">Open your booking and tickets</a></p>
@include('tickets._promo-summary')
<h3>Booking QR · {{ $order->booking_code }}</h3><img width="150" style="height:auto" alt="Booking QR" src="{{ $message->embedData(app(\App\Services\TicketDelivery::class)->qr(route('tickets.receipt',$order->reference),$order->booking_code), 'booking-inline.png', 'image/png') }}">
@foreach($order->items as $item)
<hr><h3>Ticket {{ $item->booking_label }} · {{ $item->class_name }}</h3><p>{{ $item->seat_label?'Seat '.$item->seat_label:'Free seating' }} · Admit one</p>
<img width="150" style="height:auto" alt="Individual ticket QR" src="{{ $message->embedData(app(\App\Services\TicketDelivery::class)->qr(route('tickets.validate',$item->token),$item->booking_label), 'ticket-inline-'.$item->id.'.png', 'image/png') }}">
@endforeach
<p>Your QR codes are also attached as PNG files. Keep them private and ready to show at the venue.</p><p>See you at the concert,<br>Voice of Soul Choir</p></div></body></html>
