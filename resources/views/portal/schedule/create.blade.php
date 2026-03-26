{{-- resources/views/portal/schedule/create.blade.php --}}
@extends('portal.layouts.app')
@section('title', 'Add Schedule')
@section('page-title', 'Schedule — Add Schedule')
@section('content')
<div class="page-header">
    <div><h1>Add Schedule</h1></div>
    <a href="{{ route('portal.schedule.index') }}" class="btn btn-secondary">← Back</a>
</div>
<div style="max-width:640px;">
    @include('portal.schedule._form', ['schedule' => null])
</div>
@endsection
