<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal') — VOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w:    260px;
            --topbar-h:     60px;
            --bg:           #0a0a0c;
            --sidebar-bg:   #0d0d10;
            --surface:      #131316;
            --surface2:     #1a1a1f;
            --border:       #1e1e24;
            --border2:      #2a2a32;
            --accent:       #c8a96e;
            --accent2:      #e8c88a;
            --accent-glow:  rgba(200,169,110,.18);
            --text:         #e8e8ec;
            --text-muted:   #6b6b7b;
            --text-dim:     #3a3a48;
            --success:      #22c55e;
            --danger:       #ef4444;
            --warning:      #f59e0b;
            --info:         #3b82f6;
            --radius:       10px;
            --radius-sm:    6px;
        }

        html, body { height: 100%; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            display: flex;
        }

        /* ══════════════════ SIDEBAR ══════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }

        /* Logo */
        .sidebar-logo {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .logo-mark {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: .85rem;
            color: #09090b;
            flex-shrink: 0;
        }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: var(--text);
            letter-spacing: .04em;
        }

        .logo-text small {
            display: block;
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: .68rem;
            color: var(--text-muted);
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        /* Nav */
        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }

        .nav-section-label {
            padding: .6rem 1.5rem .3rem;
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-dim);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .6rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            border-radius: 0;
            transition: color .15s, background .15s;
            position: relative;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-item:hover {
            color: var(--text);
            background: rgba(255,255,255,.03);
        }

        .nav-item.active {
            color: var(--accent);
            background: var(--accent-glow);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 3px;
            background: var(--accent);
            border-radius: 0 2px 2px 0;
        }

        .nav-icon {
            width: 18px; height: 18px;
            flex-shrink: 0;
            opacity: .7;
        }

        .nav-item.active .nav-icon { opacity: 1; }

        /* Sub-menu */
        .nav-children {
            overflow: hidden;
            max-height: 0;
            transition: max-height .3s ease;
        }

        .nav-children.open { max-height: 300px; }

        .nav-child-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .5rem 1.5rem .5rem 3.1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: .83rem;
            transition: color .15s, background .15s;
            position: relative;
        }

        .nav-child-item:hover { color: var(--text); }
        .nav-child-item.active { color: var(--accent); }

        /* Pending badge on nav */
        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 20px;
            animation: pulse-badge 1.5s ease-in-out infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .7; transform: scale(.92); }
        }

        /* Chevron */
        .nav-chevron {
            margin-left: auto;
            width: 14px; height: 14px;
            transition: transform .25s;
            opacity: .5;
        }

        .nav-item[aria-expanded="true"] .nav-chevron { transform: rotate(90deg); }

        /* User card at bottom */
        .sidebar-user {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--accent-glow);
            border: 1.5px solid var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .85rem;
            color: var(--accent);
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: .83rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role {
            font-size: .7rem;
            color: var(--text-muted);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .logout-btn {
            background: none; border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: .3rem;
            border-radius: 6px;
            transition: color .15s, background .15s;
            display: flex; align-items: center;
        }

        .logout-btn:hover { color: var(--danger); background: rgba(239,68,68,.1); }

        /* ══════════════════ MAIN ══════════════════ */
        .main-wrap {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top bar */
        .topbar {
            height: var(--topbar-h);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 2rem;
            gap: 1rem;
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            flex: 1;
        }

        .topbar-actions { display: flex; align-items: center; gap: .75rem; }

        /* Toast notifications */
        .toasts {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: .6rem;
            pointer-events: none;
        }

        .toast {
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-left: 4px solid var(--accent);
            color: var(--text);
            padding: .8rem 1.1rem;
            border-radius: var(--radius-sm);
            font-size: .85rem;
            max-width: 340px;
            pointer-events: all;
            animation: toastIn .3s ease both, toastOut .3s ease 4.7s both;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }

        .toast.success { border-left-color: var(--success); }
        .toast.error   { border-left-color: var(--danger);  }
        .toast.info    { border-left-color: var(--info);    }

        @keyframes toastIn  { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
        @keyframes toastOut { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(20px); } }

        /* Page content */
        .page-content {
            padding: 2rem;
            flex: 1;
        }

        /* Card */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .card-header {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
        }

        .card-body { padding: 1.5rem; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: var(--radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: .85rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--accent);
            color: #09090b;
            font-weight: 600;
        }

        .btn-primary:hover { background: var(--accent2); }

        .btn-secondary {
            background: var(--surface2);
            color: var(--text);
            border-color: var(--border2);
        }

        .btn-secondary:hover { border-color: var(--accent); color: var(--accent); }

        .btn-danger {
            background: rgba(239,68,68,.12);
            color: #fca5a5;
            border-color: rgba(239,68,68,.25);
        }

        .btn-danger:hover { background: rgba(239,68,68,.22); }

        .btn-sm { padding: .35rem .75rem; font-size: .78rem; }

        /* Table */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left;
            padding: .75rem 1rem;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: .875rem;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }

        tbody tr { transition: background .12s; }
        tbody tr:hover { background: rgba(255,255,255,.02); }

        tbody tr.pending-row {
            background: rgba(200,169,110,.04);
            animation: rowGlow 2s ease-in-out infinite;
        }

        @keyframes rowGlow {
            0%, 100% { background: rgba(200,169,110,.04); }
            50%       { background: rgba(200,169,110,.09); }
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .badge-pending   { background: rgba(245,158,11,.15); color: #fbbf24; }
        .badge-signed    { background: rgba(34,197,94,.12);  color: #4ade80; }
        .badge-completed { background: rgba(59,130,246,.12); color: #60a5fa; }
        .badge-rejected  { background: rgba(239,68,68,.12);  color: #fca5a5; }
        .badge-draft     { background: rgba(107,114,128,.12); color: #9ca3af; }
        .badge-partial   { background: rgba(168,85,247,.12); color: #c084fc; }
        .badge-role      { background: rgba(200,169,110,.12); color: var(--accent); }

        /* Form styles */
        .form-group { margin-bottom: 1.25rem; }

        .form-label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: .45rem;
        }

        .form-control {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .9rem;
            padding: .65rem .9rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,169,110,.12);
        }

        .form-control.is-invalid { border-color: var(--danger); }

        .form-error {
            font-size: .78rem;
            color: #fca5a5;
            margin-top: .3rem;
        }

        textarea.form-control { resize: vertical; min-height: 80px; }
        select.form-control { cursor: pointer; }

        /* Page header */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: .875rem;
            margin-top: .25rem;
        }

        /* Pagination */
        .pagination { display: flex; gap: .3rem; align-items: center; flex-wrap: wrap; }
        .pagination a, .pagination span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px;
            padding: 0 .5rem;
            border-radius: var(--radius-sm);
            font-size: .8rem;
            text-decoration: none;
            color: var(--text-muted);
            border: 1px solid var(--border);
            transition: all .15s;
        }
        .pagination a:hover { color: var(--accent); border-color: var(--accent); }
        .pagination .active span { background: var(--accent); color: #09090b; border-color: var(--accent); font-weight: 600; }
        .pagination .disabled span { opacity: .35; cursor: default; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s; }
            .sidebar.open { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- ══ SIDEBAR ══ --}}
@php
    $user        = Auth::guard('portal')->user();
    $accessKeys  = $user->accessibleMenuKeys();
    $primaryRole = $user->primaryRole();
    $pendingSign = \App\Models\DocumentSignature::where('signer_id', $user->id)
                        ->where('status', 'pending')->count();
    $pendingAccessRequests = (\App\Models\UploadAccessRequest::where('status', 'pending')->count());
    $currentRoute = request()->route()?->getName() ?? '';

    $topMenus = \App\Models\PortalMenu::whereNull('parent_id')
                    ->with('children')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->filter(fn($m) => in_array($m->key, $accessKeys));

    function heroIcon(string $name): string {
        $icons = [
            'home'                     => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
            'document-check'           => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12"/>',
            'document-text'            => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
            'arrow-up-tray'            => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>',
            'chart-bar'                => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
            'users'                    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
            'user'                     => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>',
            'shield-check'             => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
            'cog-6-tooth'              => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'lock-closed'              => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>',
            'plus-circle'              => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'presentation-chart-line'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>',
            'arrow-right-on-rectangle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>',
            'calendar'                 => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>',
            'calendar-days'            => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/>',
            'clipboard-document-list'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>',
        ];
        $path = $icons[$name] ?? '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        return '<svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">' . $path . '</svg>';
    }
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">V</div>
        <div class="logo-text">
            VOS Portal
            <small>Management System</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        @foreach($topMenus as $menu)
            @if($menu->children->isEmpty())
                {{-- Single item --}}
                <a href="{{ $menu->route_name && \Route::has($menu->route_name) ? route($menu->route_name) : '#' }}"
                   class="nav-item {{ str_starts_with($currentRoute, str_replace('.', '.', $menu->route_name ?? '')) ? 'active' : '' }}">
                    {!! heroIcon($menu->icon ?? 'home') !!}
                    {{ $menu->label }}
                    @if($menu->key === 'docsign' && $pendingSign > 0)
                        <span class="nav-badge">{{ $pendingSign }}</span>
                    @endif
                </a>
            @else
                {{-- Parent with children --}}
                @php
                    $childRoutes  = $menu->children->pluck('route_name')->filter()->toArray();
                    $isParentOpen = collect($childRoutes)->contains(fn($r) => str_starts_with($currentRoute, $r));
                @endphp
                <button class="nav-item"
                        aria-expanded="{{ $isParentOpen ? 'true' : 'false' }}"
                        onclick="toggleNav(this)">
                    {!! heroIcon($menu->icon ?? 'home') !!}
                    {{ $menu->label }}
                    @if($menu->key === 'docsign' && $pendingSign > 0)
                        <span class="nav-badge">{{ $pendingSign }}</span>
                    @endif
                    <svg class="nav-chevron" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
                <div class="nav-children {{ $isParentOpen ? 'open' : '' }}">
                    @foreach($menu->children->filter(fn($c) => in_array($c->key, $accessKeys)) as $child)
                        <a href="{{ $child->route_name && \Route::has($child->route_name) ? route($child->route_name) : '#' }}"
                           class="nav-child-item {{ $currentRoute === $child->route_name ? 'active' : '' }}">
                            {{ $child->label }}
                            @if($child->key === 'docsign.list' && $pendingSign > 0)
                                <span class="nav-badge">{{ $pendingSign }}</span>
                            @endif
                            @if($child->key === 'docsign.access_approval' && $pendingAccessRequests > 0)
                                <span class="nav-badge">{{ $pendingAccessRequests }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        <div class="user-info">
            <div class="user-name">{{ $user->name }}</div>
            <div class="user-role">{{ $primaryRole?->display_name ?? 'Member' }}</div>
        </div>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="logout-btn" title="Sign out">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
            </button>
        </form>
    </div>
</aside>

{{-- ══ MAIN ══ --}}
<div class="main-wrap">
    <header class="topbar">
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-actions">
            @yield('topbar-actions')
        </div>
    </header>

    <main class="page-content">
        @yield('content')
    </main>
</div>

{{-- Toast Notifications --}}
<div class="toasts" id="toasts">
    @if(session('success'))
        <div class="toast success">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="toast error">✕ {{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="toast info">ℹ {{ session('info') }}</div>
    @endif
</div>

<script>
function toggleNav(btn) {
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', !expanded);
    const children = btn.nextElementSibling;
    children.classList.toggle('open', !expanded);
}

// Auto-dismiss toasts
document.querySelectorAll('.toast').forEach(t => {
    setTimeout(() => t.remove(), 5000);
});
</script>

@stack('scripts')
</body>
</html>
