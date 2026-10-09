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
<div class="section-heading previous-heading"><h2>{{ __('Previous Concerts') }}</h2><span class="muted tiny">{{ __(':count concerts', ['count'=>$previous->count()]) }}</span></div>
<div class="previous-grid">
@forelse($previous as $event)
<article class="card previous-card"><div class="poster">@if($event->thumbnail)<img src="{{ route('tickets.thumbnail',$event) }}" alt="{{ $event->title }}" loading="lazy">@endif</div><div class="card-body"><div class="eyebrow">{{ $event->starts_at->timezone('Asia/Jakarta')->translatedFormat('d M Y · H:i') }} WIB</div><h3>{{ $event->title }}</h3><p class="muted tiny">{{ $event->location }}</p><button class="btn secondary" type="button" data-memories="{{ $event->id }}">{{ __('See Memories') }} ↗</button></div></article>
@empty<p class="muted tiny">{{ __('No previous concerts yet.') }}</p>@endforelse
</div>
<dialog id="concert-memories" aria-labelledby="memory-title" style="width:min(900px,94vw);max-height:92vh;border:0;border-radius:20px;padding:24px;background:#171322;color:#fff">
<div style="display:flex;justify-content:space-between;align-items:center;gap:20px"><h2 id="memory-title"></h2><button type="button" class="btn secondary" id="memory-close" aria-label="Close memories">✕</button></div>
<div id="memory-tabs" style="display:flex;gap:12px;margin-bottom:16px"><button type="button" class="btn secondary" id="memory-photos">Photos</button><button type="button" class="btn secondary" id="memory-video">Video</button></div>
<div id="memory-content"></div><div id="memory-controls" style="display:flex;justify-content:space-between;align-items:center;margin-top:16px"><button type="button" class="btn secondary" id="memory-prev" aria-label="Previous photo">←</button><span id="memory-count" aria-live="polite"></span><button type="button" class="btn secondary" id="memory-next" aria-label="Next photo">→</button></div>
</dialog>
@endsection
@push('head')<style>#concert-memories::backdrop{background:rgba(12,8,24,.85)}#concert-memories [hidden]{display:none!important}#memory-content img{width:100%;height:min(56vh,540px);object-fit:contain}#memory-content iframe{width:100%;aspect-ratio:16/9;border:0}</style>@endpush
@push('scripts')
@php($memories=$previous->mapWithKeys(fn($e)=>[$e->id=>['title'=>$e->title,'video'=>$e->memory_video,'photos'=>collect($e->memory_photos??[])->keys()->map(fn($i)=>route('tickets.memory-photo',[$e,$i]))]]))
<script>
(() => {
 const memories=@json($memories), dialog=document.getElementById('concert-memories'),content=document.getElementById('memory-content'),controls=document.getElementById('memory-controls'),tabs=document.getElementById('memory-tabs');
 let selected,index=0,opener,mode='photos';
 function draw(){
  content.replaceChildren();controls.hidden=true;tabs.hidden=!(selected.photos.length && selected.video);
  if(mode==='video' && selected.video){const frame=document.createElement('iframe');frame.src='https://www.youtube-nocookie.com/embed/'+selected.video;frame.title=selected.title+' — concert video';frame.allow='encrypted-media; picture-in-picture; fullscreen';frame.allowFullscreen=true;frame.referrerPolicy='strict-origin-when-cross-origin';content.append(frame);}
  else if(selected.photos.length){const img=document.createElement('img');img.src=selected.photos[index];img.alt=selected.title+' — photo '+(index+1);content.append(img);controls.hidden=false;document.getElementById('memory-count').textContent=(index+1)+' / '+selected.photos.length;document.getElementById('memory-prev').disabled=selected.photos.length<2;document.getElementById('memory-next').disabled=selected.photos.length<2;}
  else{const empty=document.createElement('p');empty.textContent='Coming Soon';empty.style.cssText='text-align:center;padding:70px 20px;font-size:32px';content.append(empty);}
 }
 document.querySelectorAll('[data-memories]').forEach(button=>button.addEventListener('click',()=>{opener=button;selected=memories[button.dataset.memories];index=0;mode=selected.photos.length?'photos':'video';document.getElementById('memory-title').textContent=selected.title;draw();dialog.showModal();}));
 document.getElementById('memory-close').onclick=()=>dialog.close();
 dialog.addEventListener('close',()=>{content.replaceChildren();opener?.focus();});
 document.getElementById('memory-prev').onclick=()=>{index=(index+selected.photos.length-1)%selected.photos.length;draw();};
 document.getElementById('memory-next').onclick=()=>{index=(index+1)%selected.photos.length;draw();};
 document.getElementById('memory-photos').onclick=()=>{mode='photos';draw();};document.getElementById('memory-video').onclick=()=>{mode='video';draw();};
 dialog.addEventListener('keydown',e=>{if(mode==='photos' && selected.photos.length && ['ArrowLeft','ArrowRight'].includes(e.key)){e.preventDefault();index=(index+selected.photos.length+(e.key==='ArrowRight'?1:-1))%selected.photos.length;draw();}});
})();
</script>
@endpush
