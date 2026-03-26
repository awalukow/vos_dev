@extends('portal.layouts.app')
@section('title', 'Upload Document')
@section('page-title', 'DocSign — Upload Document')

@push('styles')
<style>
.drop-zone {
    border: 2px dashed var(--border2);
    border-radius: var(--radius);
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    background: var(--surface2);
    position: relative;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent);
    background: rgba(200,169,110,.04);
}
.drop-zone input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.drop-icon { font-size: 2.5rem; margin-bottom: .75rem; }
.drop-label { font-size: .95rem; color: var(--text); font-weight: 500; margin-bottom: .3rem; }
.drop-hint  { font-size: .82rem; color: var(--text-muted); }
.file-selected {
    margin-top: 1rem;
    padding: .65rem 1rem;
    background: rgba(200,169,110,.08);
    border: 1px solid rgba(200,169,110,.2);
    border-radius: var(--radius-sm);
    font-size: .85rem;
    color: var(--accent);
    display: none;
}

/* Signer autocomplete */
.signer-search-wrap { position: relative; }
.autocomplete-list {
    position: absolute; top: 100%; left: 0; right: 0;
    background: var(--surface2);
    border: 1px solid var(--border2);
    border-radius: var(--radius-sm);
    z-index: 100;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    box-shadow: 0 8px 24px rgba(0,0,0,.4);
}
.autocomplete-item {
    padding: .6rem 1rem;
    cursor: pointer;
    font-size: .875rem;
    transition: background .12s;
    display: flex; align-items: center; gap: .6rem;
}
.autocomplete-item:hover { background: rgba(255,255,255,.05); }
.autocomplete-item .meta { font-size: .75rem; color: var(--text-muted); }

.signer-tags { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .5rem; }
.signer-tag {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(200,169,110,.1);
    border: 1px solid rgba(200,169,110,.25);
    border-radius: 20px;
    padding: .25rem .65rem;
    font-size: .8rem;
    color: var(--accent);
}
.signer-tag button {
    background: none; border: none; cursor: pointer; color: var(--text-muted);
    line-height: 1; padding: 0; font-size: .9rem;
}
.signer-tag button:hover { color: var(--danger); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Upload Document</h1>
        <p>Upload a PDF and optionally assign signers.</p>
    </div>
    <a href="{{ route('portal.docsign.index') }}" class="btn btn-secondary">← Back to List</a>
</div>

<div style="max-width:720px;">
<form method="POST" action="{{ route('portal.docsign.store') }}" enctype="multipart/form-data">
@csrf

<div class="card">
    <div class="card-header"><span class="card-title">Document Details</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:1.1rem;">

        <div class="form-group">
            <label class="form-label">Document Title *</label>
            <input type="text" name="title" value="{{ old('title') }}"
                   class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                   placeholder="e.g. Contract Agreement Q1 2025">
            @error('title') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">PDF File *</label>
            <div class="drop-zone" id="dropZone">
                <input type="file" name="file" accept=".pdf" id="fileInput"
                       onchange="handleFileSelect(this)">
                <div class="drop-icon">📄</div>
                <div class="drop-label">Drop your PDF here or click to browse</div>
                <div class="drop-hint">Maximum file size: 20 MB</div>
            </div>
            <div class="file-selected" id="fileSelected"></div>
            @error('file') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Any instructions or notes for signers…">{{ old('notes') }}</textarea>
        </div>

    </div>
</div>

<div class="card" style="margin-top:1.25rem;">
    <div class="card-header">
        <span class="card-title">Assign Signers</span>
        <span style="font-size:.8rem;color:var(--text-muted);">Optional — you can add signers later</span>
    </div>
    <div class="card-body">

        <div class="form-group">
            <label class="form-label">Search Users</label>
            <div class="signer-tags" id="signerTags"></div>
            <div class="signer-search-wrap">
                <input type="text" id="signerSearch" class="form-control"
                       placeholder="Type username or email…" autocomplete="off">
                <div class="autocomplete-list" id="autocompleteList"></div>
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);margin-top:.4rem;">
                Signers will be listed in the order you add them.
            </div>
            <div id="signerInputs"></div>
        </div>

    </div>
</div>

<div style="display:flex;gap:.75rem;margin-top:1.5rem;justify-content:flex-end;">
    <a href="{{ route('portal.docsign.index') }}" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary">Upload Document →</button>
</div>

</form>
</div>

@endsection

@push('scripts')
<script>
// ── File drop zone ──────────────────────────────────────────
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileSelected = document.getElementById('fileSelected');

['dragenter','dragover'].forEach(e => dropZone.addEventListener(e, ev => {
    ev.preventDefault(); dropZone.classList.add('drag-over');
}));
['dragleave','drop'].forEach(e => dropZone.addEventListener(e, () => dropZone.classList.remove('drag-over')));
dropZone.addEventListener('drop', ev => {
    ev.preventDefault();
    if (ev.dataTransfer.files.length) {
        fileInput.files = ev.dataTransfer.files;
        handleFileSelect(fileInput);
    }
});

function handleFileSelect(input) {
    if (input.files.length) {
        const name = input.files[0].name;
        const size = (input.files[0].size / 1024 / 1024).toFixed(2);
        fileSelected.style.display = 'block';
        fileSelected.textContent = `✓ ${name} (${size} MB)`;
    }
}

// ── Signer autocomplete ─────────────────────────────────────
const signerSearch  = document.getElementById('signerSearch');
const autocompleteList = document.getElementById('autocompleteList');
const signerTags    = document.getElementById('signerTags');
const signerInputs  = document.getElementById('signerInputs');
let selectedSigners = [];
let searchTimeout;

signerSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { autocompleteList.style.display = 'none'; return; }
    searchTimeout = setTimeout(() => fetchUsers(q), 250);
});

async function fetchUsers(q) {
    const res  = await fetch(`/portal/users/search/query?q=${encodeURIComponent(q)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    });
    if (!res.ok) return;
    const data = await res.json();
    renderAutocomplete(data);
}

function renderAutocomplete(users) {
    const filtered = users.filter(u => !selectedSigners.find(s => s.id === u.id));
    if (!filtered.length) { autocompleteList.style.display = 'none'; return; }
    autocompleteList.innerHTML = filtered.map(u => `
        <div class="autocomplete-item" onclick="addSigner(${u.id}, '${u.name}', '${u.username}')">
            <div>
                <div>${u.name}</div>
                <div class="meta">@${u.username} · ${u.email}</div>
            </div>
        </div>
    `).join('');
    autocompleteList.style.display = 'block';
}

function addSigner(id, name, username) {
    if (selectedSigners.find(s => s.id === id)) return;
    selectedSigners.push({ id, name, username });
    renderTags();
    renderHiddenInputs();
    signerSearch.value = '';
    autocompleteList.style.display = 'none';
}

function removeSigner(id) {
    selectedSigners = selectedSigners.filter(s => s.id !== id);
    renderTags();
    renderHiddenInputs();
}

function renderTags() {
    signerTags.innerHTML = selectedSigners.map(s => `
        <span class="signer-tag">
            ${s.name} <span style="opacity:.6">@${s.username}</span>
            <button type="button" onclick="removeSigner(${s.id})">×</button>
        </span>
    `).join('');
}

function renderHiddenInputs() {
    signerInputs.innerHTML = selectedSigners.map((s, i) =>
        `<input type="hidden" name="signers[]" value="${s.id}">`
    ).join('');
}

document.addEventListener('click', e => {
    if (!signerSearch.contains(e.target)) autocompleteList.style.display = 'none';
});
</script>
@endpush
