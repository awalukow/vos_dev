@extends('tickets.layout')
@section('title', __('Complete your account'))
@section('content')
<div class="auth-wrap">
    <aside class="auth-story"><div class="eyebrow" style="color:#d6b6ff">{{ __('Your next live experience') }}</div><h2>{{ __('Almost there.') }}</h2><p>{{ __('A few details before your next concert.') }}</p></aside>
    <div class="auth-form">
        <h2>{{ $existing ? __('Link your account') : __('Complete your account') }}</h2>
        <p class="muted">{{ $pending['email'] }}</p>
        <form method="post" action="{{ route('tickets.social.complete') }}">
            @csrf
            @if($existing && !$alreadySignedIn)
                <p>{{ __('An account already uses this email. Enter its password once to connect your sign-in.') }}</p>
                <p class="tiny muted">{{ __('If you registered with another provider, sign in with it first, then return here to connect this provider.') }}</p>
                <label for="password">{{ __('Password') }}</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            @elseif(!$existing)
                @if(!$pending['name'])
                    <label for="name">{{ __('Full name') }}</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required>
                @endif
                @if(!$pending['dob'])
                    <label for="dob">{{ __('Date of birth') }}</label><input type="text" inputmode="numeric" placeholder="DD/MM/YYYY" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" id="dob" name="dob" value="{{ old('dob') }}" autocomplete="bday" required>
                @else
                    <p class="tiny muted">{{ __('Date of birth received from Google: :date', ['date'=>\Carbon\Carbon::parse($pending['dob'])->format('d/m/Y')]) }}</p>
                @endif
                <label for="phone">{{ __('Phone number') }}</label><input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="628123456789" maxlength="30" required>
            @endif
            <button class="btn full" style="margin-top:24px">{{ $existing ? __('Link and sign in') : __('Complete registration') }}</button>
        </form>
        <p class="tiny muted" style="margin-top:20px"><a href="{{ route('tickets.login') }}">{{ __('Back to sign in') }}</a></p>
    </div>
</div>
@endsection
@push('scripts')
@include('tickets._customer-inputs')
@endpush
