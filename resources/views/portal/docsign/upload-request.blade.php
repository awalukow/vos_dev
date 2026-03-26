@extends('portal.layouts.app')
@section('title', 'Request Upload Access')
@section('page-title', 'DocSign — Upload Access')

@push('styles')
<style>
.request-wrap {
    max-width: 520px;
    margin: 3rem auto;
}
.lock-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: rgba(200,169,110,.1);
    border: 1.5px solid rgba(200,169,110,.25);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 1.8rem;
}
.status-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .85rem;
    border-radius: 20px;
    font-size: .8rem;
    font-weight: 600;
}
.status-pending  { background: rgba(245,158,11,.12); color: #fbbf24; border: 1px solid rgba(245,158,11,.25); }
.status-approved { background: rgba(34,197,94,.1);   color: #4ade80; border: 1px solid rgba(34,197,94,.2); }
.status-rejected { background: rgba(239,68,68,.1);   color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }
</style>
@endpush

@section('content')

<div class="request-wrap">

    {{-- Header --}}
    <div style="text-align:center;margin-bottom:2rem;">
        <div class="lock-icon">🔒</div>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:.5rem;">Upload Access Required</h1>
        <p style="color:var(--text-muted);font-size:.9rem;line-height:1.6;">
            You need approval to upload documents to DocSign.<br>
            Submit a request below and an administrator will review it.
        </p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#4ade80;padding:.85rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:.875rem;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#fbbf24;padding:.85rem 1rem;border-radius:8px;margin-bottom:1.5rem;font-size:.875rem;">
            ℹ {{ session('info') }}
        </div>
    @endif

    {{-- Already has a pending request --}}
    @if($pendingRequest)
        <div class="card">
            <div class="card-body" style="text-align:center;padding:2rem;">
                <div style="margin-bottom:1rem;">
                    <span class="status-badge status-pending">⏳ Pending Review</span>
                </div>
                <p style="color:var(--text-muted);font-size:.9rem;line-height:1.7;margin-bottom:.5rem;">
                    Your request was submitted on <strong style="color:var(--text);">{{ $pendingRequest->created_at->format('d M Y, H:i') }}</strong>.
                </p>
                <p style="color:var(--text-muted);font-size:.875rem;">
                    An administrator or Pengurus will approve or reject it shortly. You'll see the result here.
                </p>
                @if($pendingRequest->message)
                    <div style="margin-top:1rem;padding:.75rem 1rem;background:var(--surface2);border-radius:var(--radius-sm);font-size:.82rem;color:var(--text-muted);text-align:left;">
                        <strong style="color:var(--text);">Your message:</strong><br>
                        {{ $pendingRequest->message }}
                    </div>
                @endif
            </div>
        </div>

    {{-- Previous request was rejected — allow re-request --}}
    @elseif($lastRequest && $lastRequest->status === 'rejected')
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-body" style="padding:1.25rem;">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
                    <span class="status-badge status-rejected">✕ Previously Rejected</span>
                    <span style="font-size:.78rem;color:var(--text-muted);">{{ $lastRequest->reviewed_at?->format('d M Y') }}</span>
                </div>
                @if($lastRequest->reviewer_note)
                    <div style="font-size:.82rem;color:var(--text-muted);">
                        <strong style="color:var(--text);">Reason:</strong> {{ $lastRequest->reviewer_note }}
                    </div>
                @endif
            </div>
        </div>

        @include('portal.docsign._request-form')

    {{-- No previous request --}}
    @else
        @include('portal.docsign._request-form')
    @endif

</div>

@endsection
