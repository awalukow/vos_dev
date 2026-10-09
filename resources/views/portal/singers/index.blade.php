@extends('portal.layouts.app')
@section('title','Singer List')
@section('page-title','User Management / Singer List')
@push('styles')
@include('portal.ticketing._styles')
@endpush
@section('content')
<div class="portal-ticketing">
<div class="page-head"><h1>Singer List</h1><p class="muted">Manage singers available for customer referrals. Deactivating a singer preserves their previous referrals.</p></div>
@if($errors->any())<div class="notice error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="panel" method="post" action="{{ route('portal.singers.store') }}">@csrf
<h2>Add singer</h2><label for="singer-name">Singer name</label><input id="singer-name" name="name" maxlength="120" required value="{{ old('name') }}">
<label for="referral-code">Referral code</label><input id="referral-code" name="referral_code" maxlength="30" pattern="[A-Za-z0-9_-]+" required value="{{ old('referral_code') }}">
<label class="check"><input type="checkbox" name="active" value="1" checked> Active</label><button class="btn btn-primary">Add singer</button>
</form>
<div class="table-responsive"><table><thead><tr><th>Singer name</th><th>Referral code</th><th>Active</th><th>Action</th></tr></thead><tbody>
@forelse($singers as $singer)
<tr><td><input form="singer-{{ $singer->id }}" aria-label="Singer name" name="name" value="{{ $singer->name }}" maxlength="120" required></td><td><input form="singer-{{ $singer->id }}" aria-label="Referral code" name="referral_code" value="{{ $singer->referral_code }}" maxlength="30" pattern="[A-Za-z0-9_-]+" required></td><td><input form="singer-{{ $singer->id }}" aria-label="Active" type="checkbox" name="active" value="1" @checked($singer->active)></td><td><form id="singer-{{ $singer->id }}" method="post" action="{{ route('portal.singers.update',$singer) }}">@csrf<button class="btn btn-secondary">Save</button></form></td></tr>
@empty<tr><td colspan="4">No singers yet.</td></tr>@endforelse
</tbody></table></div>
{{ $singers->links('portal.components.pagination') }}
</div>
@endsection
