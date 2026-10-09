@extends('portal.ticketing.layout')
@section('ticket-page-title','Events')
@section('ticket-content')
<div class="section-heading"><div><div class="eyebrow">Ticketing / Management</div><h1 class="admin-title">Your concerts.</h1></div><a class="btn btn-primary" href="{{ route('portal.ticketing.events.create') }}">+ Create event</a></div>
<div class="panel table-wrap"><table><thead><tr><th>Event</th><th>Seating</th><th>Availability</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($events as $event)@php($a=$event->availability())
<tr><td><strong>{{ $event->title }}</strong><div class="tiny muted">{{ $event->starts_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</div>@if($event->limited_seating)<span class="badge">Limited Seating</span>@endif</td><td>{{ ucfirst($event->seating_type) }}</td><td>{{ $a['remaining'] }} / {{ $a['capacity'] }} available<div class="tiny muted">{{ $a['used'] }} sold or reserved</div></td><td>{{ $event->published?'Published':'Draft' }}<div class="tiny muted">{{ !$event->published?'Hidden from storefront':($event->starts_at->isFuture()?'Visible on storefront':'Hidden from storefront — start time reached') }}</div></td><td><a href="{{ route('portal.ticketing.events.edit',$event) }}">Edit →</a><form method="post" action="{{ route('portal.ticketing.events.destroy',$event) }}" onsubmit="return confirm('Delete this event from the lists? Existing bookings and history will be preserved.')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Delete event</button></form></td></tr>
@empty<tr><td colspan="5">No events yet. Create a venue first for numbered seating, or start with a free-seating concert.</td></tr>@endforelse
</tbody></table></div>{{ $events->links('portal.components.pagination') }}
@endsection
