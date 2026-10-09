// Hero：打字机副标题 + 滚动视差
import { qs, on, prefersReduced, decorAllowed } from './util.js';
let timer = null, unbind = null;

export function init(root = document) {
  destroy();
  const hero = qs('[data-hero]', root);
  if (!hero) return;
  const sub = qs('[data-typewriter]', hero);
  if (sub && !prefersReduced()) {
    let lines = [];
    try { lines = JSON.parse(sub.dataset.typewriter); } catch (e) {}
    lines = lines.filter((s) => typeof s === 'string' && s.trim());
    if (lines.length) {
      const el = sub.querySelector('span');
      let li = 0, ci = lines[0].length, del = false, wait = 0;
      const tick = () => {
        if (wait > 0) { wait--; return; }
        const s = lines[li];
        if (!del) { ci++; if (ci >= s.length) { ci = s.length; del = true; wait = lines.length > 1 ? 18 : 1e9; } }
        else { ci--; if (ci <= 0) { ci = 0; del = false; li = (li + 1) % lines.length; wait = 4; } }
        el.textContent = s.slice(0, ci);
      };
      timer = setInterval(tick, 80);
    }
  }
  const bg = qs('.n-hero-bg', hero);
  if (bg && decorAllowed()) {
    const h = hero.offsetHeight;
    let ticking = false;
    unbind = on(window, 'scroll', () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => { const y = window.scrollY; if (y < h) bg.style.translate = '0 ' + (y * 0.25) + 'px'; ticking = false; });
    }, { passive: true });
  }
}
export function destroy() { if (timer) clearInterval(timer); timer = null; if (unbind) unbind(); unbind = null; }
