// 置顶轮播：自动切换、指示点、箭头、hover 暂停、触摸滑动
import { qsa, on } from './util.js';
let instances = [];

function setup(el) {
  const slides = qsa('.n-slide', el), dots = qsa('[data-dot]', el);
  if (slides.length < 2) return null;
  const ms = parseInt(el.dataset.interval || '5000', 10);
  el.style.setProperty('--carousel-ms', ms + 'ms');
  let cur = 0, timer = null, offs = [];
  const go = (i) => {
    cur = (i + slides.length) % slides.length;
    slides.forEach((s, k) => { s.classList.toggle('is-on', k === cur); s.setAttribute('aria-hidden', k === cur ? 'false' : 'true'); });
    dots.forEach((d, k) => { d.classList.remove('is-on'); void d.offsetWidth; if (k === cur) d.classList.add('is-on'); });
    restart();
  };
  const restart = () => { clearInterval(timer); timer = setInterval(() => go(cur + 1), ms); };
  dots.forEach((d, k) => offs.push(on(d, 'click', () => go(k))));
  const prev = el.querySelector('[data-prev]'), next = el.querySelector('[data-next]');
  prev && offs.push(on(prev, 'click', (e) => { e.preventDefault(); go(cur - 1); }));
  next && offs.push(on(next, 'click', (e) => { e.preventDefault(); go(cur + 1); }));
  offs.push(on(el, 'mouseenter', () => { clearInterval(timer); el.classList.add('is-paused'); }));
  offs.push(on(el, 'mouseleave', () => { el.classList.remove('is-paused'); restart(); }));
  let touchStart = null, suppressClick = false, clickTimer = null;
  offs.push(on(el, 'touchstart', (e) => {
    if (!e.touches || e.touches.length !== 1) { touchStart = null; return; }
    touchStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
  }, { passive: true }));
  offs.push(on(el, 'touchend', (e) => {
    if (!touchStart || !e.changedTouches || !e.changedTouches.length) { touchStart = null; return; }
    const dx = e.changedTouches[0].clientX - touchStart.x;
    const dy = e.changedTouches[0].clientY - touchStart.y;
    touchStart = null;
    if (Math.abs(dx) <= 40 || Math.abs(dx) <= Math.abs(dy)) return;
    go(cur + (dx < 0 ? 1 : -1));
    suppressClick = true;
    clearTimeout(clickTimer);
    clickTimer = setTimeout(() => { suppressClick = false; }, 500);
  }));
  offs.push(on(el, 'click', (e) => {
    if (!suppressClick) return;
    suppressClick = false;
    clearTimeout(clickTimer);
    e.preventDefault();
    e.stopImmediatePropagation();
  }, true));
  restart();
  return { destroy() { clearInterval(timer); clearTimeout(clickTimer); offs.forEach((f) => f()); } };
}

export function init(root = document) {
  destroy();
  qsa('[data-carousel]', root).forEach((el) => { const i = setup(el); if (i) instances.push(i); });
}
export function destroy() { instances.forEach((i) => i.destroy()); instances = []; }
