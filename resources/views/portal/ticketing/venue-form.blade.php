@extends('portal.ticketing.layout')
@section('ticket-page-title','Venue Designer')
@section('ticket-content')
<div class="page-head"><div class="eyebrow">Venue studio</div><h1 class="admin-title">{{ $venue->exists?'Shape your space.':'Design a venue.' }}</h1><p class="muted">Add rows, then drag seats to create aisles and sections. Click a seat to edit its label and class.</p></div>
<form method="post" enctype="multipart/form-data" id="venue-form" action="{{ $venue->exists?route('portal.ticketing.venues.update',$venue):route('portal.ticketing.venues.store') }}">@csrf
<div class="panel"><div class="form-grid"><div><label for="venue-name">Venue name</label><input id="venue-name" name="name" value="{{ old('name',$venue->name) }}" required></div><div><label for="address">Address</label><input id="address" name="address" value="{{ old('address',$venue->address) }}"></div></div></div>
<div class="panel"><h2>Build the seating map</h2><p class="tiny muted">Coordinates are grid positions starting at 0. Each seat needs a unique label and position. Maximum 3,000 seats; X and Y from 0 to 100.</p>
<div class="toolbar">
<div><label for="row-prefix">Row label</label><input id="row-prefix" value="A" maxlength="20"></div>
<div><label for="row-class">Class name</label><input id="row-class" value="Regular" maxlength="60"></div>
<div><label for="row-count">Seat count</label><input id="row-count" type="number" value="12" min="1" max="100"></div>
<div><label for="row-x">Start X</label><input id="row-x" type="number" value="0" min="0" max="100"></div>
<div><label for="row-y">Row Y</label><input id="row-y" type="number" value="0" min="0" max="100"></div>
<button type="button" id="add-row" class="btn btn-primary">+ Add row</button></div>
<div class="notice" id="editor-message" role="status">Tip: use matching class names when you configure event prices.</div>
<div id="seat-properties" class="panel" hidden><h3>Edit selected seat</h3><div class="toolbar"><div><label for="seat-label">Label</label><input id="seat-label" maxlength="40"></div><div><label for="seat-class">Class</label><input id="seat-class" maxlength="60"></div><div><label for="seat-x">X</label><input id="seat-x" type="number" step="0.25" min="0" max="100"></div><div><label for="seat-y">Y</label><input id="seat-y" type="number" min="0" max="100"></div><button type="button" class="btn btn-secondary" id="apply-seat">Apply</button><button type="button" class="btn btn-danger" id="remove-seat">Remove</button></div></div>
<div class="panel"><h3>Change seat classes together</h3><p class="tiny muted">Enable multi-select and click seats, or select a whole row. Choose an existing class or type a new name.</p><label><input type="checkbox" id="multi-select"> Multi-select seats</label><div class="toolbar"><div><label for="select-row">Row</label><select id="select-row"></select></div><button type="button" class="btn btn-secondary" id="select-row-seats">Select row</button><button type="button" class="btn btn-secondary" id="select-all-seats">Select all</button><button type="button" class="btn btn-secondary" id="clear-selection">Clear selection</button><div><label for="bulk-class">New class</label><input id="bulk-class" list="venue-classes" maxlength="60" value="Regular"></div><button type="button" class="btn btn-primary" id="apply-class">Apply class to selected seats</button></div><datalist id="venue-classes"></datalist><p id="selection-count" role="status"></p></div>
<div class="editor-board"><div id="venue-canvas" class="venue-canvas"><div class="stage">S T A G E</div><div id="venue-board" class="seat-map" style="min-height:200px" aria-label="Editable venue seating"></div></div></div><p class="tiny muted" id="seat-count"></p>
</div>
<div class="panel"><h2>Import or export</h2><p class="tiny muted">Use the VOS JSON format (version 1). Importing replaces the current unsaved layout. Export a saved venue from the venue library.</p><label for="layout-file">Import JSON file</label><input type="file" id="layout-file" name="layout_file" accept=".json,application/json">
<details style="margin-top:20px"><summary>Advanced: edit layout JSON</summary><textarea name="layout" id="layout-json" rows="10">{{ old('layout',json_encode($venue->layout,JSON_PRETTY_PRINT)) }}</textarea><button type="button" id="apply-json" class="btn btn-secondary">Apply JSON to preview</button></details>
<div class="actions"><button class="btn btn-primary">Save venue</button><a class="btn btn-secondary" href="{{ route('portal.ticketing.venues') }}">Back to venues</a></div>
</div></form>
@endsection
@push('scripts')
<script src="{{ route('tickets.designer') }}"></script>
@endpush
