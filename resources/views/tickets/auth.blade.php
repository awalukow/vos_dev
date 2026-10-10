@extends('tickets.layout')
@section('title',$mode==='register'?__("Create your account"):($mode==='verify'?__('Verify your email'):__('Welcome back')))
@section('content')
@php
    $musicQuotes = collect(config('music_quotes'))->map(fn ($quote) => [
        'text' => __($quote['quote']),
        'attribution' => '- '.$quote['author'].', '.__($quote['occupation']),
    ])->values()->all();
    $quoteIndex = array_rand($musicQuotes);
@endphp
<div class="auth-wrap"><aside class="auth-story"><div class="eyebrow" style="color:#d6b6ff">{{ __("Your next live experience") }}</div>
<div class="auth-quotes">
    <blockquote class="auth-quote">
        <h2 id="music-quote">“{{ $musicQuotes[$quoteIndex]['text'] }}”</h2>
        <p id="music-attribution">{{ $musicQuotes[$quoteIndex]['attribution'] }}</p>
    </blockquote>
    <button type="button" id="music-quote-toggle" class="link-button quote-toggle" hidden>{{ __('Pause quotes') }}</button>
</div></aside>
<div class="auth-form">
@if($mode==='verify')
<h2>{{ __("Check your inbox") }}</h2><p class="muted">{{ __('Enter the six-digit code sent to :email. It expires in 10 minutes.', ['email'=>auth('customer')->user()->email]) }}</p>
<form method="post" action="{{ route('tickets.verify') }}">@csrf<label for="otp">{{ __("Verification code") }}</label><input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required><button class="btn full" style="margin-top:24px">{{ __("Verify email") }}</button></form>
<form method="post" action="{{ route('tickets.resend') }}">@csrf<button class="link-button" style="margin-top:18px">{{ __("Resend code") }}</button></form>
@else
<h2>{{ $mode==='register'?__("Make yourself at home."):__("Welcome back.") }}</h2><p class="muted">{{ $mode==='register'?__("Create your customer account to book tickets."):__("Sign in to book your next concert.") }}</p>
<form method="post" action="{{ route('tickets.'.$mode) }}">@csrf
@if($mode==='register')
<label for="name">{{ __("Full name") }}</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required>
<div class="form-grid"><div><label for="dob">{{ __("Date of birth") }}</label><input type="text" inputmode="numeric" placeholder="DD/MM/YYYY" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" id="dob" name="dob" value="{{ old('dob') }}" required></div><div><label for="phone">{{ __("Phone number") }}</label><input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="628123456789" required></div></div>
@endif
<label for="email">{{ __("Email address") }}</label><input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
<label for="password">{{ __("Password") }}{{ $mode==='register'?__(" · at least 10 characters"):'' }}</label><input type="password" id="password" name="password" autocomplete="{{ $mode==='register'?'new-password':'current-password' }}" @if($mode==='register') minlength="10" @endif required>
@if($mode==='register')<label for="password_confirmation">{{ __("Confirm password") }}</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
@else<label class="check"><input type="checkbox" name="remember" value="1"> {{ __("Keep me signed in") }}</label>@endif
<button class="btn full" style="margin-top:24px">{{ $mode==='register'?__("Create account & verify email"):__("Sign in") }} →</button></form>
@if($mode==='register')
<p class="tiny muted" style="margin-top:20px">{{ __("Already have an account?") }} <a href="{{ route('tickets.login') }}">{{ __("Sign in") }}</a></p>
@else
<div class="auth-register">
<span class="muted">{{ __("New to VOS?") }}</span>
<a class="btn register-button" href="{{ route('tickets.register') }}">{{ __("Register") }}</a>
</div>
@endif
@endif
</div></div>
@endsection


@push('scripts')
@include('tickets._customer-inputs')
<script>
(() => {
    const quotes = {{ Illuminate\Support\Js::from($musicQuotes) }};
    let current = {{ $quoteIndex }};
    let paused = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const toggle = document.getElementById('music-quote-toggle');
    const updateToggle = () => {
        toggle.textContent = paused ? {{ Illuminate\Support\Js::from(__('Resume quotes')) }} : {{ Illuminate\Support\Js::from(__('Pause quotes')) }};
    };
    if (quotes.length < 2) return;
    toggle.hidden = false;
    updateToggle();
    toggle.addEventListener('click', () => { paused = !paused; updateToggle(); });
    window.setInterval(() => {
        if (paused || document.hidden) return;
        // Pick any other quote, keeping the text and its attribution together.
        current = (current + 1 + Math.floor(Math.random() * (quotes.length - 1))) % quotes.length;
        document.getElementById('music-quote').textContent = '“' + quotes[current].text + '”';
        document.getElementById('music-attribution').textContent = quotes[current].attribution;
    }, 12000);
})();
</script>
@endpush
