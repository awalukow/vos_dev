@extends('portal.layouts.app')
@section('title', 'Sign Document')
@section('page-title', 'DocSign — Sign Document')

@push('styles')
<style>
.sign-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.5rem;
    align-items: start;
}

.pdf-viewer-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.pdf-toolbar {
    padding: .75rem 1rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: .75rem;
}

.pdf-page-info { font-size: .82rem; color: var(--text-muted); margin-left: auto; }

#pdfCanvas {
    display: block;
    width: 100%;
    cursor: crosshair;
    position: relative;
}

.pdf-container {
    position: relative;
    overflow: auto;
    max-height: 75vh;
}

/* QR overlay */
#qrOverlay {
    position: absolute;
    border: 2px dashed var(--accent);
    background: rgba(200,169,110,.15);
    cursor: move;
    display: none;
    pointer-events: all;
    border-radius: 4px;
}

#qrOverlay::after {
    content: 'QR';
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: .9rem;
    color: var(--accent);
    pointer-events: none;
}

.sign-panel .card + .card { margin-top: 1rem; }

.sign-instruction {
    background: rgba(200,169,110,.07);
    border: 1px solid rgba(200,169,110,.2);
    border-radius: var(--radius-sm);
    padding: .85rem 1rem;
    font-size: .85rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.sign-instruction strong { color: var(--accent); }

.range-group { margin-bottom: .9rem; }
.range-group label { display: flex; justify-content: space-between; font-size: .78rem; color: var(--text-muted); margin-bottom: .3rem; }
.range-group label span { color: var(--accent); font-weight: 600; }
input[type=range] {
    width: 100%; accent-color: var(--accent);
    height: 4px; cursor: pointer;
}

.doc-info-row { display: flex; gap: .5rem; align-items: baseline; margin-bottom: .5rem; }
.doc-info-key { font-size: .75rem; color: var(--text-muted); width: 90px; flex-shrink:0; }
.doc-info-val { font-size: .85rem; font-weight: 500; }
</style>
@endpush

@section('content')

<div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;">
    <a href="{{ route('portal.docsign.show', $document) }}" class="btn btn-secondary">← Back</a>
    <div>
        <h1 style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;">Sign Document</h1>
        <p style="color:var(--text-muted);font-size:.875rem;">{{ $document->title }}</p>
    </div>
</div>

<div class="sign-layout">

    {{-- PDF Viewer --}}
    <div class="pdf-viewer-wrap">
        <div class="pdf-toolbar">
            <button class="btn btn-secondary btn-sm" onclick="prevPage()">← Prev</button>
            <button class="btn btn-secondary btn-sm" onclick="nextPage()">Next →</button>
            <span class="pdf-page-info" id="pageInfo">Page 1 of —</span>
            <span style="font-size:.78rem;color:var(--text-muted);">Click on the PDF to place QR</span>
        </div>
        <div class="pdf-container" id="pdfContainer">
            <canvas id="pdfCanvas"></canvas>
            <div id="qrOverlay"></div>
        </div>
    </div>

    {{-- Sign Panel --}}
    <div class="sign-panel">

        <div class="card">
            <div class="card-header"><span class="card-title">Document Info</span></div>
            <div class="card-body" style="padding:1rem 1.25rem;">
                <div class="doc-info-row"><span class="doc-info-key">Title</span><span class="doc-info-val">{{ $document->title }}</span></div>
                <div class="doc-info-row"><span class="doc-info-key">Uploaded by</span><span class="doc-info-val">{{ $document->uploader->name }}</span></div>
                <div class="doc-info-row"><span class="doc-info-key">Date</span><span class="doc-info-val">{{ $document->created_at->format('d M Y') }}</span></div>
                @if($document->notes)
                <div style="margin-top:.75rem;padding:.65rem .8rem;background:var(--surface2);border-radius:var(--radius-sm);font-size:.82rem;color:var(--text-muted);">
                    {{ $document->notes }}
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Place QR Code</span></div>
            <div class="card-body">
                <div class="sign-instruction">
                    <strong>How to sign:</strong><br>
                    Click anywhere on the PDF to place your QR code. Drag to reposition. Adjust size below, then click <strong>Sign Document</strong>.
                </div>

                <form method="POST" action="{{ route('portal.docsign.sign.process', [$document, $signature]) }}" id="signForm">
                    @csrf

                    <input type="hidden" name="qr_page"       id="inputPage" value="1">
                    <input type="hidden" name="qr_position_x" id="inputPosX" value="10">
                    <input type="hidden" name="qr_position_y" id="inputPosY" value="10">
                    <input type="hidden" name="qr_size"       id="inputSize" value="40">

                    <div class="range-group">
                        <label>QR Size <span id="sizeLabel">40 mm</span></label>
                        <input type="range" id="sizeRange" min="20" max="100" value="40"
                               oninput="updateSize(this.value)">
                    </div>

                    <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:.75rem;margin-bottom:1rem;font-size:.8rem;color:var(--text-muted);">
                        <div>Page: <strong id="dispPage" style="color:var(--text);">—</strong></div>
                        <div>Position X: <strong id="dispX" style="color:var(--text);">—</strong></div>
                        <div>Position Y: <strong id="dispY" style="color:var(--text);">—</strong></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;" id="signBtn" disabled>
                        ✍ Sign Document
                    </button>
                </form>

                <div style="margin-top:.75rem;border-top:1px solid var(--border);padding-top:.75rem;">
                    <button onclick="showRejectModal()" class="btn btn-danger btn-sm" style="width:100%;">
                        Reject / Decline
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9000;display:none;align-items:center;justify-content:center;">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;width:90%;max-width:440px;">
        <h3 style="font-family:'Syne',sans-serif;margin-bottom:1rem;">Reject Document</h3>
        <form method="POST" action="{{ route('portal.docsign.reject', [$document, $signature]) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Reason for rejection *</label>
                <textarea name="reason" class="form-control" rows="4" required
                          placeholder="Please explain why you are declining to sign this document…"></textarea>
            </div>
            <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" onclick="hideRejectModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

let pdfDoc = null, pageNum = 1, pageRendering = false, scale = 1.5;
const canvas      = document.getElementById('pdfCanvas');
const ctx         = canvas.getContext('2d');
const overlay     = document.getElementById('qrOverlay');
const container   = document.getElementById('pdfContainer');
let qrPlaced      = false;
let dragOffset    = { x: 0, y: 0 };
let isDragging    = false;
let qrSizePx      = 80; // pixels on screen

// Load PDF
const pdfUrl = "{{ route('portal.docsign.pdf', $document) }}";
pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
    pdfDoc = pdf;
    document.getElementById('pageInfo').textContent = `Page ${pageNum} of ${pdf.numPages}`;
    renderPage(pageNum);
});

