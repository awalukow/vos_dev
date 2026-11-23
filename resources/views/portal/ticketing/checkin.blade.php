@extends('portal.ticketing.layout')
@section('ticket-page-title', 'Check In')

@section('ticket-content')
<div class="checkin-page">
    <div class="checkin-heading">
        <div><div class="eyebrow">Ticketing / Entrance</div><h1 class="admin-title">Ticket Check In</h1><p class="muted">Scan each guest’s ticket QR code. Valid tickets are checked in instantly.</p></div>
        <span class="connection-state" id="connection-state"><i></i><span>Ready to scan</span></span>
    </div>

    <div class="checkin-grid">
        <section class="panel scanner-panel">
            <div class="camera-box" id="camera-box">
                <video id="camera" playsinline muted aria-label="Ticket QR scanner"></video>
                <div class="camera-placeholder" id="camera-placeholder"><span class="camera-icon">⌗</span><strong>Camera is off</strong><span>Start the camera and point it at a ticket QR code.</span></div>
                <div class="scan-frame" aria-hidden="true"><i></i></div>
                <div class="camera-message" id="camera-message" role="status"></div>
            </div>
            <div class="camera-actions">
                <button class="btn btn-primary" type="button" id="camera-toggle">Start camera</button>
                <button class="btn btn-secondary" type="button" id="switch-camera" hidden>Switch camera</button>
            </div>
            <p class="tiny muted camera-note">Camera access requires HTTPS and your permission. Keep this page open while scanning.</p>

            <details class="manual-entry">
                <summary>Enter code manually</summary>
                <form id="manual-form" class="manual-row">
                    <input id="ticket-code" name="token" type="text" inputmode="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Paste a ticket QR link or code">
                    <button class="btn btn-secondary" type="submit">Check ticket</button>
                </form>
            </details>
        </section>

        <section class="result-panel" id="result-panel" data-kind="ready" aria-live="assertive" aria-atomic="true">
            <div class="result-symbol" id="result-symbol">⌗</div>
            <div class="result-kicker" id="result-kicker">Waiting for a ticket</div>
            <h2 id="result-title">Ready when you are</h2>
            <p id="result-description">The guest’s check in result will appear here.</p>
            <dl class="ticket-details" id="ticket-details" hidden>
                <div><dt>Guest</dt><dd id="guest-name"></dd></div>
                <div><dt>Ticket</dt><dd><span class="ticket-class-badge" id="ticket-class"></span></dd></div>
                <div><dt>Event</dt><dd id="event-title"></dd></div>
                <div><dt>Time</dt><dd id="checkin-time"></dd></div>
            </dl>
            <div class="result-footer" id="result-footer">Camera stays ready for the next guest.</div>
        </section>
    </div>
</div>

