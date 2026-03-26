@extends('portal.layouts.app')
@section('title', $document->title)
@section('page-title', 'DocSign — Document Detail')

@push('styles')
<style>
.detail-layout { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; align-items: start; }
.signature-step {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    background: var(--surface2);
    margin-bottom: .5rem;
}
.step-num {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .78rem; font-weight: 700;
    flex-shrink: 0;
}
.step-num.pending  { background: rgba(245,158,11,.15); color: #fbbf24; border: 1.5px solid rgba(245,158,11,.3); }
.step-num.signed   { background: rgba(34,197,94,.12);  color: #4ade80; border: 1.5px solid rgba(34,197,94,.25); }
.step-num.rejected { background: rgba(239,68,68,.12);  color: #fca5a5; border: 1.5px solid rgba(239,68,68,.25); }
.step-info { flex: 1; }
.step-name { font-weight: 600; font-size: .875rem; }
.step-meta { font-size: .75rem; color: var(--text-muted); }

/* Signer search inline */
.inline-assign { display: flex; gap: .5rem; margin-top:.75rem; }

.pdf-mini { width:100%; border-radius:var(--radius-sm); border:1px solid var(--border); height:400px; background:#1a1a1f; }
</style>
@endpush

@section('content')
@php $user = Auth::guard('portal')->user(); @endphp

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
    <a href="{{ route('portal.docsign.index') }}" class="btn btn-secondary">← Back</a>
    <div>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;">{{ $document->title }}</h1>
        <p style="color:var(--text-muted);font-size:.82rem;">{{ $document->original_filename }}</p>
    </div>
    @php $statusMap = ['draft'=>['badge-draft','Draft'],'pending'=>['badge-pending','Pending'],'partially_signed'=>['badge-partial','Partially Signed'],'completed'=>['badge-completed','Completed'],'rejected'=>['badge-rejected','Rejected']]; [$cls,$lbl] = $statusMap[$document->status]??['badge-draft',$document->status]; @endphp
    <span class="badge {{ $cls }}" style="font-size:.85rem;padding:.3rem .8rem;">{{ $lbl }}</span>
</div>

<div class="detail-layout">

    {{-- Left: PDF preview --}}
    <div>
        <div class="card">
            @php
                // Show the latest signed version if one exists, otherwise the original
                $latestSig = $document->signatures
                    ->where('status', 'signed')
                    ->whereNotNull('signed_file_path')
                    ->sortByDesc('sort_order')
                    ->first();
                $previewUrl = $latestSig
                    ? route('portal.docsign.pdf', $document) . '?signature=' . $latestSig->id
                    : route('portal.docsign.pdf', $document);
            @endphp
            <div class="card-header">
                <span class="card-title">
                    Document Preview
                    @if($latestSig)
                        <span style="font-size:.72rem;font-weight:400;color:var(--accent);margin-left:.5rem;">
                            ✓ Showing signed version
                        </span>
                    @endif
                </span>
                <a href="{{ $previewUrl }}" target="_blank" class="btn btn-secondary btn-sm">Open PDF ↗</a>
            </div>
            <div style="padding:0;">
                <iframe src="{{ $previewUrl }}" class="pdf-mini" style="display:block;"></iframe>
            </div>
        </div>

        {{-- Document info --}}
        <div class="card" style="margin-top:1rem;">
            <div class="card-header"><span class="card-title">Details</span></div>
            <div class="card-body" style="padding:1rem 1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:.6rem 1.5rem;">
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;">Uploaded By</div>
                    <div style="font-weight:600;margin-top:.2rem;">{{ $document->uploader->name }}</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;">Upload Date</div>
                    <div style="font-weight:600;margin-top:.2rem;">{{ $document->created_at->format('d M Y, H:i') }}</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;">Verify URL</div>
                    <div style="font-size:.78rem;margin-top:.2rem;word-break:break-all;color:var(--accent);">{{ $document->verify_url }}</div>
                </div>
                @if($document->notes)
                <div style="grid-column:1/-1;">
                    <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;">Notes</div>
                    <div style="margin-top:.3rem;font-size:.875rem;color:var(--text-muted);">{{ $document->notes }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: Signers & actions --}}
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Signature Chain</span></div>
            <div class="card-body">
                @forelse($document->signatures as $sig)
                <div class="signature-step">
                    <div class="step-num {{ $sig->status }}">
                        @if($sig->status === 'signed') ✓
                        @elseif($sig->status === 'rejected') ✕
                        @else {{ $loop->iteration }}
                        @endif
                    </div>
                    <div class="step-info">
                        <div class="step-name">{{ $sig->signer?->name ?? 'Unknown' }}</div>
                        <div class="step-meta">
                            @if($sig->status === 'signed')
                                Signed {{ $sig->signed_at?->format('d M Y H:i') }}
                            @elseif($sig->status === 'rejected')
                                Rejected: {{ Str::limit($sig->rejection_reason, 40) }}
                            @else
                                Pending signature
                            @endif
                        </div>
                    </div>
                    @if($sig->status === 'pending' && $sig->signer_id === $user->id)
                        <a href="{{ route('portal.docsign.sign', [$document, $sig]) }}" class="btn btn-primary btn-sm">Sign</a>
                    @endif
                    @if($sig->status === 'signed' && $sig->signed_file_path)
                        <a href="{{ route('portal.docsign.pdf', $document) }}?signature={{ $sig->id }}" target="_blank" class="btn btn-secondary btn-sm" title="View signed version">PDF</a>
                    @endif
                </div>
                @empty
                    <div style="color:var(--text-muted);font-size:.875rem;text-align:center;padding:1rem 0;">No signers assigned yet.</div>
                @endforelse

                {{-- Add signer (uploader or admin) --}}
                @if($document->uploaded_by === $user->id || $user->isAdministrator())
                @if($document->status !== 'completed' && $document->status !== 'rejected')
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
                    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.5rem;">Add another signer:</div>
                    <div class="inline-assign">
                        <input type="text" id="addSignerInput" class="form-control" placeholder="Search user…" autocomplete="off" style="font-size:.83rem;">
                        <button onclick="assignSigner()" class="btn btn-secondary btn-sm" style="white-space:nowrap;">Add</button>
                    </div>
                    <div id="addAutocomplete" style="position:relative;">
                        <div id="addAutoList" style="position:absolute;top:0;left:0;right:0;background:var(--surface2);border:1px solid var(--border2);border-radius:var(--radius-sm);z-index:100;display:none;max-height:160px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.4);"></div>
                    </div>
                    <input type="hidden" id="addSignerId" value="">
                </div>
                @endif
                @endif
            </div>
        </div>

        {{-- Verification Status Card --}}
        @php
            $verification = \App\Models\DocumentVerification::where('hash_code', $document->hash_code)->first();
        @endphp
        <div class="card" style="margin-top:1rem;">
            <div class="card-header"><span class="card-title">Verification Status</span></div>
            <div class="card-body" style="padding:1rem 1.25rem;">
                @if($verification)
                    @php
                        $vStatus = $verification->validation_status;
                        $isValid = $vStatus === 'VALID';
                    @endphp

                    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;">
                        <span style="font-size:1.1rem;">{{ $isValid ? '✓' : '✕' }}</span>
                        <span style="font-weight:700;font-size:.95rem;color:{{ $isValid ? '#4ade80' : '#fca5a5' }};">
                            {{ $vStatus }}
                        </span>
                    </div>

                    <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.3rem;">Signed By</div>
                    <div style="font-size:.875rem;font-weight:600;margin-bottom:.75rem;">{{ $verification->signatureBy }}</div>

                    <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.3rem;">Valid Thru</div>
                    <div style="font-size:.875rem;font-weight:600;margin-bottom:.75rem;">
                        {{ $verification->signature_validThru ? $verification->signature_validThru->format('d F Y') : 'No expiry' }}
                    </div>

                    @if(!$isValid)
                    <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.3rem;">Reason</div>
                    <div style="font-size:.875rem;color:#fca5a5;margin-bottom:.75rem;">{{ $verification->invalid_reason }}</div>
                    @endif

                    <a href="{{ url('/verify/' . $document->hash_code) }}" target="_blank"
                       class="btn btn-secondary btn-sm" style="width:100%;text-align:center;margin-bottom:.6rem;">
                        Open Verify Page ↗
                    </a>

                    {{-- Admin: force invalidate / restore --}}
                    @if(Auth::guard('portal')->user()->isAdministrator())
                    <div style="border-top:1px solid var(--border);padding-top:.75rem;margin-top:.5rem;">
                        @if(!$verification->isForcedInvalidity)
                        <form method="POST" action="{{ route('portal.docsign.invalidate', $document) }}"
                              onsubmit="return confirm('Force-invalidate this document? The verify page will show NOT VALID.')">
                            @csrf
                            <div class="form-group" style="margin-bottom:.6rem;">
                                <input type="text" name="reason" class="form-control"
                                       placeholder="Reason for invalidation…" required
                                       style="font-size:.82rem;padding:.4rem .7rem;">
                            </div>
                            <button class="btn btn-danger btn-sm" style="width:100%;">Force Invalidate</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('portal.docsign.restore', $document) }}">
                            @csrf
                            <button class="btn btn-secondary btn-sm" style="width:100%;">Restore Validity</button>
                        </form>
                        @endif

                        <div style="margin-top:.75rem;">
                            <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.4rem;">Change Valid Thru:</div>
                            <form method="POST" action="{{ route('portal.docsign.validthru', $document) }}"
                                  style="display:flex;gap:.4rem;">
                                @csrf
                                <input type="date" name="valid_thru" class="form-control"
                                       value="{{ $verification->signature_validThru?->format('Y-m-d') }}"
                                       style="font-size:.82rem;padding:.4rem .7rem;">
                                <button class="btn btn-secondary btn-sm" style="white-space:nowrap;">Set</button>
                            </form>
                        </div>
                    </div>
                    @endif

                @else
                    <div style="color:var(--text-muted);font-size:.875rem;text-align:center;padding:.75rem 0;">
                        No verification record yet.<br>
                        <small>Created automatically when the document is signed.</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const addInput   = document.getElementById('addSignerInput');
const addList    = document.getElementById('addAutoList');
const addIdInput = document.getElementById('addSignerId');
let addTimeout;

if (addInput) {
    addInput.addEventListener('input', function() {
        clearTimeout(addTimeout);
        const q = this.value.trim();
        if (q.length < 2) { addList.style.display = 'none'; return; }
        addTimeout = setTimeout(() => fetchAdd(q), 250);
    });
}

async function fetchAdd(q) {
    const res  = await fetch(`/portal/users/search/query?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!res.ok) return;
    const data = await res.json();
    if (!data.length) { addList.style.display='none'; return; }
    addList.innerHTML = data.map(u => `
        <div onclick="selectAdd(${u.id},'${u.name}')" style="padding:.55rem .9rem;cursor:pointer;font-size:.83rem;transition:background .12s;" onmouseover="this.style.background='rgba(255,255,255,.05)'" onmouseout="this.style.background=''">
            ${u.name} <span style="color:var(--text-muted);font-size:.75rem;">@${u.username}</span>
        </div>
    `).join('');
    addList.style.display = 'block';
}

function selectAdd(id, name) {
    addIdInput.value     = id;
    addInput.value       = name;
    addList.style.display = 'none';
}

async function assignSigner() {
    const id = addIdInput.value;
    if (!id) { alert('Please select a user first.'); return; }
    const docId = {{ $document->id }};
    const res = await fetch(`/portal/docsign/${docId}/assign`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ signer_id: id }),
    });
    const data = await res.json();
    if (data.success) {
        window.location.reload();
    }
}

document.addEventListener('click', e => {
    if (addList && !addInput?.contains(e.target)) addList.style.display = 'none';
});
</script>
@endpush
