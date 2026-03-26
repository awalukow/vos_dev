<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOS Portal — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #09090b;
            --surface:  #111113;
            --border:   #1f1f23;
            --accent:   #c8a96e;
            --accent2:  #e8c88a;
            --text:     #e8e8e8;
            --muted:    #71717a;
            --danger:   #ef4444;
            --radius:   12px;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── Left panel: branding ── */
        .brand-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem;
            background: var(--surface);
            border-right: 1px solid var(--border);
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 60% at 20% 80%, rgba(200,169,110,.12) 0%, transparent 70%),
                radial-gradient(ellipse 40% 40% at 80% 20%, rgba(200,169,110,.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-logo {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: .05em;
            color: var(--accent);
        }

        .brand-logo span { color: var(--text); }

        .brand-hero { position: relative; z-index: 1; }

        .brand-hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.5rem, 4vw, 3.8rem);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -.02em;
            margin-bottom: 1.5rem;
        }

        .brand-hero h1 em {
            font-style: normal;
            color: var(--accent);
        }

        .brand-hero p {
            color: var(--muted);
            font-size: .95rem;
            line-height: 1.7;
            max-width: 36ch;
        }

        .brand-footer {
            font-size: .75rem;
            color: var(--muted);
        }

        /* ── Decorative grid lines ── */
        .grid-deco {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        /* ── Right panel: form ── */
        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }

        .form-card {
            width: 100%;
            max-width: 420px;
        }

        .form-header {
            margin-bottom: 2.5rem;
        }

        .form-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.9rem;
            font-weight: 700;
            margin-bottom: .5rem;
        }

        .form-header p {
            color: var(--muted);
            font-size: .9rem;
        }

        /* Alert */
        .alert {
            padding: .85rem 1rem;
            border-radius: 8px;
            font-size: .875rem;
            margin-bottom: 1.5rem;
        }
        .alert-error {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.25);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(200,169,110,.1);
            border: 1px solid rgba(200,169,110,.25);
            color: var(--accent2);
        }

        /* Form elements */
        .field { margin-bottom: 1.25rem; }

        label {
            display: block;
            font-size: .8rem;
            font-weight: 500;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: .5rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            padding: .8rem 1rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,169,110,.15);
        }

        input.is-invalid {
            border-color: var(--danger);
        }

        .field-error {
            font-size: .8rem;
            color: #fca5a5;
            margin-top: .35rem;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.75rem;
        }

        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--accent);
        }

        .remember-row label {
            margin: 0;
            text-transform: none;
            letter-spacing: 0;
            font-size: .875rem;
            color: var(--muted);
        }

        .btn-primary {
            width: 100%;
            background: var(--accent);
            color: #09090b;
            border: none;
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .03em;
            padding: .9rem;
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
        }

        .btn-primary:hover {
            background: var(--accent2);
            box-shadow: 0 4px 24px rgba(200,169,110,.3);
        }

        .btn-primary:active { transform: scale(.98); }

        /* Responsive */
        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .brand-panel { display: none; }
        }

        /* Subtle animation */
        .form-card { animation: fadeUp .5s ease both; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- ── Brand Panel ──────────────────────────────── -->
<div class="brand-panel">
    <div class="grid-deco"></div>
    <!--<div class="brand-logo">VOS<span>.</span></div>-->
    <div class="brand-logo">
        <img src="../../assets/images/logo-light.png" alt="VOS Logo" class="light" height="60px" width="83px">
    </div>

    <div class="brand-hero">
        <h1>Voice<br>Of <em>Soul</em><br>Portal</h1>
        <p>--</p>
    </div>

    <div class="brand-footer">
        © {{ date('Y') }} Axcellent. All rights reserved.
    </div>
</div>

<!-- ── Form Panel ───────────────────────────────── -->
<div class="form-panel">
    <div class="form-card">
        <div class="form-header">
            <h2>Welcome back</h2>
            <p>Sign in to your portal account to continue.</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('portal.login.post') }}">
            @csrf

            <div class="field">
                <label for="login">Username or Email</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    value="{{ old('login') }}"
                    autocomplete="username"
                    placeholder="yudasiskariot@gmail.com"
                    class="{{ $errors->has('login') ? 'is-invalid' : '' }}"
                    autofocus
                >
                @error('login')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                >
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Keep me signed in</label>
            </div>

            <button type="submit" class="btn-primary">Sign In →</button>
        </form>
    </div>
</div>

</body>
</html>
