@extends('portal.layouts.app')
@section('title', 'System Maintenance')
@section('page-title', 'Settings — System Maintenance')

@section('content')
    <div class="page-header"><h1>System Maintenance</h1></div>
    <div class="card">
    <div class="card-header"><h2 class="card-title">Cache and autoloader</h2></div>
    <div class="card-body">
    <p>Clear Laravel's caches and regenerate Composer's autoloader.</p>
    <form method="POST" action="{{ route('system.maintenance.run') }}">
        @csrf
        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Run optimize:clear and dump-autoload</button>
    </form>
    </div>
    </div>
    <div class="card" style="margin-top: 1.5rem;">
    <div class="card-header"><h2 class="card-title">Database migrations</h2></div>
    <div class="card-body">
    <p>Run pending migrations, then clear Laravel's caches.</p>
    <form method="POST" action="{{ route('system.migrate') }}">
        @csrf
        <p style="margin: 1rem 0;">
            <label><input type="checkbox" name="force" value="1" @checked(request()->boolean('force'))> Use --force (required in production)</label>
        </p>
        <button type="submit" class="btn btn-primary">Run migrations</button>
    </form>
    </div>
    </div>
    @isset($output)
        <div class="card" style="margin-top: 1.5rem;" role="status">
            <div class="card-header"><h2 class="card-title">{{ $successful ? 'Maintenance completed' : 'Maintenance failed' }}</h2></div>
            <div class="card-body"><pre style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $output }}</pre></div>
        </div>
    @endisset
@endsection
