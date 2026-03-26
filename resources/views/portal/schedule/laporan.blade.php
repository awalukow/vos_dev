@extends('portal.layouts.app')
@section('title', 'Laporan Kehadiran')
@section('page-title', 'Schedule — Laporan Kehadiran')

@push('styles')
<style>
.voice-pill {
    display: inline-flex; flex-direction: column; align-items: center;
    min-width: 56px;
    padding: .3rem .5rem;
    border-radius: var(--radius-sm);
    font-size: .72rem;
}
.voice-count { font-size: 1.1rem; font-weight: 800; font-family: 'Syne', sans-serif; line-height: 1; }
.voice-label { font-size: .65rem; font-weight: 500; margin-top: .2rem; opacity: .7; text-transform: uppercase; letter-spacing: .05em; }

.vp-sopran { background: rgba(236,72,153,.1);  color: #f472b6; }
.vp-alto   { background: rgba(168,85,247,.1);  color: #c084fc; }
.vp-tenor  { background: rgba(59,130,246,.1);  color: #60a5fa; }
.vp-bass   { background: rgba(34,197,94,.1);   color: #4ade80; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Laporan Kehadiran</h1>
        <p>Attendance report per schedule per voice section.</p>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kegiatan</th>
                    <th style="text-align:center;">Sopran</th>
                    <th style="text-align:center;">Alto</th>
                    <th style="text-align:center;">Tenor</th>
                    <th style="text-align:center;">Bass</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                <tr>
                    <td style="white-space:nowrap;">
                        <div style="font-weight:600;">{{ $schedule->program_date->format('d M Y') }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);">{{ $schedule->program_date->format('H:i') }}</div>
                    </td>
                    <td>
                        <div style="font-weight:600;">{{ $schedule->event_name }}</div>
                        <span style="font-size:.72rem;padding:.15rem .45rem;border-radius:4px;
                            {{ $schedule->event_type === 'Pelayanan' ? 'background:rgba(59,130,246,.1);color:#60a5fa;' : 'background:rgba(168,85,247,.1);color:#c084fc;' }}">
                            {{ $schedule->event_type }}
                        </span>
                    </td>
                    @foreach(['Sopran','Alto','Tenor','Bass'] as $voice)
                    @php
                        $vLower = strtolower($voice);
                        $hadir  = $counts[$schedule->id][$voice]['hadir'] ?? 0;
                        $tidak  = $counts[$schedule->id][$voice]['tidak_hadir'] ?? 0;
                    @endphp
                    <td style="text-align:center;">
                        <div class="voice-pill vp-{{ $vLower }}">
                            <span class="voice-count">{{ $hadir }}</span>
                            <span class="voice-label">Hadir</span>
                        </div>
                        @if($tidak > 0)
                        <div style="font-size:.7rem;color:var(--text-muted);margin-top:.2rem;">{{ $tidak }} tidak hadir</div>
                        @endif
                    </td>
                    @endforeach
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <a href="{{ route('portal.schedule.laporan.detail', $schedule) }}"
                               class="btn btn-secondary btn-sm">Detail</a>
                            <a href="{{ route('portal.schedule.laporan.edit', $schedule) }}"
                               class="btn btn-secondary btn-sm">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem;">
                        No schedules found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
