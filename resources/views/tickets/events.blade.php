@extends('tickets.layout')
@section('title',__("Discover concerts"))
@section('content')
<section class="hero"><div><div class="eyebrow">{{ __("Voice of Soul · Live experiences") }}</div><h1>{{ __("Find your seat.") }}<br><em>{{ __("Feel every note.") }}</em></h1><p class="muted">{{ __("An evening of harmony. A moment to remember.") }}<br>{{ __("Discover our upcoming concerts and be part of the music.") }}</p></div><div class="hero-art" aria-hidden="true"><b>voce.</b>@foreach([55,90,65,110,80,120,90,65] as $height)<span style="height:{{ $height }}px"></span>@endforeach</div></section>
<div class="section-heading"><h2>{{ __("Upcoming concerts") }}</h2><span class="muted tiny">{{ __(':count experiences to discover', ['count'=>$events->count()]) }}</span></div>
<div class="grid">
@forelse($events as $event)
@php($availability=$event->availability())
<article class="card {{ $availability['remaining']===0?'sold-out':'' }}">
<div class="poster">@if($event->thumbnail)<img src="{{ route('tickets.thumbnail',$event) }}" alt="{{ $event->title }}" loading="lazy">@endif
<div class="poster-price"><span>{{ __("PRICE RANGE") }}</span><strong>Rp {{ number_format($event->classes->min('price')??0,0,',','.') }}<br>– {{ number_format($event->classes->max('price')??0,0,',','.') }}</strong></div>
<div class="badges">@if($event->limited_seating)<span class="badge white">{{ __("Limited Seating") }}</span>@endif @if($availability['almost'] && $availability['remaining']>0)<span class="badge yellow">{{ __("Almost Sold") }}</span>@endif</div>
@if($event->seating_type==='free')<span class="badge free">{{ __("FREE SEATING") }}</span>@endif</div>
<div class="card-body"><div class="eyebrow">{{ $event->starts_at->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') }} WIB</div><h3>{{ $event->title }}</h3><p class="muted tiny">{{ $event->location }}</p><div class="card-foot"><div><div class="price">{{ __(':count tickets left', ['count'=>$availability['remaining']]) }}</div><div class="muted tiny">{{ $event->seating_type==='numbered'?__("Choose your seats"):__("Choose your class") }}</div></div>
@if($availability['remaining']>0)<a class="btn" href="{{ route('tickets.select',$event) }}">{{ __("Purchase ↗") }}</a>@else<button class="btn" disabled>{{ __("Sold out") }}</button>@endif</div></div></article>
@empty<div class="empty" style="grid-column:1/-1"><h2>{{ __("The next great evening is on its way.") }}</h2><p class="muted">{{ __("Our upcoming concerts will appear here when booking opens.") }}</p><a class="btn secondary" href="/">{{ __("Explore the choir") }}</a></div>@endforelse
</div>
@endsection