{{-- resources/views/portal/sales/_wip.blade.php --}}
@push('styles')
<style>
.wip-scene {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 65vh;
    text-align: center;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

/* Floating background emojis */
.float-emoji {
    position: absolute;
    font-size: 2rem;
    opacity: .12;
    animation: floatUp linear infinite;
    pointer-events: none;
    user-select: none;
}

@keyframes floatUp {
    0%   { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10%  { opacity: .12; }
    90%  { opacity: .12; }
    100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
}

/* Main robot */
.robot-container {
    position: relative;
    margin-bottom: 2.5rem;
}

.robot {
    font-size: 6rem;
    animation: robotBob 2s ease-in-out infinite;
    display: block;
    line-height: 1;
}

@keyframes robotBob {
    0%,100% { transform: translateY(0) rotate(-3deg); }
    50%      { transform: translateY(-12px) rotate(3deg); }
}

.robot-shadow {
    width: 80px; height: 12px;
    background: rgba(200,169,110,.2);
    border-radius: 50%;
    margin: 0 auto;
    animation: shadowPulse 2s ease-in-out infinite;
}

@keyframes shadowPulse {
    0%,100% { transform: scaleX(1); opacity: .5; }
    50%      { transform: scaleX(.7); opacity: .2; }
}

/* Thinking bubbles */
.think-bubble {
    position: absolute;
    top: -10px; right: -20px;
    display: flex; flex-direction: column; align-items: flex-start; gap: 3px;
}

.think-dot {
    background: var(--accent);
    border-radius: 50%;
    animation: thinkBounce 1.2s ease-in-out infinite;
}
.think-dot:nth-child(1) { width: 6px; height: 6px; animation-delay: 0s; }
.think-dot:nth-child(2) { width: 9px; height: 9px; animation-delay: .15s; }
.think-dot:nth-child(3) { width: 14px; height: 14px; animation-delay: .3s; }

@keyframes thinkBounce {
    0%,100% { opacity: .3; transform: scale(1); }
    50%      { opacity: 1; transform: scale(1.15); }
}

/* Title */
.wip-title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    margin-bottom: .75rem;
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 50%, #fff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.wip-subtitle {
    color: var(--text-muted);
    font-size: 1rem;
    max-width: 480px;
    line-height: 1.7;
    margin-bottom: 2rem;
}

/* Progress bar (fake) */
.fake-progress-wrap {
    width: 320px;
    max-width: 90%;
    margin-bottom: 2rem;
}

.fake-progress-label {
    display: flex; justify-content: space-between;
    font-size: .75rem; color: var(--text-muted); margin-bottom: .4rem;
}

.fake-progress-bar {
    height: 6px;
    background: var(--border2);
    border-radius: 99px;
    overflow: hidden;
}

.fake-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    border-radius: 99px;
    animation: fakeProgress 4s ease-in-out infinite;
}

@keyframes fakeProgress {
    0%   { width: 20%; }
    30%  { width: 55%; }
    60%  { width: 72%; }
    80%  { width: 68%; }
    100% { width: 20%; }
}

/* Rotating quotes */
.funny-quote {
    font-size: .85rem;
    color: var(--text-muted);
    font-style: italic;
    animation: quotesFade 8s ease-in-out infinite;
    min-height: 1.4em;
}

@keyframes quotesFade {
    0%,15%  { opacity: 1; }
    20%,80% { opacity: 0; }
    85%,100%{ opacity: 1; }
}

/* Bouncing dots loader */
.loader-dots {
    display: flex; gap: .5rem; justify-content: center;
    margin-top: 1.5rem;
}

.loader-dot {
    width: 10px; height: 10px;
    background: var(--accent);
    border-radius: 50%;
    animation: dotBounce 1.2s ease-in-out infinite;
}

.loader-dot:nth-child(2) { animation-delay: .2s; }
.loader-dot:nth-child(3) { animation-delay: .4s; }

@keyframes dotBounce {
    0%,100% { transform: translateY(0); opacity: .4; }
    40%     { transform: translateY(-12px); opacity: 1; }
}

