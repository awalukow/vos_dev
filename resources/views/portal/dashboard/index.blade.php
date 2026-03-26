@extends('portal.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    transition: border-color .2s;
}

.stat-card:hover { border-color: var(--border2); }

.stat-card.highlight {
    border-color: rgba(200,169,110,.3);
    background: rgba(200,169,110,.04);
}

.stat-value {
    font-family: 'Syne', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
    color: var(--text);
}

.stat-card.highlight .stat-value { color: var(--accent); }

.stat-label {
    font-size: .78rem;
    color: var(--text-muted);
    margin-top: .3rem;
    font-weight: 500;
}

.stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.stat-icon.gold { background: rgba(200,169,110,.12); color: var(--accent); }
.stat-icon.blue { background: rgba(59,130,246,.12); color: #60a5fa; }
.stat-icon.green { background: rgba(34,197,94,.12); color: #4ade80; }
.stat-icon.red { background: rgba(239,68,68,.12); color: #fca5a5; }

.pending-sign-table td { padding: .7rem 1rem; }

.welcome-banner {
    background: linear-gradient(135deg, rgba(200,169,110,.08) 0%, rgba(200,169,110,.02) 100%);
    border: 1px solid rgba(200,169,110,.15);
    border-radius: var(--radius);
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.welcome-text h2 {
    font-family: 'Syne', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: .25rem;
}

.welcome-text p { color: var(--text-muted); font-size: .875rem; }

.time-display {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent);
    text-align: right;
}

.time-display small { display: block; font-family: 'Inter', sans-serif; font-weight: 400; font-size: .75rem; color: var(--text-muted); }
</style>
@endpush

@section('content')
@php $user = Auth::guard('portal')->user(); @endphp

<div class="welcome-banner">
    <div class="welcome-text">
        <h2>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', $user->name)[0] }} 👋</h2>
        <p>Here's an overview of your portal activity today.</p>
    </div>
    <div class="time-display">
        {{ now()->format('H:i') }}
        <small>{{ now()->format('l, d F Y') }}</small>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card {{ $stats['pending_docs'] > 0 ? 'highlight' : '' }}">
        <div>
            <div class="stat-value">{{ $stats['pending_docs'] }}</div>
            <div class="stat-label">Awaiting My Signature</div>
        </div>
        <div class="stat-icon gold">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
            </svg>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-value">{{ $stats['total_docs'] }}</div>
            <div class="stat-label">My Uploaded Documents</div>
        </div>
        <div class="stat-icon blue">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        </div>
    </div>

    @if($user->isAdministrator() || $user->isAdm2())
    <div class="stat-card">
        <div>
            <div class="stat-value">{{ $stats['total_users'] }}</div>
            <div class="stat-label">Total Portal Users</div>
        </div>
        <div class="stat-icon green">
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </div>
    </div>
    @endif
</div>

{{-- Pending signatures table --}}
@if($stats['docs_to_sign']->count() > 0)
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <span class="card-title">⚡ Documents Awaiting Your Signature</span>
        <a href="{{ route('portal.docsign.index') }}" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Uploaded By</th>
                    <th>Assigned</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['docs_to_sign'] as $sig)
                <tr class="pending-row">
                    <td>
                        <div style="font-weight:600;">{{ $sig->document->title }}</div>
                        <div style="font-size:.78rem;color:var(--text-muted);">{{ $sig->document->original_filename }}</div>
                    </td>
                    <td>{{ $sig->document->uploader->name ?? '—' }}</td>
                    <td style="color:var(--text-muted);font-size:.82rem;">{{ $sig->created_at->diffForHumans() }}</td>
                    <td>
                        <a href="{{ route('portal.docsign.sign', [$sig->document, $sig]) }}"
                           class="btn btn-primary btn-sm">Sign Now</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
