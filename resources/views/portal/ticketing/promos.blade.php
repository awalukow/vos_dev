@extends('portal.ticketing.layout')
@section('ticket-page-title','Promo Codes')
@section('ticket-content')
<h1 class="admin-title">Promo codes</h1>
<section class="panel"><h2>{{ $editing->exists?'Edit promo':'Create promo' }}</h2>
<form method="post" action="{{ $editing->exists?route('portal.ticketing.promos.update',$editing):route('portal.ticketing.promos.store') }}">@csrf
<label for="code">Code</label>
<div class="toolbar">
<div><input id="code" name="code" maxlength="64" value="{{ old('code',$editing->code) }}" required></div>
<button type="button" class="btn btn-secondary" id="randomize-promo-code">Randomize code</button>
</div>
<p class="muted tiny">Generate a 10-character code, or enter your own.</p>
<label for="type">Offer</label><select id="type" name="type">@foreach(['percent'=>'Percentage discount','fixed'=>'Fixed discount (Rp)','bogo'=>'Buy 1 Get 1 (repeats for each pair)','bundle'=>'Free X tickets with minimum Y paid tickets','free_ticket'=>'One free ticket'] as $value=>$label)<option value="{{ $value }}" @selected(old('type',$editing->type)===$value)>{{ $label }}</option>@endforeach</select>
<label for="value">Discount value (% or Rp; discounts only)</label><input id="value" name="value" type="number" min="1" max="1000000000" value="{{ old('value',$editing->value?:'') }}">
<label for="buy_quantity">Minimum paid tickets Y (X/Y offer only)</label><input id="buy_quantity" name="buy_quantity" type="number" min="1" max="9" value="{{ old('buy_quantity',$editing->buy_quantity??1) }}">
<label for="free_quantity">Free tickets X (X/Y offer only)</label><input id="free_quantity" name="free_quantity" type="number" min="1" max="9" value="{{ old('free_quantity',$editing->free_quantity??1) }}">
<p class="muted">Customers select all tickets, including free seats, before applying the code. The cheapest selected tickets are free. The X/Y offer applies once per booking. A booking can contain up to 10 tickets.</p>
<label for="expires_at">Expiry (WIB / Jakarta; blank means no expiry)</label><input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at',$editing->expires_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}">
<label for="daily_limit">Maximum bookings per day across all customers (blank means unlimited)</label><input id="daily_limit" name="daily_limit" type="number" min="1" value="{{ old('daily_limit',$editing->daily_limit) }}">
<p class="muted">Daily limits use Jakarta time. Applying a promo holds a use until the booking expires or is cancelled. Submitted and paid bookings consume a use; rejected payments release it.</p>
@foreach(['active'=>'Active','single_use'=>'Single use per customer','user_specific'=>'Restrict to selected customers'] as $field=>$label)<label><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field,session()->hasOldInput()?false:($editing->exists?$editing->$field:$field==='active')))>{{ $label }}</label>@endforeach
<label for="customer_emails">Registered customer emails (one or bulk; separate with commas or new lines)</label><textarea id="customer_emails" name="customer_emails" rows="5">{{ old('customer_emails',$editing->exists?$editing->customers->pluck('email')->implode("\n"):'') }}</textarea>
<button class="btn btn-primary">Save promo</button> <a href="{{ route('portal.ticketing.promos') }}">New promo</a>
</form></section>
<section class="panel table-wrap"><table><thead><tr><th>Code</th><th>Offer</th><th>Expiry (WIB)</th><th>Limits</th><th></th></tr></thead><tbody>
@forelse($promos as $promo)<tr><td><strong>{{ $promo->code }}</strong><br>{{ $promo->active?'Active':'Disabled' }}</td><td>{{ $promo->type }} @if(in_array($promo->type,['fixed','percent'])) {{ $promo->value }}{{ $promo->type==='percent'?'%':' Rp' }} @elseif($promo->type==='bundle') {{ $promo->free_quantity }} free / {{ $promo->buy_quantity }} paid @endif</td><td>{{ $promo->expires_at?->timezone('Asia/Jakarta')->format('d M Y H:i')??'No expiry' }}</td><td>{{ $promo->single_use?'Once per customer':'Repeat use' }}<br>{{ $promo->daily_limit??'Unlimited' }} bookings/day<br>{{ $promo->user_specific?'Selected customers':'All customers' }}</td><td><a href="{{ route('portal.ticketing.promos',['edit'=>$promo->id]) }}">Edit</a></td></tr>@empty<tr><td colspan="5">No promo codes yet.</td></tr>@endforelse
</tbody></table>{{ $promos->links('portal.components.pagination') }}</section>
@endsection

@push('scripts')
<script>
document.getElementById('randomize-promo-code').addEventListener('click', () => {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    const bytes = crypto.getRandomValues(new Uint8Array(10));
    const input = document.getElementById('code');
    input.value = Array.from(bytes, byte => alphabet[byte % alphabet.length]).join('');
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.focus();
});
</script>
@endpush
