@extends('tickets.layout')
@section('title',$event->title)
@section('content')
<div class="page-head"><a class="tiny muted" href="{{ route('tickets.events') }}">{{ __("← All concerts") }}</a><div class="eyebrow" style="margin-top:22px">{{ __("01 Select · 02 Confirm · 03 Pay") }}</div><h1>{{ $event->title }}</h1><p class="muted">{{ $event->starts_at->timezone('Asia/Jakarta')->translatedFormat('l, d F Y · H:i') }} WIB &nbsp; / &nbsp; {{ $event->location }}</p></div>
<form id="selection-form" method="post" action="{{ route('tickets.reserve',$event) }}">@csrf
<div class="split"><section class="panel">
<h2>{{ $event->seating_type==='numbered'?__("A seat with your name on it."):__("Choose your experience.") }}</h2><p class="muted">{{ $event->description }}</p>
@if($event->seating_type==='numbered')
<p class="tiny muted">{{ __("Tap a seat to select it. Colors identify ticket classes. Select up to 10 seats.") }}</p>
<div class="legend">@foreach($event->classes as $class)<span><i class="swatch" style="background:{{ $class->color }}"></i>{{ $class->name }} · Rp {{ number_format($class->price,0,',','.') }}</span>@endforeach<span><i class="swatch" style="background:#e9e6ed"></i>{{ __("Unavailable") }}</span></div>
<div class="seat-scroll" tabindex="0" aria-label="{{ __('Seating map, scroll horizontally for more seats') }}">
<div class="venue-canvas" style="width:{{ max(450,$event->seats->max('x')*42+34) }}px"><div class="stage">{{ __('S T A G E') }}</div>
<div class="seat-map" style="width:{{ $event->seats->max('x')*42+34 }}px;height:{{ (max($event->seats->max('y'),collect($event->layout_dividers??[])->max('y'))+1)*42 }}px">
@foreach($event->layout_dividers??[] as $divider)<div class="venue-divider" style="top:{{ $divider['y']*42 }}px"><span>{{ $divider['label'] }}</span></div>@endforeach
@foreach($event->seats as $seat)<button type="button" class="seat" data-id="{{ $seat->id }}" data-price="{{ $seat->ticketClass->price }}" data-label="{{ $seat->label }}" data-class="{{ $seat->ticketClass->name }}" style="left:{{ $seat->x*42 }}px;top:{{ $seat->y*42 }}px;--seat-color:{{ $seat->ticketClass->color }}" aria-pressed="false" aria-label="{{ $seat->label }}, {{ $seat->ticketClass->name }}, Rp {{ $seat->ticketClass->price }}" @disabled($reserved->contains('ticket_seat_id',$seat->id))>{{ $seat->label }}</button>@endforeach
</div></div></div><div id="seat-inputs"></div>
@else
<span class="badge">{{ __("FREE SEATING") }}</span><p class="tiny muted" style="margin-top:15px">{{ __("Choose your class and quantity. Seats within your class are unassigned. Maximum 10 tickets per booking.") }}</p>
@foreach($event->classes as $class)
@php($remaining=max(0,$class->capacity-$reserved->where('ticket_class_id',$class->id)->count()))
<div class="row"><div><h3><i class="swatch" style="background:{{ $class->color }}"></i>{{ $class->name }}</h3><span class="price">Rp {{ number_format($class->price,0,',','.') }}</span><div class="tiny muted">{{ __(':count tickets available', ['count'=>$remaining]) }}</div></div>
<div class="qty"><label for="qty-{{ $class->id }}">{{ __("Quantity") }}</label><input id="qty-{{ $class->id }}" class="ticket-qty" name="quantities[{{ $class->id }}]" type="number" min="0" max="{{ min(10,$remaining) }}" value="{{ old('quantities.'.$class->id,0) }}" data-price="{{ $class->price }}" data-class="{{ $class->name }}" @disabled(!$remaining)></div></div>
@endforeach
@endif
</section><aside class="panel summary"><div class="eyebrow">{{ __("Your evening") }}</div><h3>{{ __("Booking summary") }}</h3><div id="selection-list" class="muted tiny" aria-live="polite">{{ __("Choose your tickets to begin.") }}</div><div class="row"><span>{{ __("Total") }}</span><strong id="selection-total">Rp 0</strong></div><p class="tiny muted">{{ __("Prices in Indonesian Rupiah. Tickets are held for 30 minutes after confirmation.") }}</p><p id="selection-error" class="tiny" role="alert" style="color:#a52a45"></p><button type="button" id="review-selection" class="btn full">{{ __("Review selection →") }}</button></aside></div>
<dialog id="confirm-dialog" aria-labelledby="confirm-title"><div class="eyebrow">{{ __("One more look") }}</div><h2 id="confirm-title">{{ __("Confirm your booking") }}</h2><p>{{ $event->title }}</p><div id="confirmation-lines"></div><div class="row"><span>{{ __("Total") }}</span><strong id="confirmation-total"></strong></div><p class="tiny muted">{{ __("Confirm to reserve these tickets and continue to payment.") }}</p><div class="actions"><button type="button" id="back-selection" class="btn secondary">{{ __("Change selection") }}</button><button type="submit" class="btn">{{ __("Confirm & pay →") }}</button></div></dialog>
</form>
@endsection
@push('scripts')
<script>
(() => {
 const selected=new Map(), form=document.getElementById('selection-form'), dialog=document.getElementById('confirm-dialog');
 const money=n=>'Rp '+Number(n).toLocaleString('id-ID');
 function lines() {
  const entries=Array.from(selected.values()).map(s=>({text:s.label+' · '+s.className,qty:1,price:s.price}));
  document.querySelectorAll('.ticket-qty').forEach(input=>{const qty=Number(input.value);if(qty>0) entries.push({text:input.dataset.class+' × '+qty,qty,price:Number(input.dataset.price)*qty});});
  return entries;
 }
 function render(target, entries) {
  target.replaceChildren();
  entries.forEach(line=>{const row=document.createElement('p');row.textContent=line.text+' — '+money(line.price);target.append(row);});
 }
 function update() {
  const entries=lines(),total=entries.reduce((n,l)=>n+l.price,0);
  render(document.getElementById('selection-list'),entries);
  if(!entries.length) document.getElementById('selection-list').textContent=@json( __("Choose your tickets to begin.") );
  document.getElementById('selection-total').textContent=money(total);
  const inputs=document.getElementById('seat-inputs');
  if(inputs){inputs.replaceChildren(); selected.forEach((seat,id)=>{const input=document.createElement('input');input.type='hidden';input.name='seats[]';input.value=id;inputs.append(input);});}
 }
 document.querySelectorAll('.seat:not(:disabled)').forEach(button=>button.addEventListener('click',()=>{
  const id=button.dataset.id;
  if(selected.has(id))selected.delete(id);else {if(selected.size>=10){document.getElementById('selection-error').textContent=@json( __("You can select up to 10 seats.") );return;}selected.set(id,{label:button.dataset.label,className:button.dataset.class,price:Number(button.dataset.price)});}
  button.classList.toggle('selected',selected.has(id));button.setAttribute('aria-pressed',selected.has(id)?'true':'false');document.getElementById('selection-error').textContent='';update();
 }));
 document.querySelectorAll('.ticket-qty').forEach(input=>input.addEventListener('input',update));
 document.getElementById('review-selection').addEventListener('click',()=>{
  if(!form.reportValidity())return;
  const entries=lines(),count=entries.reduce((n,l)=>n+l.qty,0);
  if(count<1||count>10){document.getElementById('selection-error').textContent=@json( __("Choose between 1 and 10 tickets.") );return;}
  render(document.getElementById('confirmation-lines'),entries);
  document.getElementById('confirmation-total').textContent=money(entries.reduce((n,l)=>n+l.price,0));
  dialog.showModal();
 });
 document.getElementById('back-selection').addEventListener('click',()=>dialog.close());
 form.addEventListener('submit',e=>{if(!dialog.open){e.preventDefault();return;}const b=dialog.querySelector('[type=submit]');b.disabled=true;b.textContent=@json( __("Reserving…") );});
 update();
})();
</script>
@endpush