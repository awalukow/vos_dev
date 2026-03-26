@extends('portal.layouts.app')
@section('title', 'Access Approval')
@section('page-title', 'DocSign — Access Approval')

@push('styles')
<style>
.request-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.1rem 1.25rem;
    margin-bottom: .75rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}
.request-card.pending { border-left: 3px solid var(--accent); }

.user-avatar-sm {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: var(--accent-glow);
    border: 1.5px solid var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: .85rem;
    color: var(--accent);
    flex-shrink: 0;
}

.req-body { flex: 1; min-width: 0; }
.req-name { font-weight: 600; font-size: .9rem; margin-bottom: .15rem; }
.req-meta { font-size: .75rem; color: var(--text-muted); margin-bottom: .5rem; }
.req-message {
    font-size: .82rem;
    color: var(--text-muted);
    background: var(--surface2);
    border-radius: var(--radius-sm);
    padding: .5rem .75rem;
    margin-bottom: .75rem;
    font-style: italic;
    line-height: 1.5;
}

.req-actions { display: flex; gap: .5rem; align-items: flex-end; flex-direction: column; flex-shrink: 0; }

.inline-note {
    width: 180px;
    font-size: .78rem;
    padding: .35rem .6rem;
}

.status-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    margin-right: .35rem;
}
.dot-pending  { background: #fbbf24; }
.dot-approved { background: #4ade80; }
.dot-rejected { background: #fca5a5; }

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-muted);
    font-size: .9rem;
}
</style>
@endpush

@section('content')
@php $user = Auth::guard('portal')->user(); @endphp

@if($canApprove)
{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- ADMIN / PENGURUS VIEW                                           --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}

<div class="page-header">
    <div>
        <h1>Access Approval</h1>
        <p>Review upload access requests from portal users.</p>
    </div>
    @if($pending->count() > 0)
        <span class="badge badge-pending" style="font-size:.85rem;padding:.35rem .85rem;">
            {{ $pending->count() }} pending
        </span>
    @endif
</div>

{{-- Pending requests --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
        <span class="card-title">⏳ Pending Requests</span>
    </div>
    <div style="padding:1rem 1.25rem;">
        @forelse($pending as $req)
        <div class="request-card pending">
            <div class="user-avatar-sm">{{ strtoupper(substr($req->user->name, 0, 1)) }}</div>
            <div class="req-body">
                <div class="req-name">{{ $req->user->name }}</div>
                <div class="req-meta">
                    @{{ $req->user->username }} · {{ $req->user->email }}
                    · Requested {{ $req->created_at->diffForHumans() }}
                </div>
                @if($req->message)
                    <div class="req-message">"{{ $req->message }}"</div>
                @else
                    <div class="req-message" style="font-style:normal;color:var(--text-dim);">No message provided.</div>
                @endif
            </div>
            <div class="req-actions">
                {{-- Approve --}}
                <form method="POST" action="{{ route('portal.docsign.access-approval.approve', $req) }}"
                      style="display:flex;gap:.4rem;align-items:center;">
                    @csrf
                    <input type="text" name="note" class="form-control inline-note"
                           placeholder="Note (optional)…">
                    <button class="btn btn-primary btn-sm">✓ Approve</button>
                </form>
                {{-- Reject --}}
                <form method="POST" action="{{ route('portal.docsign.access-approval.reject', $req) }}"
                      style="display:flex;gap:.4rem;align-items:center;"
                      onsubmit="return validateReject(this)">
                    @csrf
                    <input type="text" name="note" class="form-control inline-note"
                           placeholder="Rejection reason (required)…">
                    <button class="btn btn-danger btn-sm">✕ Reject</button>
                </form>
            </div>
        </div>
        @empty
            <div class="empty-state">
                <div style="font-size:2rem;margin-bottom:.5rem;">🎉</div>
                No pending requests — all caught up!
            </div>
        @endforelse
    </div>
</div>

{{-- History --}}
<div class="card">
    <div class="card-header"><span class="card-title">Review History</span></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Reviewed By</th>
                    <th>Note</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $req)
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $req->user->name }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);">@{{ $req->user->username }}</div>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-muted);max-width:200px;">
                        {{ $req->message ? Str::limit($req->message, 60) : '—' }}
                    </td>
                    <td>
                        <span class="status-dot dot-{{ $req->status }}"></span>
                        <span style="font-size:.82rem;font-weight:600;text-transform:uppercase;">{{ $req->status }}</span>
                    </td>
                    <td style="font-size:.82rem;">{{ $req->reviewer?->name ?? '—' }}</td>
                    <td style="font-size:.82rem;color:var(--text-muted);">{{ $req->reviewer_note ?? '—' }}</td>
                    <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;">
                        {{ $req->reviewed_at?->format('d M Y') ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">No history yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($history) && $history->hasPages())
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);">
        {{ $history->links('portal.components.pagination') }}
    </div>
    @endif
</div>

@else
{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- REGULAR USER VIEW — own request history                        --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}

<div style="max-width:540px;margin:0 auto;">
    <div class="page-header">
        <div><h1>My Access Requests</h1><p>Your upload access request history.</p></div>
    </div>

    @forelse($myRequests as $req)
    <div class="request-card {{ $req->status === 'pending' ? 'pending' : '' }}" style="margin-bottom:.75rem;">
        <div class="req-body">
            <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.4rem;">
                <span class="status-dot dot-{{ $req->status }}"></span>
                <span style="font-weight:700;font-size:.85rem;text-transform:uppercase;">{{ $req->status }}</span>
                <span style="font-size:.75rem;color:var(--text-muted);">· {{ $req->created_at->format('d M Y, H:i') }}</span>
            </div>
            @if($req->message)
                <div class="req-message">"{{ $req->message }}"</div>
            @endif
            @if($req->reviewer_note)
                <div style="font-size:.82rem;color:var(--text-muted);">
                    <strong style="color:var(--text);">Reviewer note:</strong> {{ $req->reviewer_note }}
                </div>
            @endif
        </div>
    </div>
    @empty
        <div class="empty-state">No requests yet. Go to <a href="{{ route('portal.docsign.upload') }}" style="color:var(--accent);">Upload</a> to submit one.</div>
    @endforelse
</div>
@endif

@endsection

@push('scripts')
<script>
function validateReject(form) {
    const note = form.querySelector('input[name="note"]').value.trim();
    if (!note) {
        alert('Please enter a rejection reason before rejecting.');
        return false;
    }
    return true;
}
</script>
@endpush
