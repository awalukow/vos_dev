@extends('portal.layouts.app')
@section('title', 'Detail Kehadiran')
@section('page-title', 'Schedule — Detail Kehadiran')

@push('styles')
<style>
.voice-section {
    margin-bottom: 1.5rem;
}
.voice-header {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1.25rem;
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem;
}
.voice-body { border: 1px solid var(--border); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); }

.vh-sopran { background: rgba(236,72,153,.15); color: #f472b6; border: 1px solid rgba(236,72,153,.25); }
.vh-alto   { background: rgba(168,85,247,.12); color: #c084fc; border: 1px solid rgba(168,85,247,.2); }
.vh-tenor  { background: rgba(59,130,246,.12); color: #60a5fa; border: 1px solid rgba(59,130,246,.2); }
.vh-bass   { background: rgba(34,197,94,.1);   color: #4ade80; border: 1px solid rgba(34,197,94,.2); }

.member-row {
    display: flex; align-items: center; gap: .75rem;
    padding: .65rem 1.25rem;
    border-bottom: 1px solid var(--border);
    font-size: .875rem;
}
.member-row:last-child { border-bottom: none; }

.log-trail {
    font-size: .72rem;
    color: var(--text-muted);
    margin-top: .2rem;
    line-height: 1.5;
}
.log-entry {
    display: inline;
}
.log-entry + .log-entry::before { content: ' → '; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>{{ $schedule->event_name }}</h1>
        <p>{{ $schedule->program_date->format('d F Y, H:i') }} · {{ $schedule->event_type }}</p>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.schedule.laporan.edit', $schedule) }}" class="btn btn-primary btn-sm">Edit Kehadiran</a>
        <a href="{{ route('portal.schedule.laporan') }}" class="btn btn-secondary">← Laporan</a>
    </div>
</div>

@foreach($voices as $voice)
@php
    $vLower = strtolower($voice);
    $members = $attendances[$voice] ?? collect();
    $hadir   = $members->where('status', 'hadir')->count();
    $tidak   = $members->where('status', 'tidak_hadir')->count();
@endphp

<div class="voice-section">
    <div class="voice-header vh-{{ $vLower }}">
        <span>{{ $voice }}</span>
        <span style="font-size:.8rem;font-weight:400;opacity:.8;">
            {{ $hadir }} Hadir · {{ $tidak }} Tidak Hadir
        </span>
    </div>
    <div class="voice-body">
        @forelse($members->sortBy('status') as $attendance)
        <div class="member-row">
            <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                font-family:'Syne',sans-serif;font-weight:700;font-size:.8rem;flex-shrink:0;
                {{ $attendance->status === 'hadir' ? 'background:rgba(34,197,94,.1);color:#4ade80;border:1.5px solid rgba(34,197,94,.25);' : 'background:rgba(239,68,68,.08);color:#fca5a5;border:1.5px solid rgba(239,68,68,.2);' }}">
                {{ strtoupper(substr($attendance->user?->name ?? '?', 0, 1)) }}
            </div>
            <div style="flex:1;">
                <div style="font-weight:600;">{{ $attendance->user?->name ?? 'Unknown' }}</div>
                @php $logs = $attendance->logs; @endphp
                @if($logs->count() > 1)
                    <div class="log-trail">
                        Trail:
                        @foreach($logs->sortBy('changed_at') as $log)
                            <span class="log-entry"
                                  style="color: {{ $log->new_status === 'hadir' ? '#4ade80' : '#fca5a5' }}">
                                {{ $log->new_status === 'hadir' ? 'Hadir' : 'Tidak Hadir' }}
                                ({{ $log->changed_at->format('d M H:i') }}
                                @if($log->changedBy && $log->changed_by !== $attendance->portal_user_id)
                                    by {{ $log->changedBy->name }}
                                @endif)
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
            <span style="font-size:.78rem;font-weight:700;padding:.2rem .6rem;border-radius:20px;
                {{ $attendance->status === 'hadir' ? 'background:rgba(34,197,94,.12);color:#4ade80;' : 'background:rgba(239,68,68,.1);color:#fca5a5;' }}">
                {{ $attendance->status === 'hadir' ? 'Hadir' : 'Tidak Hadir' }}
            </span>
        </div>
        @empty
        <div style="padding:1.25rem;color:var(--text-muted);font-size:.875rem;text-align:center;">
            No confirmations yet for {{ $voice }}.
        </div>
        @endforelse
    </div>
</div>
@endforeach

@endsection
