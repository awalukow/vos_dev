@extends('portal.ticketing.layout')
@section('ticket-page-title','Order List')
@section('ticket-content')
<div class="page-head"><h1 class="admin-title">Order List</h1><p class="muted">Find bookings and open issued tickets with their QR barcodes.</p></div>
<form method="get" class="toolbar"><input name="q" value="{{ request('q') }}" placeholder="Booking code, reference, customer or referral" aria-label="Search orders"><select name="status" aria-label="Order status"><option value="">All statuses</option>@foreach(['awaiting_payment','payment_review','paid','rejected','cancelled','expired'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select><button class="btn btn-primary">Search</button></form>
<div class="table-responsive"><table>
<thead><tr><th>Status</th><th>Event</th><th>Customer</th><th>Email</th><th>Booking code</th><th>Reference</th><th>Total</th><th>Referral</th><th>Open order</th></tr></thead>
<tbody>@forelse($orders as $order)
<tr><td><span class="badge">{{ str_replace('_',' ',$order->status==='awaiting_payment' && $order->expires_at?->isPast()?'expired':$order->status) }}</span></td><td>{{ $order->event->title }}</td><td>{{ $order->customer->name }}</td><td>{{ $order->customer->email }}</td><td><strong>{{ $order->booking_code }}</strong></td><td><code>{{ $order->reference }}</code></td><td>Rp {{ number_format($order->total,0,',','.') }}</td><td>{{ $order->singer_name ?? '—' }}@if($order->referral_code)<br><code>{{ $order->referral_code }}</code>@endif</td><td><a class="btn btn-secondary" href="{{ route('portal.ticketing.orders.show',$order) }}">Open order / tickets</a>@if(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2())<form method="post" action="{{ route('portal.ticketing.orders.destroy',$order) }}" onsubmit="return confirm('Delete this booking? Its tickets will stop working and seats will be released. Payment history is preserved; this does not issue a refund.')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Delete booking</button></form>@endif</td></tr>
@empty<tr><td colspan="9">No orders found.</td></tr>@endforelse</tbody>
</table></div>
{{ $orders->links('portal.components.pagination') }}
@endsection
