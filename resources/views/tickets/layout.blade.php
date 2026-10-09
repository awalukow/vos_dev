<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','VOS Tickets') · Voice of Soul</title><link rel="stylesheet" href="{{ route('tickets.styles',['v'=>substr(hash_file('sha256',public_path('css/tickets.css')),0,12)]) }}">@stack('head')</head>
<body class="ticket-app"><div class="shell">
<nav class="topnav" aria-label="{{ __('Main navigation') }}">
<a class="brand" href="{{ route('tickets.events') }}" aria-label="Voice of Soul Choir tickets"><span class="brand-logo"><img src="{{ route('tickets.logo') }}" alt="" width="76" height="76"></span><span class="brand-wordmark"><span>VOICE OF SOUL</span><!--<span>CHOIR</span>>--><small>CONCERT TICKETS</small></span></a>
<div class="links"><form method="post" action="{{ route('tickets.language') }}">@csrf<label class="tiny" for="ticket-language">{{ __('Language') }}</label><select id="ticket-language" name="locale" onchange="this.form.submit()"><option value="id" @selected(app()->getLocale()==='id')>Bahasa Indonesia</option><option value="en" @selected(app()->getLocale()==='en')>English</option></select><noscript><button type="submit">OK</button></noscript></form><a href="/">{{ __("Choir home") }}</a><a href="{{ route('tickets.events') }}">{{ __("Concerts") }}</a>
@auth('customer')<a href="{{ route('tickets.orders') }}">{{ __("My tickets") }}</a><form method="post" action="{{ route('tickets.logout') }}">@csrf<button class="link-button">{{ __("Sign out") }}</button></form>
@else<a class="btn dark" href="{{ route('tickets.login') }}">{{ __("Sign in") }}</a>@endauth
</div></nav>
<main>
@foreach(['success','error','info'] as $kind) @if(session($kind))<div role="status" class="notice {{ $kind }}">{{ __(session($kind)) }}</div>@endif @endforeach
@if($errors->any())<div class="notice error" role="alert"><strong>{{ __("Please check the following:") }}</strong><ul>@foreach($errors->all() as $error)<li>{{ __($error) }}</li>@endforeach</ul></div>@endif
@yield('content')
</main><footer>Voice of Soul Choir &nbsp; · &nbsp; {{ __('Music brings us together.') }} <a style="float:right" href="{{ route('portal.login') }}">{{ __("Staff portal") }}</a></footer>
</div>@stack('scripts')</body></html>
