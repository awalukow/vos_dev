@extends('portal.ticketing.layout')
@section('ticket-page-title','Event Details')
@section('ticket-content')
@php($locked=$event->exists && $event->orders()->withoutGlobalScope('active')->exists())
<div class="page-head"><div class="eyebrow">Concert details</div><h1 class="admin-title">{{ $event->exists?'Edit concert':'Create a concert' }}</h1></div>
<form method="post" enctype="multipart/form-data" id="event-form" action="{{ $event->exists?route('portal.ticketing.events.update',$event):route('portal.ticketing.events.store') }}">@csrf
<div class="split"><div class="panel"><h2>The essentials</h2><div class="form-grid">
<div class="wide"><label for="title">Concert title</label><input id="title" name="title" value="{{ old('title',$event->title) }}" required maxlength="190"></div>
<div><label for="starts_at">Date & time (WIB / Jakarta)</label><input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at',$event->starts_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}" required></div>
<div><label for="location">Location shown to customers</label><input id="location" name="location" value="{{ old('location',$event->location) }}" required></div>
<div class="wide"><label for="description">About this concert</label><textarea id="description" name="description" rows="4">{{ old('description',$event->description) }}</textarea></div>
<div><label for="seating_type">Seating type</label><select id="seating_type" name="seating_type" @if($locked) style="pointer-events:none" aria-readonly="true" @endif><option value="free" @selected(old('seating_type',$event->seating_type)==='free')>Free seating</option><option value="numbered" @selected(old('seating_type',$event->seating_type)==='numbered')>Numbered seating</option></select></div>
<div id="venue-field"><label for="venue">Venue layout</label><select id="venue" name="ticket_venue_id"><option value="">Choose a venue</option>@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected(old('ticket_venue_id',$event->ticket_venue_id)==$venue->id)>{{ $venue->name }} · {{ count($venue->layout['seats']) }} seats</option>@endforeach</select><p class="tiny muted">Class names must match the venue’s seat classes. <a href="{{ route('portal.ticketing.venues') }}">Manage venues</a></p></div>
</div>
<hr style="border:0;border-top:1px solid var(--border);margin:30px 0"><h2>Ticket classes</h2><p class="tiny muted">Prices in whole Rupiah. For numbered seating, capacity comes from the venue map.</p>
@if($locked)<div class="notice">This event has bookings. Seating, classes, prices and capacities are locked to preserve purchased tickets. You can still edit its title, date, description, thumbnail and publication status.</div>@endif
<input type="hidden" name="classes_json" id="classes-json" value="{{ old('classes_json',$event->exists?$event->classes->map->only(['name','color','price','capacity'])->toJson():json_encode([['name'=>'Regular','color'=>'#713cce','price'=>150000,'capacity'=>100]])) }}">
<div id="class-editor"></div>@unless($locked)<button type="button" id="add-class" class="btn btn-secondary">+ Add class</button>@endunless
</div><aside class="panel"><h2>Make it stand out</h2>
@if($event->thumbnail)<img style="width:100%;border-radius:10px" src="{{ route('tickets.thumbnail',$event) }}" alt="Current thumbnail">@endif
<label for="thumbnail">Concert thumbnail</label><input id="thumbnail" name="thumbnail" type="file" accept="image/png,image/jpeg,image/webp"><p class="tiny muted">JPG, PNG or WebP, up to 4 MB. Required to publish.</p>
<label class="check"><input type="checkbox" name="limited_seating" value="1" @checked(old('limited_seating',$event->limited_seating))> Limited Seating badge</label>
<label class="check"><input type="checkbox" name="published" value="1" @checked(old('published',$event->published))> Publish to storefront</label>
<p class="tiny muted">Published concerts appear on the storefront until their start time (WIB / Jakarta). To list a concert again, set a future date and time.</p>
<button class="btn btn-primary full" style="margin-top:24px">Save concert</button>
</aside></div></form>
@endsection
@push('scripts')
<script>
(() => {
 const field=document.getElementById('classes-json'), editor=document.getElementById('class-editor'), locked=@json($locked);
 let classes;try{classes=JSON.parse(field.value.replaceAll('&quot;','"'));}catch(e){classes=[];}
 if(!Array.isArray(classes))classes=[];
 function sync(){field.value=JSON.stringify(classes);}
 function draw(){
  editor.replaceChildren();
  classes.forEach((c,i)=>{
   const box=document.createElement('div');box.className='panel class-editor-panel';
   const grid=document.createElement('div');grid.className='form-grid';
   [['name','Class name','text'],['color','Seat color','color'],['price','Price (Rp)','number'],['capacity','Capacity (free seating)','number']].forEach(([key,label,type])=>{
    const wrap=document.createElement('div'),l=document.createElement('label'),input=document.createElement('input');
    l.textContent=label;input.type=type;input.value=c[key];input.required=true;input.disabled=locked;
    input.id='class-'+i+'-'+key;l.htmlFor=input.id;
    if(type==='number'){input.min=key==='price'?'1':'0';input.step='1';}
    input.addEventListener('input',()=>{classes[i][key]=type==='number'?Number(input.value):input.value;sync();});
    wrap.append(l,input);grid.append(wrap);
   });
   box.append(grid);
   if(!locked){const remove=document.createElement('button');remove.type='button';remove.className='link-button';remove.style.marginTop='15px';remove.textContent='Remove class';remove.addEventListener('click',()=>{classes.splice(i,1);draw();});box.append(remove);}
   editor.append(box);
  });sync();
 }
 document.getElementById('add-class')?.addEventListener('click',()=>{classes.push({name:'',color:'#3e90a1',price:150000,capacity:100});draw();});
 const seating=document.getElementById('seating_type'),venue=document.getElementById('venue-field');
 function visibility(){venue.hidden=seating.value!=='numbered';document.getElementById('venue').required=seating.value==='numbered';}
 seating.addEventListener('change',visibility);visibility();draw();
})();
</script>
@endpush
