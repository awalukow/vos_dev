@extends('portal.ticketing.layout')
@section('ticket-page-title','Customers & OTP')
@section('ticket-content')
<div class="page-head"><div class="eyebrow">Ticketing / Customers</div><h1 class="admin-title">Your audience.</h1><p class="muted">Customer accounts are separate from portal staff accounts. Manage ticket operators in Portal → Users.</p></div>
@if(session('manual_otp'))<div class="notice"><strong>OTP Generator · {{ session('otp_customer') }}</strong><p style="font-size:32px;letter-spacing:8px">{{ session('manual_otp') }}</p><p class="tiny">Expires in 10 minutes. Share only with the customer after confirming their identity. This code replaces the previous one.</p></div>@endif
<form method="get" class="toolbar" style="margin-bottom:25px"><input name="q" value="{{ request('q') }}" placeholder="Search name or email" aria-label="Search customers" style="max-width:400px"><button class="btn btn-secondary">Search</button></form>
@forelse($customers as $customer)
<div class="panel"><div class="section-heading"><div><h3>{{ $customer->name }}</h3><p class="tiny muted">{{ $customer->email }}</p></div><span class="badge">{{ $customer->email_verified_at?'Verified':'Awaiting OTP' }} · {{ $customer->is_active?'Active':'Inactive' }}</span></div>
<details><summary>Edit customer</summary><form method="post" action="{{ route('portal.ticketing.customers.update',$customer) }}">@csrf<div class="form-grid"><div><label for="name-{{ $customer->id }}">Name</label><input id="name-{{ $customer->id }}" name="name" value="{{ $customer->name }}" required></div><div><label for="phone-{{ $customer->id }}">Phone</label><input id="phone-{{ $customer->id }}" name="phone" value="{{ $customer->phone }}" required></div><div><label for="dob-{{ $customer->id }}">Date of birth</label><input id="dob-{{ $customer->id }}" type="text" inputmode="numeric" placeholder="DD/MM/YYYY" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" name="dob" value="{{ $customer->dob->format('d/m/Y') }}" required></div><div><label class="check"><input type="checkbox" name="is_active" value="1" @checked($customer->is_active)> Account active</label></div></div><button class="btn btn-secondary" style="margin-top:20px">Save customer</button></form></details>
@if(!$customer->email_verified_at && $customer->is_active)<form class="actions" method="post" action="{{ route('portal.ticketing.customers.otp',$customer) }}">@csrf<button class="btn btn-primary">OTP Generator</button></form>@endif
</div>
@empty<div class="empty">No customers found.</div>@endforelse
{{ $customers->links('portal.components.pagination') }}
@endsection
@push('scripts')
@include('tickets._customer-inputs')
@endpush

@push('scripts')
@include('tickets._customer-inputs')
@endpush
