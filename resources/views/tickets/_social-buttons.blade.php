@php
    $googleEnabled = \App\Services\TicketSocialLogin::enabled('google');
    $appleEnabled = \App\Services\TicketSocialLogin::enabled('apple');
@endphp
@if($googleEnabled || $appleEnabled)
<div class="social-auth" id="social-auth" @if(!$googleEnabled) hidden @endif>
    @if($googleEnabled)
    <a class="social-button" href="{{ route('tickets.social.redirect', ['provider'=>'google', 'mode'=>$mode]) }}">
        <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true"><path fill="#4285F4" d="M43.6 24.5c0-1.4-.1-2.8-.4-4.2H24v8h11a9.4 9.4 0 0 1-4.1 6.2v5.2h6.7c4-3.7 6-9.1 6-15.2Z"/><path fill="#34A853" d="M24 44c5.5 0 10.1-1.8 13.5-4.9l-6.7-5.2c-1.8 1.2-4.1 1.9-6.8 1.9-5.3 0-9.8-3.6-11.4-8.5H5.7v5.4A20.4 20.4 0 0 0 24 44Z"/><path fill="#FBBC05" d="M12.6 27.3a12.2 12.2 0 0 1 0-7.6v-5.4H5.7a20 20 0 0 0 0 18.4l6.9-5.4Z"/><path fill="#EA4335" d="M24 11.2c3 0 5.6 1 7.7 3L37.5 8A19.4 19.4 0 0 0 24 3 20.4 20.4 0 0 0 5.7 14.3l6.9 5.4c1.6-4.9 6.1-8.5 11.4-8.5Z"/></svg>
        {{ __('Continue with Google') }}
    </a>
    @endif
    @if($appleEnabled)
    <a class="social-button social-button-apple" id="apple-sign-in" hidden href="{{ route('tickets.social.redirect', ['provider'=>'apple', 'mode'=>$mode]) }}">
        <svg width="20" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.1 12.3c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.2-2.8.9-3.5.9-.7 0-1.8-.9-3-.9-1.6 0-3.1.9-3.9 2.3-1.7 2.9-.4 7.2 1.2 9.5.8 1.1 1.7 2.3 2.9 2.2 1.2 0 1.6-.7 3.1-.7s1.9.7 3.2.7 2.1-1.1 2.8-2.2c.9-1.3 1.3-2.6 1.3-2.7-.1 0-2.7-1-2.7-3.8ZM14.8 5.5c.6-.8 1.1-1.9 1-3-.9 0-2.1.6-2.8 1.4-.6.7-1.2 1.8-1 2.9 1 .1 2.1-.5 2.8-1.3Z"/></svg>
        {{ __('Continue with Apple') }}
    </a>
    @endif
    <p class="tiny muted social-divider">{{ __('or continue with email') }}</p>
</div>
@push('scripts')
<script>
(() => {
    const apple = document.getElementById('apple-sign-in');
    const platform = navigator.userAgentData?.platform || navigator.platform || '';
    const appleDevice = /iPhone|iPad|iPod/.test(navigator.userAgent) || /Mac/i.test(platform);
    if (apple && appleDevice) {
        apple.hidden = false;
        document.getElementById('social-auth').hidden = false;
    }
})();
</script>
@endpush
@endif
