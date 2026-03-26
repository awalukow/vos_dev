@extends('portal.layouts.app')
@section('title', 'Edit Kehadiran')
@section('page-title', 'Schedule — Edit Kehadiran')

@push('styles')
<style>
.voice-group {
    margin-bottom: 1.5rem;
}
.voice-group-header {
    padding: .65rem 1.25rem;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: .9rem;
    border-radius: var(--radius-sm) var(--radius-sm) 0 0;
    display: flex;
    align-items: center;
    gap: .6rem;
}
.vgh-sopran { background: rgba(236,72,153,.12); color: #f472b6; border: 1px solid rgba(236,72,153,.2); }
.vgh-alto   { background: rgba(168,85,247,.1);  color: #c084fc; border: 1px solid rgba(168,85,247,.18); }
.vgh-tenor  { background: rgba(59,130,246,.1);  color: #60a5fa; border: 1px solid rgba(59,130,246,.18); }
.vgh-bass   { background: rgba(34,197,94,.08);  color: #4ade80; border: 1px solid rgba(34,197,94,.18); }

.voice-group-body {
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 var(--radius-sm) var(--radius-sm);
    overflow: hidden;
}

.member-edit-row {
    display: grid;
    grid-template-columns: 1fr 220px 1fr;
    gap: 1rem;
    align-items: center;
    padding: .75rem 1.25rem;
    border-bottom: 1px solid var(--border);
}
.member-edit-row:last-child { border-bottom: none; }
.member-edit-row:hover { background: rgba(255,255,255,.02); }

.status-toggle {
    display: flex;
    border: 1px solid var(--border2);
    border-radius: var(--radius-sm);
    overflow: hidden;
}
.status-toggle label {
    flex: 1;
    text-align: center;
    padding: .4rem .6rem;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    margin: 0;
    text-transform: none;
    letter-spacing: 0;
    color: var(--text-muted);
}
.status-toggle input[type=radio] { display: none; }
.status-toggle input[type=radio]:checked + label.hadir-label {
    background: rgba(34,197,94,.2);
    color: #4ade80;
}
.status-toggle input[type=radio]:checked + label.tidak-label {
    background: rgba(239,68,68,.15);
    color: #fca5a5;
}
.status-toggle .divider { width: 1px; background: var(--border2); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Edit Kehadiran</h1>
        <p>{{ $schedule->event_name }} · {{ $schedule->program_date->format('d F Y') }}</p>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.schedule.laporan.detail', $schedule) }}" class="btn btn-secondary">← Detail</a>
    </div>
</div>

<form method="POST" action="{{ route('portal.schedule.laporan.update', $schedule) }}">
@csrf

@foreach($voices as $voice)
@php
    $vLower  = strtolower($voice);
    $members = $portalUsers->filter(fn($u) => $u->voice === $voice);
@endphp

@if($members->count() > 0)
<div class="voice-group">
    <div class="voice-group-header vgh-{{ $vLower }}">
        {{ $voice }}
        <span style="font-size:.75rem;font-weight:400;opacity:.75;">{{ $members->count() }} member{{ $members->count() !== 1 ? 's' : '' }}</span>
    </div>
    <div class="voice-group-body">
        @foreach($members as $member)
        @php $currentStatus = $attendanceMap[$member->id] ?? null; @endphp
        <div class="member-edit-row">
            {{-- Member name --}}
            <div style="display:flex;align-items:center;gap:.65rem;">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-glow);border:1.5px solid var(--accent);
                    display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.8rem;color:var(--accent);flex-shrink:0;">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-weight:600;font-size:.875rem;">{{ $member->name }}</div>
                    <div style="font-size:.72rem;color:var(--text-muted);">@{{ $member->username }}</div>
                </div>
            </div>

            {{-- Status toggle --}}
            <div class="status-toggle">
                <input type="radio" name="attendances[{{ $member->id }}][status]"
                       id="hadir_{{ $member->id }}" value="hadir"
                       {{ ($currentStatus ?? 'hadir') === 'hadir' ? 'checked' : '' }}>
                <label for="hadir_{{ $member->id }}" class="hadir-label">✓ Hadir</label>
                <div class="divider"></div>
                <input type="radio" name="attendances[{{ $member->id }}][status]"
                       id="tidak_{{ $member->id }}" value="tidak_hadir"
                       {{ $currentStatus === 'tidak_hadir' ? 'checked' : '' }}>
                <label for="tidak_{{ $member->id }}" class="tidak-label">✕ Tidak Hadir</label>
            </div>

            {{-- Change note --}}
            <input type="text"
                   name="notes[{{ $member->id }}]"
                   class="form-control"
                   placeholder="Catatan perubahan (opsional)…"
                   style="font-size:.78rem;padding:.4rem .7rem;">
        </div>
        @endforeach
    </div>
</div>
@endif
@endforeach

{{-- Members with no voice assigned --}}
@php $noVoice = $portalUsers->filter(fn($u) => is_null($u->voice)); @endphp
@if($noVoice->count() > 0)
<div class="voice-group">
    <div class="voice-group-header" style="background:var(--surface2);color:var(--text-muted);border:1px solid var(--border);">
        Belum Ada Suara
        <span style="font-size:.75rem;font-weight:400;">{{ $noVoice->count() }} member</span>
    </div>
    <div class="voice-group-body">
        @foreach($noVoice as $member)
        @php $currentStatus = $attendanceMap[$member->id] ?? null; @endphp
        <div class="member-edit-row">
            <div style="display:flex;align-items:center;gap:.65rem;">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--surface2);border:1.5px solid var(--border2);
                    display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.8rem;color:var(--text-muted);flex-shrink:0;">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-weight:600;font-size:.875rem;">{{ $member->name }}</div>
                    <div style="font-size:.72rem;color:var(--text-muted);">@{{ $member->username }} · <span style="color:#fbbf24;">No voice assigned</span></div>
                </div>
            </div>
            <div class="status-toggle">
                <input type="radio" name="attendances[{{ $member->id }}][status]"
                       id="hadir_{{ $member->id }}" value="hadir"
                       {{ ($currentStatus ?? 'hadir') === 'hadir' ? 'checked' : '' }}>
                <label for="hadir_{{ $member->id }}" class="hadir-label">✓ Hadir</label>
                <div class="divider"></div>
                <input type="radio" name="attendances[{{ $member->id }}][status]"
                       id="tidak_{{ $member->id }}" value="tidak_hadir"
                       {{ $currentStatus === 'tidak_hadir' ? 'checked' : '' }}>
                <label for="tidak_{{ $member->id }}" class="tidak-label">✕ Tidak Hadir</label>
            </div>
            <input type="text" name="notes[{{ $member->id }}]"
                   class="form-control" placeholder="Catatan…"
                   style="font-size:.78rem;padding:.4rem .7rem;">
        </div>
        @endforeach
    </div>
</div>
@endif

<div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem;">
    <a href="{{ route('portal.schedule.laporan.detail', $schedule) }}" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary">Save Attendance →</button>
</div>

</form>

@endsection
