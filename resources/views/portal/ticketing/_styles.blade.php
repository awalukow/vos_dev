<style>
/* Ticketing content uses the management portal's shared theme and components. */
.portal-ticketing { min-width:0; line-height:1.6; }
.portal-ticketing .table-responsive { overflow-x:auto; margin:1.25rem 0; }
.portal-ticketing table { width:100%; border-collapse:collapse; background:var(--surface); }
.portal-ticketing th, .portal-ticketing td { text-align:left; padding:1rem; border-bottom:1px solid var(--border); vertical-align:top; }
.portal-ticketing th { white-space:nowrap; color:var(--text-muted); }
.portal-ticketing a:not(.btn) { color:var(--accent); }
.portal-ticketing p { margin:8px 0 18px; }
.portal-ticketing h1, .portal-ticketing h2, .portal-ticketing h3 { font-family:'Syne',sans-serif; color:var(--text); }
.portal-ticketing h1 { font-size:1.6rem; font-weight:800; margin:8px 0 12px; }
.portal-ticketing h2 { font-size:1.05rem; margin:0 0 14px; }
.portal-ticketing h3 { font-size:1rem; margin:10px 0; }
.portal-ticketing .eyebrow { font-size:.7rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; color:var(--accent); }
.portal-ticketing .muted { color:var(--text-muted); }
.portal-ticketing .tiny { font-size:.78rem; }
.portal-ticketing .page-head { margin-bottom:1.5rem; }
.portal-ticketing .section-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
.portal-ticketing .panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:1.25rem; min-width:0; }
.portal-ticketing .split { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:1.5rem; align-items:start; }
.portal-ticketing .grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.25rem; }
.portal-ticketing .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 1.25rem; }
.portal-ticketing .form-grid .wide { grid-column:1/-1; }
.portal-ticketing label { display:block; font-size:.78rem; font-weight:600; color:var(--text-muted); margin:1rem 0 .45rem; }
.portal-ticketing input:not([type=checkbox]):not([type=radio]):not([type=color]),
.portal-ticketing textarea, .portal-ticketing select {
    width:100%; background:var(--surface2); border:1px solid var(--border2);
    border-radius:var(--radius-sm); color:var(--text); font:inherit; padding:.65rem .9rem; color-scheme:dark;
}
.portal-ticketing input[type=color] { width:70px; height:42px; padding:3px; border:1px solid var(--border2); border-radius:var(--radius-sm); background:var(--surface2); }
.portal-ticketing input[type=checkbox] { accent-color:var(--accent); }
.portal-ticketing :focus-visible { outline:2px solid var(--accent); outline-offset:3px; }
.portal-ticketing input:disabled { opacity:.6; }
.portal-ticketing .check { display:flex; gap:.5rem; align-items:center; }
.portal-ticketing .full { width:100%; justify-content:center; }
.portal-ticketing .link-button { border:0; background:transparent; color:var(--accent); font:inherit; cursor:pointer; }
.portal-ticketing .actions { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1.25rem; }
.portal-ticketing .toolbar { display:flex; flex-wrap:wrap; align-items:end; gap:.75rem; }
.portal-ticketing .toolbar>div { flex:1; min-width:90px; }
.portal-ticketing .notice { padding:1rem; margin:1.25rem 0; background:var(--accent-glow); border:1px solid var(--border2); border-left:3px solid var(--accent); border-radius:var(--radius-sm); overflow-wrap:anywhere; }
.portal-ticketing .notice.error { background:rgba(239,68,68,.08); border-left-color:var(--danger); color:#fca5a5; }
.portal-ticketing .notice ul { padding-left:1.5rem; margin-top:.5rem; }
.portal-ticketing .badge { background:var(--accent-glow); color:var(--accent); }
.portal-ticketing .badge.yellow { background:rgba(245,158,11,.15); color:#fbbf24; }
.portal-ticketing .empty { text-align:center; border:1px dashed var(--border2); padding:3rem 1.5rem; border-radius:var(--radius); margin-bottom:1.25rem; }
.portal-ticketing .qr-image { max-width:240px; max-height:300px; display:block; padding:12px; background:white; margin-top:1rem; }
.portal-ticketing details summary { cursor:pointer; color:var(--text); font-weight:600; }
.portal-ticketing code { overflow-wrap:anywhere; }
.portal-ticketing .editor-board { background:var(--surface2); border:1px solid var(--border2); border-radius:var(--radius); padding:1rem; margin-top:1.25rem; overflow:auto; }
.portal-ticketing .stage { max-width:450px; margin:0 auto 30px; background:var(--accent-glow); color:var(--accent); text-align:center; padding:12px; font-size:.7rem; letter-spacing:.25em; border-radius:0 0 50% 50%; }
.portal-ticketing .seat-map { position:relative; margin:auto; }
.portal-ticketing .seat { position:absolute; width:34px; height:32px; border:2px solid var(--seat-color); border-radius:8px 8px 4px 4px; background:var(--surface); color:var(--text); font:600 10px 'Inter',sans-serif; cursor:pointer; padding:0; }
.portal-ticketing .seat.selected { background:var(--seat-color); color:white; box-shadow:0 0 0 2px var(--surface2),0 0 0 4px var(--seat-color); }
.portal-ticketing .class-editor-panel { background:var(--surface2); }
@media(max-width:1200px) { .portal-ticketing .split { grid-template-columns:1fr; } .portal-ticketing .grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:600px) { .portal-ticketing .grid,.portal-ticketing .form-grid { grid-template-columns:1fr; } .portal-ticketing .section-heading { align-items:flex-start; flex-wrap:wrap; } .portal-ticketing .panel { padding:1rem; } }
.portal-ticketing .venue-canvas { margin:0 auto; }
.portal-ticketing .venue-divider { position:absolute; left:0; right:0; height:32px; display:flex; align-items:center; gap:18px; font-weight:700; letter-spacing:.2em; }
.portal-ticketing .venue-divider::before,.portal-ticketing .venue-divider::after { content:""; flex:1; border-top:6px double var(--border); }
.portal-ticketing .venue-divider span { flex:none; }
</style>
