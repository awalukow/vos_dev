@extends('portal.layouts.app')
@section('title', 'Roles')
@section('page-title', 'User Management — Roles')
@section('content')
<div class="page-header"><div><h1>Roles</h1><p>Review roles and their menu access. Assign roles from User Management.</p></div>
@if(auth('portal')->user()->isAdministrator())
<a class="btn btn-primary" href="{{ route('portal.settings.menu-permissions') }}">Manage menu permissions</a>
@endif
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Role</th><th>Description</th><th>Users</th><th>Menu access</th></tr></thead>
<tbody>@foreach($roles as $role)
<tr><td>{{ $role->display_name }}<br><small>{{ $role->name }}</small></td><td>{{ $role->description ?: '—' }}</td><td>{{ $role->users_count }}</td><td>{{ $role->menus->where('is_active', true)->pluck('label')->join(', ') ?: 'No menus assigned' }}</td></tr>
@endforeach</tbody></table></div></div>
@endsection
