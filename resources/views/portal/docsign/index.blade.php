@extends('portal.layouts.app')

@section('title', 'Document List')
@section('page-title', 'DocSign — Document List')

@push('styles')
<style>
.pending-flash {
    animation: flashRow 1.8s ease-in-out infinite;
}
@keyframes flashRow {
    0%,100% { background: rgba(200,169,110,.07); }
    50%      { background: rgba(200,169,110,.16); }
}

.pending-indicator {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .75rem;
    font-weight: 600;
    color: var(--accent);
    padding: .2rem .55rem;
    background: rgba(200,169,110,.12);
    border-radius: 20px;
    animation: pulseText 1.5s ease-in-out infinite;
}
@keyframes pulseText {
    0%,100% { opacity: 1; }
    50%      { opacity: .6; }
}

.pending-indicator::before {
    content: '';
    width: 7px; height: 7px;
    background: var(--accent);
    border-radius: 50%;
    animation: pulseDot 1.5s ease-in-out infinite;
}
@keyframes pulseDot {
    0%,100% { transform: scale(1); opacity:1; }
    50%      { transform: scale(1.4); opacity:.6; }
}

.signers-list { display: flex; flex-wrap: wrap; gap: .3rem; }
.signer-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .15rem .5rem;
    border-radius: 20px;
    font-size: .72rem;
    background: var(--surface2);
    border: 1px solid var(--border2);
    color: var(--text-muted);
}
.signer-chip.done { border-color: rgba(34,197,94,.2); color: #4ade80; }
.signer-chip.mine { border-color: rgba(200,169,110,.35); color: var(--accent); }
</style>
@endpush

@section('content')

@php $user = Auth::guard('portal')->user(); @endphp

<div class="page-header">
    <div>
        <h1>Document List</h1>
        <p>Manage and track all documents in the system.</p>
    </div>
    @if($user->can_upload_documents || $user->isAdministrator())
    <a href="{{ route('portal.docsign.upload') }}" class="btn btn-primary">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
        </svg>
        Upload Document
    </a>
    @endif
</div>

{{-- Pending for ME --}}
@if($pendingMySignature->count() > 0)
<div class="card" style="margin-bottom:1.5rem; border-color:rgba(200,169,110,.3);">
    <div class="card-header">
        <span class="card-title" style="color:var(--accent);">⚡ Requires Your Signature ({{ $pendingMySignature->count() }})</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th><th>Uploaded By</th><th>Assigned</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingMySignature as $sig)
                <tr class="pending-flash">
                    <td>
                        <div style="font-weight:600;">{{ $sig->document->title }}</div>
                        <div style="font-size:.78rem;color:var(--text-muted);">{{ $sig->document->original_filename }}</div>
                        <span class="pending-indicator">AWAITING SIGNATURE</span>
                    </td>
                    <td>{{ $sig->document->uploader->name ?? '—' }}</td>
                    <td style="color:var(--text-muted);font-size:.82rem;">{{ $sig->created_at->diffForHumans() }}</td>
                    <td style="display:flex;gap:.5rem;align-items:center;">
                        <a href="{{ route('portal.docsign.sign', [$sig->document, $sig]) }}" class="btn btn-primary btn-sm">Sign Now</a>
                        <a href="{{ route('portal.docsign.show', $sig->document) }}" class="btn btn-secondary btn-sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- All documents --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">All Documents</span>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <input type="text" id="docSearch" placeholder="Search documents…"
                   class="form-control" style="width:220px;padding:.4rem .8rem;font-size:.82rem;">
        </div>
    </div>
    <div class="table-wrap">
        <table id="docTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Uploaded By</th>
                    <th>Status</th>
                    <th>Signers</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allDocuments as $doc)
                @php
                    $isPendingForMe = $doc->isPendingForUser($user);
                @endphp
                <tr class="{{ $isPendingForMe ? 'pending-row' : '' }}" data-search="{{ strtolower($doc->title . ' ' . $doc->uploader?->name) }}">
                    <td style="color:var(--text-muted);font-size:.78rem;">{{ $doc->id }}</td>
                    <td>
                        <div style="font-weight:600;">{{ $doc->title }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);">{{ $doc->original_filename }}</div>
                    </td>
                    <td>{{ $doc->uploader?->name ?? '—' }}</td>
                    <td>
                        @php
                            $statusMap = [
                                'draft'            => ['badge-draft',    'Draft'],
                                'pending'          => ['badge-pending',  'Pending'],
                                'partially_signed' => ['badge-partial',  'Partially Signed'],
                                'completed'        => ['badge-completed','Completed'],
                                'rejected'         => ['badge-rejected', 'Rejected'],
                            ];
                            [$cls, $lbl] = $statusMap[$doc->status] ?? ['badge-draft', $doc->status];
                        @endphp
                        <span class="badge {{ $cls }}">{{ $lbl }}</span>
                        @if($isPendingForMe)
                            <span class="pending-indicator" style="margin-top:.3rem;display:flex;">MY TURN</span>
                        @endif
                    </td>
                    <td>
                        <div class="signers-list">
                            @forelse($doc->signatures as $sig)
                                <span class="signer-chip {{ $sig->status === 'signed' ? 'done' : ($sig->signer_id === $user->id ? 'mine' : '') }}">
                                    @if($sig->status === 'signed') ✓ @endif
                                    {{ $sig->signer?->name ?? '?' }}
                                </span>
                            @empty
                                <span style="color:var(--text-muted);font-size:.78rem;">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap;">{{ $doc->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <a href="{{ route('portal.docsign.show', $doc) }}" class="btn btn-secondary btn-sm">View</a>
                            @if($isPendingForMe)
                                @php $mySig = $doc->signatures->firstWhere('signer_id', $user->id); @endphp
                                <a href="{{ route('portal.docsign.sign', [$doc, $mySig]) }}" class="btn btn-primary btn-sm">Sign</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem;">
                        No documents found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($allDocuments->hasPages())
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);">
        {{ $allDocuments->links('portal.components.pagination') }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
document.getElementById('docSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#docTable tbody tr').forEach(row => {
        const text = row.dataset.search || '';
        row.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>
@endpush
