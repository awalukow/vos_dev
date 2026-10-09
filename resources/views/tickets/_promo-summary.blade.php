@if($order->ticket_promo_id)
<p>{{ __('Promo Code') }}: <strong>{{ $order->promo_snapshot['code']??'' }}</strong><br>{{ __('Discount') }}: Rp {{ number_format($order->discount,0,',','.') }}@if($order->promo_snapshot['free_tickets']??0)<br>{{ __('Free tickets') }}: {{ $order->promo_snapshot['free_tickets'] }}@endif</p>
@endif
