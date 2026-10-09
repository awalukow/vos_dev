@extends('portal.ticketing.layout')
@section('ticket-page-title','Concert Memories')
@section('ticket-content')
<div class="section-heading"><div><div class="eyebrow">Previous Concerts</div><h1 class="admin-title">{{ $event->title }}</h1><p class="muted">Share the moments that made this concert special.</p></div><a class="btn btn-secondary" href="{{ route('portal.ticketing.events.edit',$event) }}">Back to event</a></div>
<form class="panel" method="post" enctype="multipart/form-data" action="{{ route('portal.ticketing.events.memories.save',$event) }}">@csrf
<h2>Concert memories</h2><p>Photos appear as a slideshow. A YouTube video appears in its own tab when both are provided. Without media, visitors see “Coming Soon”.</p>
<label for="video">YouTube video</label><input id="video" name="video" type="url" placeholder="https://www.youtube.com/watch?v=…" value="{{ old('video',$event->memory_video?'https://www.youtube.com/watch?v='.$event->memory_video:'') }}"><p class="tiny muted">Leave blank to remove the video.</p>
<label for="photos">Add photos</label><input id="photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"><p class="tiny muted">Up to 30 photos, 4 MB each. New photos are added at the end of the slideshow.</p>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px;margin:24px 0">
@foreach($event->memory_photos??[] as $index=>$photo)<div><img src="{{ route('tickets.memory-photo',[$event,$index]) }}" alt="Memory {{ $index+1 }}" style="width:100%;height:140px;object-fit:cover;border-radius:8px"><label class="check"><input type="checkbox" name="remove[]" value="{{ $index }}" @checked(in_array($index,old('remove',[])))> Remove photo {{ $index+1 }}</label></div>@endforeach
</div><button class="btn btn-primary">Save memories</button></form>
@endsection
