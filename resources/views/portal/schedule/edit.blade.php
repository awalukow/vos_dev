@extends('portal.layouts.app')
@section('title', 'Edit Schedule')
@section('page-title', 'Schedule — Edit Schedule')
@section('content')
<div class="page-header">
    <div><h1>Edit Schedule</h1><p>{{ $schedule->event_name }}</p></div>
    <a href="{{ route('portal.schedule.index') }}" class="btn btn-secondary">← Back</a>
</div>
<div style="max-width:640px;">
    @include('portal.schedule._form', ['schedule' => $schedule])
</div>
@endsection
