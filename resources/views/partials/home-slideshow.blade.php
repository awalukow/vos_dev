@push('head')
<style>
  #section-home.choir-slideshow { overflow: hidden; background: #272432; min-height: 680px; }
  .choir-slides, .choir-slides::after, .choir-slide { position: absolute; inset: 0; width: 100%; height: 100%; }
  .choir-slide { object-fit: cover; opacity: 0; transition: opacity 1.2s ease; }
  .choir-slide.is-active { opacity: 1; }
  .choir-slides::after { content: ''; background: linear-gradient(180deg, rgba(20,19,30,.45), rgba(20,19,30,.5) 65%, rgba(20,19,30,.7)); }
  #section-home > .container { z-index: 1; }
  #section-home .choir-controls { position: absolute; z-index: 2; bottom: 28px; left: 0; right: 0; display: flex; align-items: center; justify-content: center; gap: 16px; }
  #section-home .choir-controls[hidden] { display: none; }
  .choir-controls button { display: inline-flex; align-items: center; justify-content: center; min-width: 44px; height: 44px; border: 1px solid #ffffff70; border-radius: 50%; color: #fff; background: #17132150; cursor: pointer; font: inherit; }
  .choir-controls button:hover { background: #ffffff30; }
  .choir-controls button:focus-visible { outline: 2px solid white; outline-offset: 4px; }
  .choir-controls button[data-slide-pause] { border-radius: 24px; padding: 0 16px; font-size: 12px; min-width: 80px; }
  .choir-dots { display: flex; }
  .choir-dots button { width: 32px; min-width: 32px; border: 0; background: transparent; }
  .choir-dots button::after { content: ''; width: 8px; height: 8px; border: 1px solid white; border-radius: 50%; }
  .choir-dots button[aria-current=true]::after { background: white; }
  @media(max-width:767px) { #section-home.choir-slideshow { padding: 110px 0 100px; min-height: 640px; } #section-home > .container > .row { height: auto; padding: 20px 0; } #section-home .heading { font-size: 42px; } #section-home .sub-heading { font-size: 17px; } #section-home .choir-controls { gap: 8px; bottom: 22px; } }
  @media(prefers-reduced-motion:reduce) { .choir-slide { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const hero = document.querySelector('.choir-slideshow');
    if (!hero) return;
    const slides = [...hero.querySelectorAll('.choir-slide')];
    const dots = [...hero.querySelectorAll('[data-slide-to]')];
    const pause = hero.querySelector('[data-slide-pause]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0;
    let paused = reducedMotion.matches;
    let hovered = false;
    let timer;

    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current));
        dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === current)));
    }
    function schedule() {
        window.clearInterval(timer);
        if (!paused && !hovered && !document.hidden) timer = window.setInterval(() => show(current + 1), 6000);
        pause.textContent = paused ? 'Play' : 'Pause';
        pause.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
    }
    function choose(index) {
        paused = true;
        show(index);
        schedule();
    }
    hero.querySelector('[data-slide-prev]').addEventListener('click', () => choose(current - 1));
    hero.querySelector('[data-slide-next]').addEventListener('click', () => choose(current + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => choose(i)));
    pause.addEventListener('click', () => { paused = !paused; schedule(); });
    hero.addEventListener('mouseenter', () => { hovered = true; schedule(); });
    hero.addEventListener('mouseleave', () => { hovered = false; schedule(); });
    // Keyboard focus stops rotation until the visitor explicitly starts it again.
    hero.addEventListener('focusin', () => { paused = true; schedule(); });
    document.addEventListener('visibilitychange', schedule);
    reducedMotion.addEventListener('change', () => { paused = reducedMotion.matches; schedule(); });
    hero.querySelector('.choir-controls').hidden = false;
    schedule();
})();
</script>
@endpush