<style>
.checkin-page { max-width:1100px; margin:0 auto; }
.checkin-heading { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.25rem; }
.checkin-heading .admin-title { margin:.2rem 0 .35rem; }
.connection-state { display:inline-flex; align-items:center; gap:.5rem; padding:.55rem .8rem; border:1px solid var(--border2); border-radius:999px; color:var(--text-muted); font-size:.8rem; white-space:nowrap; }
.connection-state i { width:8px; height:8px; background:var(--text-dim); border-radius:50%; }
.connection-state[data-state="scanning"] i { background:var(--success); box-shadow:0 0 10px var(--success); }
.connection-state[data-state="checking"] i { background:var(--warning); }
.checkin-grid { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(300px,.9fr); gap:1rem; align-items:stretch; }
.scanner-panel { padding:1rem; }
.camera-box { position:relative; min-height:300px; aspect-ratio:4/3; overflow:hidden; border-radius:var(--radius); background:#050507; display:grid; place-items:center; }
.camera-box video { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; }
.camera-box.is-live video { display:block; }
.camera-placeholder { display:flex; flex-direction:column; align-items:center; gap:.55rem; text-align:center; color:var(--text-muted); padding:2rem; }
.camera-placeholder strong { color:var(--text); font-size:1rem; }
.camera-icon { color:var(--accent); font-size:2.8rem; line-height:1; }
.scan-frame { display:none; position:absolute; width:min(68%,280px); aspect-ratio:1; border:2px solid rgba(232,200,138,.9); border-radius:24px; box-shadow:0 0 0 100vmax rgba(0,0,0,.22); pointer-events:none; }
.scan-frame:before,.scan-frame:after,.scan-frame i:before,.scan-frame i:after { content:""; position:absolute; width:24px; height:24px; border-color:#f1d398; border-style:solid; }
.scan-frame:before { left:-2px;top:-2px;border-width:4px 0 0 4px;border-radius:10px 0 0; }
.scan-frame:after { right:-2px;top:-2px;border-width:4px 4px 0 0;border-radius:0 10px 0 0; }
.scan-frame i:before { left:-2px;bottom:-2px;border-width:0 0 4px 4px;border-radius:0 0 0 10px; }
.scan-frame i:after { right:-2px;bottom:-2px;border-width:0 4px 4px 0;border-radius:0 0 10px; }
.camera-box.is-live .scan-frame { display:block; }
.camera-message { position:absolute; left:.75rem;right:.75rem;bottom:.75rem;text-align:center;color:#fff;font-size:.8rem;text-shadow:0 1px 4px #000; }
.camera-actions { display:flex; gap:.6rem; margin-top:.85rem; }
.camera-note { margin-top:.6rem; }
.manual-entry { border-top:1px solid var(--border); padding-top:1rem; margin-top:1rem; }
.manual-entry label { display:block; font-size:.8rem; font-weight:600; margin-bottom:.45rem; }
.manual-row { display:flex; gap:.5rem; }
.manual-row input { min-width:0; flex:1; padding:.68rem .75rem; color:var(--text); background:var(--surface2); border:1px solid var(--border2); border-radius:var(--radius-sm); font:inherit; }
.result-panel { min-height:360px; padding:2rem; border:1px solid var(--border2); border-radius:var(--radius); background:var(--surface); display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; transition:background .2s,border-color .2s; }
.result-panel[data-kind="success"] { background:linear-gradient(145deg,rgba(34,197,94,.18),var(--surface) 65%);border-color:rgba(34,197,94,.55); }
.result-panel[data-kind="used"] { background:linear-gradient(145deg,rgba(245,158,11,.17),var(--surface) 65%);border-color:rgba(245,158,11,.55); }
.result-panel[data-kind="invalid"],.result-panel[data-kind="error"] { background:linear-gradient(145deg,rgba(239,68,68,.16),var(--surface) 65%);border-color:rgba(239,68,68,.5); }
.result-panel.scan-update { animation: checkin-result-arrive .55s cubic-bezier(.2,.8,.2,1); }
.result-panel.scan-update .result-symbol { animation: checkin-symbol-pop .55s cubic-bezier(.2,.8,.2,1); }
.result-panel.result-clearing { animation: checkin-result-clear .2s ease-in forwards; }
@keyframes checkin-result-arrive { 0% { transform:scale(.965); box-shadow:0 0 0 0 rgba(200,169,110,.42); } 55% { transform:scale(1.012); box-shadow:0 0 0 7px rgba(200,169,110,.16); } 100% { transform:scale(1); box-shadow:0 0 0 0 rgba(200,169,110,0); } }
@keyframes checkin-symbol-pop { 0% { transform:scale(.72); } 70% { transform:scale(1.12); } 100% { transform:scale(1); } }
@keyframes checkin-result-clear { to { opacity:0; transform:translateY(5px) scale(.99); } }
@media(prefers-reduced-motion:reduce) { .result-panel.scan-update,.result-panel.scan-update .result-symbol,.result-panel.result-clearing { animation:none; } }
.result-symbol { width:74px;height:74px;display:grid;place-items:center;border-radius:50%;font-size:2.5rem;color:var(--text-muted);background:var(--surface2);margin-bottom:1rem; }
.result-panel[data-kind="success"] .result-symbol { color:#08150c;background:var(--success); }
.result-panel[data-kind="used"] .result-symbol { color:#211606;background:var(--warning); }
.result-panel[data-kind="invalid"] .result-symbol,.result-panel[data-kind="error"] .result-symbol { color:#fff;background:var(--danger); }
.result-kicker { font-size:.72rem;text-transform:uppercase;letter-spacing:.13em;color:var(--text-muted);font-weight:700; }
.result-panel h2 { font-family:'Syne',sans-serif;font-size:clamp(1.5rem,3vw,2.15rem);margin:.4rem 0; }
.result-panel p { color:var(--text-muted);line-height:1.55;max-width:32rem; }
.ticket-details { width:100%;max-width:420px;text-align:left;margin:1.2rem 0 0;display:grid;gap:.45rem; }
.ticket-details[hidden] { display:none; }
.ticket-details div { display:flex;justify-content:space-between;gap:1rem;padding:.55rem .7rem;background:rgba(255,255,255,.035);border-radius:6px; }
.ticket-details dt { color:var(--text-muted);font-size:.78rem; }
.ticket-details dd { text-align:right;font-weight:600; }
.ticket-class-badge { display:inline-flex;align-items:center;max-width:100%;padding:.18rem .55rem;border:1px solid rgba(200,169,110,.4);border-radius:999px;background:rgba(200,169,110,.12);color:var(--accent2);font-size:.82rem;line-height:1.25; }
.result-footer { color:var(--text-dim);font-size:.76rem;margin-top:1.2rem; }
@media(max-width:800px) { .checkin-grid { grid-template-columns:1fr; } .result-panel { min-height:300px; } }
@media(max-width:600px) {
    body:has(.checkin-page) { height:100vh; overflow:hidden; }
    .main-wrap:has(.checkin-page) { height:100%; min-height:0; }
    body:has(.checkin-page) .page-content { flex:1 1 auto; min-height:0; overflow:hidden; padding:.45rem; }
    .checkin-page { height:calc(100vh - var(--topbar-h) - .9rem); max-width:none; display:flex; flex-direction:column; gap:.45rem; }
    @supports(height:100dvh) {
        body:has(.checkin-page) { height:100dvh; }
        .checkin-page { height:calc(100dvh - var(--topbar-h) - .9rem); }
    }
    .checkin-heading { flex:0 0 auto; align-items:center; flex-direction:row; gap:.5rem; margin:0; min-height:2.35rem; }
    .checkin-heading .eyebrow,.checkin-heading p { display:none; }
    .checkin-heading .admin-title { font-size:1.15rem; margin:0; }
    .connection-state { padding:.35rem .6rem; font-size:.72rem; }
    .checkin-grid { flex:1 1 auto; min-height:0; display:flex; flex-direction:column; gap:.45rem; }
    .scanner-panel { flex:0 0 auto; padding:.55rem; }
    .camera-box { min-height:0; height:clamp(132px,24vh,205px); height:clamp(132px,24svh,205px); aspect-ratio:auto; }
    .camera-placeholder { gap:.25rem; padding:.7rem; font-size:.75rem; }
    .camera-icon { font-size:1.8rem; }
    .camera-placeholder strong { font-size:.85rem; }
    .camera-actions { gap:.45rem; margin-top:.45rem; }
    .camera-actions .btn { min-height:38px; flex:1; justify-content:center; padding:.4rem .6rem; }
    .camera-note { display:none; }
    .manual-entry { border:0; padding:0; margin:.35rem 0 0; }
    .manual-entry summary { color:var(--text-muted); font-size:.75rem; padding:.25rem 0; cursor:pointer; }
    .manual-row { display:flex; gap:.35rem; margin-top:.3rem; }
    .manual-row input { min-width:0; padding:.5rem; font-size:.8rem; }
    .manual-row .btn { padding:.45rem .55rem; font-size:.75rem; }
    .result-panel { flex:1 1 auto; min-height:100px; padding:.65rem; display:grid; grid-template-columns:46px minmax(0,1fr); grid-template-rows:auto auto auto; column-gap:.6rem; row-gap:.1rem; align-content:center; justify-content:initial; text-align:left; }
    .result-symbol { grid-column:1; grid-row:1 / span 3; width:44px; height:44px; font-size:1.55rem; margin:0; align-self:center; }
    .result-kicker { grid-column:2; font-size:.61rem; letter-spacing:.1em; }
    .result-panel h2 { grid-column:2; font-size:1.18rem; line-height:1.15; margin:0; }
    .result-panel p { display:none; }
    .ticket-details { grid-column:1 / -1; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.25rem .4rem; margin:.5rem 0 0; max-width:none; }
    .ticket-details div { min-width:0; display:block; padding:.3rem .4rem; }
    .ticket-details dt { font-size:.62rem; }
    .ticket-details dd { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:left; font-size:.75rem; }
    .ticket-class-badge { padding:.12rem .4rem;font-size:.7rem; }
    .result-footer { display:none; }
    .result-panel:has(.ticket-details:not([hidden])) { align-content:center; }
}
@media(max-width:600px) and (max-height:700px) {
    .camera-box { height:clamp(118px,20vh,155px); height:clamp(118px,20svh,155px); }
    .scanner-panel { padding:.45rem; }
    .result-panel { padding:.5rem; }
}
@media(max-width:380px) {
    .connection-state { padding:.3rem .45rem; }
    .checkin-heading .admin-title { font-size:1.05rem; }
    .camera-box { height:clamp(118px,22vh,165px); height:clamp(118px,22svh,165px); }
}
</style>

<script>
(() => {
    const video = document.getElementById('camera');
    const box = document.getElementById('camera-box');
    const placeholder = document.getElementById('camera-placeholder');
    const toggle = document.getElementById('camera-toggle');
    const switchButton = document.getElementById('switch-camera');
    const status = document.getElementById('connection-state');
    const message = document.getElementById('camera-message');
    const result = document.getElementById('result-panel');
    const details = document.getElementById('ticket-details');
    const manualForm = document.getElementById('manual-form');
    const manualInput = document.getElementById('ticket-code');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const canvas = document.createElement('canvas');
    const canvasContext = canvas.getContext('2d', {willReadFrequently:true});
    let stream = null, detector = null, decoderMode = '', scanning = false, facing = 'environment', busy = false;
    let resultTimer = null, resultResetTimer = null, resultAnimationTimer = null, lastDetectedToken = '', lastDetectedAt = 0;
    const recentCameraScans = new Map();

    function loadFallbackDecoder() {
        if (window.jsQR) return Promise.resolve();
        if (!window.jsQRPromise) {
            window.jsQRPromise = new Promise((resolve, reject) => {
                const fail = reason => {
                    window.jsQRPromise = null;
                    reject(new Error(reason));
                };
                const script = document.createElement('script');
                script.src = @json(route('tickets.jsqr'));
                script.async = true;
                script.onload = () => window.jsQR
                    ? resolve()
                    : fail('The QR scanner script loaded without its decoder.');
                script.onerror = () => fail('Could not load the QR scanner. Check your connection and try again.');
                document.head.appendChild(script);
            });
        }
        return window.jsQRPromise;
    }

    function setState(value, label) { status.dataset.state = value; status.querySelector('span').textContent = label; }
    function show(kind, symbol, kicker, title, description, footer) {
        result.dataset.kind = kind;
        document.getElementById('result-symbol').textContent = symbol;
        document.getElementById('result-kicker').textContent = kicker;
        document.getElementById('result-title').textContent = title;
        document.getElementById('result-description').textContent = description;
        document.getElementById('result-footer').textContent = footer;
    }
    function resetResult() {
        clearTimeout(resultResetTimer);
        clearTimeout(resultAnimationTimer);
        resultResetTimer = null;
        result.classList.remove('scan-update', 'result-clearing');
        details.hidden = true;
        show('ready','⌗','Waiting for a ticket','Ready when you are','The guest’s check in result will appear here.','Camera stays ready for the next guest.');
    }
    function showScanResult(kind, symbol, kicker, title, description, footer) {
        clearTimeout(resultTimer);
        clearTimeout(resultResetTimer);
        clearTimeout(resultAnimationTimer);
        if (!['success', 'used'].includes(kind)) details.hidden = true;
        result.classList.remove('result-clearing', 'scan-update');
        show(kind, symbol, kicker, title, description, footer);
        void result.offsetWidth;
        result.classList.add('scan-update');
        resultAnimationTimer = setTimeout(() => result.classList.remove('scan-update'), 650);
        resultTimer = setTimeout(() => {
            result.classList.add('result-clearing');
            resultResetTimer = setTimeout(resetResult, 220);
        }, 5000);
    }
    function stopCamera() {
        scanning = false;
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        lastDetectedToken = '';
        lastDetectedAt = 0;
        video.srcObject = null;
        box.classList.remove('is-live');
        placeholder.hidden = false;
        toggle.textContent = 'Start camera';
        switchButton.hidden = true;
        setState('', 'Ready to scan');
    }
    function extractToken(value) {
        const match = String(value).trim().match(/[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/i);
        return match ? match[0].toLowerCase() : '';
    }
    function fillDetails(data) {
        document.getElementById('guest-name').textContent = data.guest || '';
        const ticketBadge = document.getElementById('ticket-class');
        ticketBadge.textContent = data.ticket || '';
        const classColor = /^#[0-9a-f]{6}$/i.test(data.ticket_color || '') ? data.ticket_color : '';
        ticketBadge.style.color = classColor || '';
        ticketBadge.style.backgroundColor = classColor ? classColor + '22' : '';
        ticketBadge.style.borderColor = classColor ? classColor + '88' : '';
        document.getElementById('event-title').textContent = data.event || '';
        document.getElementById('checkin-time').textContent = data.checked_in_at || '';
        details.hidden = false;
    }
    async function check(value) {
        const token = extractToken(value);
        if (!token) { showScanResult('invalid','×','Invalid code','Ticket code not recognized','Scan an individual ticket QR code, not the booking QR.','Ready for another scan.'); return; }
        if (busy) return;
        busy = true;
        setState('checking','Checking ticket…');
        message.textContent = 'Checking ticket…';
        try {
            const response = await fetch(@json(route('portal.ticketing.checkin.scan')), {
                method:'POST', credentials:'same-origin', cache:'no-store',
                headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
                body:JSON.stringify({token})
            });
            if (response.status === 419) throw new Error('Your session expired. Refresh this page and sign in again.');
            if (!response.ok) throw new Error('Could not reach the ticket service. Check your connection and scan again.');
            const data = await response.json();
            if (data.result === 'checked_in') {
                fillDetails(data);
                showScanResult('success','✓','Valid ticket','CHECKED IN',data.guest + ' is checked in.','Keep scanning; the camera is ready for the next guest.');
                if (navigator.vibrate) navigator.vibrate(90);
            } else if (data.result === 'already_checked_in') {
                fillDetails(data);
                showScanResult('used','!','Ticket already used','ALREADY CHECKED IN',data.guest + ' was checked in at ' + data.checked_in_at + '.','This ticket cannot be checked in twice.');
                if (navigator.vibrate) navigator.vibrate([60,50,60]);
            } else {
                showScanResult('invalid','×','Ticket rejected','NOT A VALID TICKET','This ticket is unpaid, invalid, or has been cancelled.','Ready for another scan.');
                if (navigator.vibrate) navigator.vibrate([120,50,120]);
            }
            setState(scanning ? 'scanning' : '', scanning ? 'Camera ready' : 'Ready to scan');
            message.textContent = '';
        } catch (error) {
            showScanResult('error','!','Connection issue','Could not check ticket',error.message,'Check the connection, then scan again.');
            setState('', 'Connection issue');
            message.textContent = 'Connection issue';
        } finally {
            busy = false;
            if (manualInput) manualInput.value = '';
        }
    }
    async function startCamera() {
        if (!navigator.mediaDevices?.getUserMedia) {
            const secureMessage = window.isSecureContext ? 'This browser cannot access a camera here. Use a recent Safari, Chrome, Edge, or Firefox browser.' : 'Open the portal over HTTPS, then tap Start camera again. Browsers block camera access on insecure pages.';
            show('error','!','Camera unavailable','Camera access is unavailable',secureMessage,'You can also enter the ticket code below.');
            return;
        }

        setState('checking','Waiting for camera permission…');
        message.textContent = 'Your browser may ask you to allow camera access.';
        // Start this directly from the button tap so Safari can show its native permission prompt.
        let cameraRequest;
        try {
            cameraRequest = navigator.mediaDevices.getUserMedia({audio:false,video:{facingMode:{ideal:facing}}});
            stream = await cameraRequest;
        } catch (error) {
            if (['OverconstrainedError', 'NotFoundError'].includes(error.name)) {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({audio:false,video:true});
                } catch (fallbackError) {
                    error = fallbackError;
                }
            }
            if (!stream) {
                const reasons = {
                    NotAllowedError: 'Allow camera access when Safari or your browser asks. If access was blocked earlier, enable Camera for this site in the browser settings, then try again.',
                    NotFoundError: 'No camera was found. Connect a camera or choose another device, then try again.',
                    NotReadableError: 'The camera is busy or unavailable. Close other apps using it and try again.',
                    OverconstrainedError: 'This camera does not support the requested mode. Check that a camera is available and try again.',
                    SecurityError: 'Camera access is blocked by browser security. Open the portal over HTTPS and allow camera access for this site.'
                };
                const explanation = !window.isSecureContext
                    ? 'Open the portal over HTTPS, then tap Start camera again. Browsers block camera access on insecure pages.'
                    : (reasons[error.name] || 'The browser could not open a camera. Check the site permission and device camera settings, then try again.');
                show('error','!','Camera unavailable','Unable to open camera',explanation,'You can also enter the ticket code below.');
                stopCamera();
                return;
            }
        }

        try {
            if ('BarcodeDetector' in window) {
                try {
                    const formats = await BarcodeDetector.getSupportedFormats();
                    if (formats.includes('qr_code')) {
                        detector = new BarcodeDetector({formats:['qr_code']});
                        decoderMode = 'native';
                    }
                } catch (_) { /* Use the local decoder when the browser API is incomplete. */ }
            }
            if (!decoderMode) {
                setState('checking','Loading QR scanner…');
                message.textContent = 'Preparing camera scanner…';
                await loadFallbackDecoder();
                decoderMode = 'jsqr';
            }
            video.srcObject = stream;
            await video.play();
            scanning = true;
            box.classList.add('is-live'); placeholder.hidden = true;
            toggle.textContent = 'Stop camera'; switchButton.hidden = false;
            setState('scanning','Camera ready'); message.textContent = 'Point at an individual ticket QR';
            scanFrame();
        } catch (error) {
            const isDecoderError = !window.jsQR && !detector;
            const title = isDecoderError ? 'Unable to load QR scanner' : 'Unable to start camera';
            const explanation = isDecoderError
                ? 'The camera is open, but the QR decoder could not load. Check your connection and tap Start camera to retry.'
                : 'The camera permission was granted, but the video could not start. Close other apps using the camera and try again.';
            show('error','!','Camera unavailable',title,explanation,'You can also enter the ticket code below.');
            stopCamera();
        }
    }
    async function scanFrame() {
        if (!scanning) return;
        if (!busy && video.readyState >= 2) {
            try {
                let rawValue = '';
                if (decoderMode === 'native') {
                    const codes = await detector.detect(video);
                    rawValue = codes[0]?.rawValue || '';
                } else {
                    if (video.videoWidth && video.videoHeight) {
                        const width = Math.min(video.videoWidth, 640);
                        const height = Math.round(video.videoHeight * (width / video.videoWidth));
                        if (canvas.width !== width || canvas.height !== height) { canvas.width = width; canvas.height = height; }
                        canvasContext.drawImage(video, 0, 0, width, height);
                        rawValue = window.jsQR(canvasContext.getImageData(0, 0, width, height).data, width, height, {inversionAttempts:'dontInvert'})?.data || '';
                    }
                }
                if (rawValue) {
                    const token = extractToken(rawValue);
                    if (token) {
                        lastDetectedAt = Date.now();
                        if (token !== lastDetectedToken) {
                            lastDetectedToken = token;
                            const lastScanAt = recentCameraScans.get(token) || 0;
                            if (lastDetectedAt - lastScanAt >= 5000) {
                                recentCameraScans.set(token, lastDetectedAt);
                                if (recentCameraScans.size > 256) {
                                    for (const [seenToken, seenAt] of recentCameraScans) {
                                        if (lastDetectedAt - seenAt >= 30000) recentCameraScans.delete(seenToken);
                                    }
                                    while (recentCameraScans.size > 256) {
                                        recentCameraScans.delete(recentCameraScans.keys().next().value);
                                    }
                                }
                                await check(token);
                            }
                        }
                    }
                }
                if (Date.now() - lastDetectedAt > 900) {
                    lastDetectedToken = '';
                }
            } catch (_) { /* Wait for the next camera frame. */ }
        }
        if (scanning) window.setTimeout(scanFrame, 120);
    }
    toggle.addEventListener('click', () => scanning ? stopCamera() : startCamera());
    switchButton.addEventListener('click', async () => { facing = facing === 'environment' ? 'user' : 'environment'; stopCamera(); await startCamera(); });
    manualForm.addEventListener('submit', event => { event.preventDefault(); check(manualInput.value); });
    window.addEventListener('pagehide', stopCamera);
})();
</script>
@endsection
