@extends('portal.layouts.app')
@section('title', 'Edit User')
@section('page-title', 'User Management — Edit User')

@section('content')
<div class="page-header">
    <div><h1>Edit User</h1><p>{{ $user->name }}</p></div>
    <a href="{{ route('portal.users.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="max-width:640px;">
@include('portal.users._form', ['user' => $user, 'roles' => $roles])
</div>
@endsection
