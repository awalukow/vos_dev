@extends('portal.layouts.app')
@section('title', 'Jadwal Pelayanan')
@section('page-title', 'Schedule — Jadwal Pelayanan')

@push('styles')
<style>
.voice-badge {
    display: inline-flex; align-items: center;
    padding: .18rem .55rem;
    border-radius: 20px;
    font-size: .7rem; font-weight: 700;
    letter-spacing: .04em;
}
.voice-sopran { background: rgba(236,72,153,.12); color: #f472b6; }
.voice-alto   { background: rgba(168,85,247,.12);  color: #c084fc; }
.voice-tenor  { background: rgba(59,130,246,.12);  color: #60a5fa; }
.voice-bass   { background: rgba(34,197,94,.12);   color: #4ade80; }

.confirm-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .35rem .85rem;
    border-radius: 20px;
    font-size: .78rem; font-weight: 600;
    border: none; cursor: pointer;
    transition: all .15s;
}
.confirm-hadir {
    background: rgba(34,197,94,.15);
    color: #4ade80;
    border: 1px solid rgba(34,197,94,.3);
}
.confirm-hadir:hover { background: rgba(34,197,94,.28); }
.confirm-tidak {
    background: rgba(239,68,68,.12);
    color: #fca5a5;
    border: 1px solid rgba(239,68,68,.25);
}
.confirm-tidak:hover { background: rgba(239,68,68,.22); }
.confirm-none {
    background: var(--surface2);
    color: var(--text-muted);
    border: 1px solid var(--border2);
}
.confirm-none:hover { border-color: var(--accent); color: var(--accent); }

.event-type-pelayanan { background: rgba(59,130,246,.1);  color: #60a5fa;  border: 1px solid rgba(59,130,246,.2); }
.event-type-konser    { background: rgba(168,85,247,.1);   color: #c084fc;  border: 1px solid rgba(168,85,247,.2); }

.maps-link {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .78rem; color: var(--accent);
    text-decoration: none;
}
.maps-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Jadwal Pelayanan</h1>
        <p>Upcoming schedules and service confirmations.</p>
    </div>
    @if($canManage)
    <a href="{{ route('portal.schedule.create') }}" class="btn btn-primary">+ Add Schedule</a>
    @endif
</div>

@if($schedules->isEmpty())
    <div class="card">
        <div style="text-align:center;padding:4rem 2rem;color:var(--text-muted);">
            <div style="font-size:2.5rem;margin-bottom:1rem;">📅</div>
            <div style="font-size:1rem;font-weight:600;margin-bottom:.4rem;">No upcoming schedules</div>
            <div style="font-size:.875rem;">Check back later or ask an admin to add schedules.</div>
        </div>
    </div>
@else
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tipe Kegiatan</th>
                    <th>Tanggal</th>
                    <th>Lokasi</th>
                    <th>Jadwal</th>
                    <th>Konfirmasi Kehadiran</th>
                    @if($canManage)<th>Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($schedules as $schedule)
                @php $myStatus = $attendanceMap[$schedule->id] ?? null; @endphp
                <tr>
                    <td>
                        <span class="voice-badge {{ $schedule->event_type === 'Pelayanan' ? 'event-type-pelayanan' : 'event-type-konser' }}" style="border-radius:6px;">
                            {{ $schedule->event_type }}
                        </span>
                        <div style="font-weight:600;margin-top:.35rem;font-size:.875rem;">{{ $schedule->event_name }}</div>
                        @if($schedule->event_detail)
                            <div style="font-size:.75rem;color:var(--text-muted);margin-top:.15rem;">{{ Str::limit($schedule->event_detail, 50) }}</div>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        <div style="font-weight:600;">{{ $schedule->program_date->format('d M Y') }}</div>
                        @if($schedule->isSundayService)
                            <span style="font-size:.7rem;color:var(--accent);">Kebaktian Minggu</span>
                        @endif
                    </td>
                    <td>
                        @if($schedule->location)
                            <div style="font-size:.875rem;">{{ $schedule->location }}</div>
                        @endif
                        @if($schedule->maps_url)
                            <a href="{{ $schedule->maps_url }}" target="_blank" class="maps-link">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                </svg>
                                Google Maps
                            </a>
                        @else
                            <span style="color:var(--text-muted);font-size:.78rem;">—</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;font-size:.82rem;color:var(--text-muted);">
                        {{ $schedule->program_date->format('H:i') }}
                        @if($schedule->program_until)
                            – {{ $schedule->program_until->format('H:i') }}
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                            {{-- Hadir button --}}
                            <form method="POST" action="{{ route('portal.schedule.confirm', $schedule) }}">
                                @csrf
                                <input type="hidden" name="status" value="hadir">
                                <button type="submit"
                                    class="confirm-btn {{ $myStatus === 'hadir' ? 'confirm-hadir' : 'confirm-none' }}">
                                    @if($myStatus === 'hadir') ✓ @endif Hadir
                                </button>
                            </form>
                            {{-- Tidak Hadir button --}}
                            <form method="POST" action="{{ route('portal.schedule.confirm', $schedule) }}">
                                @csrf
                                <input type="hidden" name="status" value="tidak_hadir">
                                <button type="submit"
                                    class="confirm-btn {{ $myStatus === 'tidak_hadir' ? 'confirm-tidak' : 'confirm-none' }}">
                                    @if($myStatus === 'tidak_hadir') ✕ @endif Tidak Hadir
                                </button>
                            </form>
                        </div>
                    </td>
                    @if($canManage)
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <a href="{{ route('portal.schedule.edit', $schedule) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" action="{{ route('portal.schedule.destroy', $schedule) }}"
                                  onsubmit="return confirm('Remove this schedule?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Del</button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
