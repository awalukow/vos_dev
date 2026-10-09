@foreach($order->items->groupBy('ticket_class_id') as $items)
@php($seats=$items->pluck('seat_label')->filter(fn($seat)=>$seat!==null && $seat!==''))
<div class="row tiny booking-class"><span><strong>{{ $items->first()->class_name }}</strong><br>{{ $seats->isEmpty()?__('Free seating'):__('Numbered seating') }} · {{ __($items->count()===1?':count Ticket':':count Tickets', ['count'=>$items->count()]) }}@if($seats->isNotEmpty())<br><span class="booking-seats">{{ $seats->implode(', ') }}</span>@endif</span><strong>Rp {{ number_format($items->sum('price'),0,',','.') }}</strong></div>
@endforeach
