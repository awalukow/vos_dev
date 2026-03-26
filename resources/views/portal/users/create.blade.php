{{-- resources/views/portal/users/create.blade.php --}}
@extends('portal.layouts.app')
@section('title', 'Add User')
@section('page-title', 'User Management — Add User')

@section('content')
<div class="page-header">
    <div><h1>Add User</h1><p>Create a new portal account.</p></div>
    <a href="{{ route('portal.users.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="max-width:640px;">
@include('portal.users._form', ['user' => null, 'roles' => $roles])
</div>
@endsection