function renderPage(num) {
    pageRendering = true;
    pdfDoc.getPage(num).then(page => {
        const viewport = page.getViewport({ scale });
        canvas.height  = viewport.height;
        canvas.width   = viewport.width;
        page.render({ canvasContext: ctx, viewport }).promise.then(() => {
            pageRendering = false;
            document.getElementById('pageInfo').textContent = `Page ${num} of ${pdfDoc.numPages}`;
            // Re-position overlay if placed
            if (qrPlaced) positionOverlayFromPercent();
        });
    });
}

function prevPage() { if (pageNum > 1) { pageNum--; renderPage(pageNum); } }
function nextPage() { if (pageNum < pdfDoc.numPages) { pageNum++; renderPage(pageNum); } }

// Click to place QR
canvas.addEventListener('click', function(e) {
    if (isDragging) return;
    const rect = canvas.getBoundingClientRect();
    const x    = e.clientX - rect.left;
    const y    = e.clientY - rect.top;
    placeQR(x, y);
});

function placeQR(x, y) {
    qrPlaced = true;
    const pct = getPercent(x, y);
    positionOverlay(x, y);
    updateInputs(pct.x, pct.y);
    document.getElementById('signBtn').disabled = false;
}

function positionOverlay(x, y) {
    const size = qrSizePx;
    overlay.style.display  = 'block';
    overlay.style.left     = (x - size/2) + 'px';
    overlay.style.top      = (y - size/2) + 'px';
    overlay.style.width    = size + 'px';
    overlay.style.height   = size + 'px';
}

function positionOverlayFromPercent() {
    const x = (parseFloat(document.getElementById('inputPosX').value) / 100) * canvas.offsetWidth;
    const y = (parseFloat(document.getElementById('inputPosY').value) / 100) * canvas.offsetHeight;
    positionOverlay(x, y);
}

function getPercent(x, y) {
    return {
        x: parseFloat(((x / canvas.offsetWidth) * 100).toFixed(2)),
        y: parseFloat(((y / canvas.offsetHeight) * 100).toFixed(2)),
    };
}

function updateInputs(px, py) {
    document.getElementById('inputPage').value = pageNum;
    document.getElementById('inputPosX').value = px;
    document.getElementById('inputPosY').value = py;
    document.getElementById('dispPage').textContent = pageNum;
    document.getElementById('dispX').textContent    = px + '%';
    document.getElementById('dispY').textContent    = py + '%';
}

// Drag QR overlay
overlay.addEventListener('mousedown', function(e) {
    isDragging = true;
    const rect = overlay.getBoundingClientRect();
    dragOffset.x = e.clientX - rect.left;
    dragOffset.y = e.clientY - rect.top;
    e.preventDefault();
});

document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    const canvasRect = canvas.getBoundingClientRect();
    const x = e.clientX - canvasRect.left;
    const y = e.clientY - canvasRect.top;
    positionOverlay(x, y);
    const pct = getPercent(x, y);
    updateInputs(pct.x, pct.y);
});

document.addEventListener('mouseup', () => { setTimeout(() => isDragging = false, 50); });

// Size range
function updateSize(val) {
    qrSizePx = val * 1.8; // approx px
    document.getElementById('sizeLabel').textContent = val + ' mm';
    document.getElementById('inputSize').value = val;
    if (qrPlaced) positionOverlayFromPercent();
    overlay.style.width  = qrSizePx + 'px';
    overlay.style.height = qrSizePx + 'px';
}

// Reject modal
function showRejectModal() { document.getElementById('rejectModal').style.display = 'flex'; }
function hideRejectModal() { document.getElementById('rejectModal').style.display = 'none'; }
</script>
@endpush