/* Ticket/card animation */
.wip-badge {
    display: inline-flex; align-items: center; gap: .5rem;
    background: rgba(200,169,110,.1);
    border: 1px solid rgba(200,169,110,.25);
    border-radius: 20px;
    padding: .4rem 1rem;
    font-size: .8rem;
    color: var(--accent);
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    animation: badgePulse 2.5s ease-in-out infinite;
    margin-bottom: 1.25rem;
}

@keyframes badgePulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(200,169,110,.2); }
    50%     { box-shadow: 0 0 0 8px rgba(200,169,110,0); }
}

.wip-badge::before {
    content: '';
    width: 8px; height: 8px;
    background: var(--accent);
    border-radius: 50%;
    animation: dotBounceSmall 1.5s ease-in-out infinite;
}

@keyframes dotBounceSmall {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.4); }
}
</style>
@endpush

<div class="wip-scene" id="wipScene">

    {{-- Floating background emojis (generated by JS) --}}

    <div class="robot-container">
        <span class="robot">🤖</span>
        <div class="think-bubble">
            <div class="think-dot"></div>
            <div class="think-dot"></div>
            <div class="think-dot"></div>
        </div>
        <div class="robot-shadow"></div>
    </div>

    <div class="wip-badge">
        <span>On Development</span>
    </div>

    <h2 class="wip-title">{{ $title }}</h2>

    <p class="wip-subtitle">{{ $subtitle }}</p>

    <div class="fake-progress-wrap">
        <div class="fake-progress-label">
            <span>Building something awesome…</span>
            <span id="pctLabel">42%</span>
        </div>
        <div class="fake-progress-bar">
            <div class="fake-progress-fill" id="fillBar"></div>
        </div>
    </div>

    <p class="funny-quote" id="quoteEl">"We're just moving fast and breaking things. Mostly promises."</p>

    <div class="loader-dots">
        <div class="loader-dot"></div>
        <div class="loader-dot"></div>
        <div class="loader-dot"></div>
    </div>

</div>

<script>
// ── Float emojis ──────────────────────────────────────────────────────────
const emojis = ['📊','💹','💰','📈','🎵','🎶','💼','📋','🎤','🏆','⭐','💡','🚀','☕','🖥'];
const scene  = document.getElementById('wipScene');

emojis.forEach((em, i) => {
    const el = document.createElement('span');
    el.className   = 'float-emoji';
    el.textContent = em;
    el.style.left  = (Math.random() * 95) + '%';
    el.style.animationDuration = (8 + Math.random() * 12) + 's';
    el.style.animationDelay   = (Math.random() * 10) + 's';
    el.style.fontSize = (1.2 + Math.random() * 2) + 'rem';
    scene.appendChild(el);
});

// ── Rotating funny quotes ────────────────────────────────────────────────
const quotes = [
    '"We're just moving fast and breaking things. Mostly promises."',
    '"It works on my machine™. Shipping the machine soon."',
    '"The feature is 90% done. The remaining 90% is in progress."',
    '"Our backlog is so full it has its own backlog."',
    '"Code review approved! (by the developer who wrote it)"',
    '"Currently converting coffee into code. ETA: 3 sprints."',
    '"We pivoted. Then pivoted back. Now pivoting sideways."',
    '"The UI designer and backend developer are not speaking."',
];
let qi = 0;
const quoteEl = document.getElementById('quoteEl');
setInterval(() => {
    qi = (qi + 1) % quotes.length;
    quoteEl.textContent = quotes[qi];
    quoteEl.style.animation = 'none';
    void quoteEl.offsetWidth; // reflow
    quoteEl.style.animation = 'quotesFade 8s ease-in-out infinite';
}, 8000);

// ── Fake progress counter ────────────────────────────────────────────────
const fillBar  = document.getElementById('fillBar');
const pctLabel = document.getElementById('pctLabel');
function syncPct() {
    const w = parseFloat(getComputedStyle(fillBar).width);
    const p = parseFloat(getComputedStyle(fillBar.parentElement).width);
    const pct = Math.round((w/p) * 100);
    pctLabel.textContent = pct + '%';
}
setInterval(syncPct, 200);
</script>
