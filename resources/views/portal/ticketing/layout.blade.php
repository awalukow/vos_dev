@extends('portal.layouts.app')
@section('title','Ticketing Management')
@section('page-title')
Ticketing / @yield('ticket-page-title', 'Management')
@endsection

@push('styles')
@include('portal.ticketing._styles')
@endpush

@section('content')
<div class="portal-ticketing">
    @if($errors->any())
    <div class="notice error" role="alert">
        <strong>Please check the following:</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif
    @yield('ticket-content')
</div>
@endsection