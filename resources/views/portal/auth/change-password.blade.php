<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — VOS Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #09090b; --surface: #111113; --border: #1f1f23;
            --accent: #c8a96e; --accent2: #e8c88a;
            --text: #e8e8e8; --muted: #71717a;
            --danger: #ef4444; --success: #22c55e;
        }
        body {
            background: var(--bg); color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh; display: flex;
            align-items: center; justify-content: center;
            padding: 2rem;
        }
        .card {
            width: 100%; max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 2.5rem;
            animation: fadeUp .4s ease both;
        }
        @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:none; } }

        .icon-wrap {
            width: 56px; height: 56px; border-radius: 50%;
            background: rgba(200,169,110,.1);
            border: 1.5px solid rgba(200,169,110,.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1.5rem;
        }
        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem; font-weight: 800;
            margin-bottom: .5rem;
        }
        p.subtitle {
            color: var(--muted); font-size: .875rem;
            line-height: 1.6; margin-bottom: 2rem;
        }
        .alert {
            padding: .8rem 1rem; border-radius: 8px;
            font-size: .875rem; margin-bottom: 1.25rem;
        }
        .alert-error {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.25);
            color: #fca5a5;
        }
        .form-group { margin-bottom: 1.1rem; }
        label {
            display: block; font-size: .75rem; font-weight: 600;
            letter-spacing: .07em; text-transform: uppercase;
            color: var(--muted); margin-bottom: .4rem;
        }
        input[type=password] {
            width: 100%;
            background: #1a1a1f;
            border: 1px solid #2a2a32;
            border-radius: 8px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            padding: .75rem 1rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        input[type=password]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,169,110,.12);
        }
        input.is-invalid { border-color: var(--danger); }
        .field-error { font-size: .78rem; color: #fca5a5; margin-top: .3rem; }

        .requirements {
            font-size: .75rem; color: var(--muted);
            margin-top: .4rem; line-height: 1.6;
        }
        button[type=submit] {
            width: 100%;
            background: var(--accent); color: #09090b;
            border: none; border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 700;
            padding: .85rem; cursor: pointer;
            transition: background .15s;
            margin-top: .5rem;
        }
        button[type=submit]:hover { background: var(--accent2); }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">🔑</div>
    <h1>Set New Password</h1>
    <p class="subtitle">
        Your password was reset by an administrator. You must set a new password before continuing.
    </p>

    @if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('portal.password.update') }}">
        @csrf

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password"
                   class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                   placeholder="••••••••" autocomplete="new-password" autofocus>
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
            <div class="requirements">Minimum 8 characters · Mixed case · At least one number</div>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="{{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
                   placeholder="••••••••" autocomplete="new-password">
            @error('password_confirmation') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <button type="submit">Set Password & Continue →</button>
    </form>
</div>
</body>
</html>
