<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System maintenance</title>
</head>
<body>
    <h1>System maintenance</h1>
    <p>Clear Laravel's caches and regenerate Composer's autoloader.</p>
    <form method="POST" action="{{ route('system.maintenance.run') }}">
        @csrf
        <button type="submit">Run optimize:clear and dump-autoload</button>
    </form>
    @isset($output)
        <h2>{{ $successful ? 'Maintenance completed' : 'Maintenance failed' }}</h2>
        <pre style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $output }}</pre>
    @endisset
    <p><a href="{{ route('portal.dashboard') }}">Back to portal</a></p>
</body>
</html>
