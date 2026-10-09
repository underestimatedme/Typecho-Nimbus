// 目录：滚动高亮、点击平滑滚动、移动端抽屉、阅读进度
import { qs, qsa, on, throttle } from './util.js';
let unbind = [];
export function init(root = document) {
  destroy();
  const toc = qs('[data-toc]'), prose = qs('#n-prose');
  if (!toc || !prose) return;
  const links = qsa('[data-toc-link]', toc);
  const heads = links.map((a) => document.getElementById(a.dataset.tocLink)).filter(Boolean);
  const bar = qs('.n-toc-progress i', toc);
  let active = null;
  const update = () => {
    const y = window.scrollY + 110;
    let idx = -1;
    heads.forEach((h, i) => { if (h.offsetTop <= y) idx = i; });
    const a = idx >= 0 ? links[idx] : null;
    if (a !== active) {
      links.forEach((l) => l.classList.remove('is-active'));
      if (a) { a.classList.add('is-active'); const box = toc.querySelector('.n-toc-in'); if (box && box.scrollHeight > box.clientHeight) a.scrollIntoView({ block: 'nearest' }); }
      active = a;
    }
    if (bar) {
      const start = prose.offsetTop, end = start + prose.offsetHeight - window.innerHeight * 0.6;
      bar.style.width = Math.max(0, Math.min(1, (window.scrollY - start) / Math.max(1, end - start))) * 100 + '%';
    }
  };
  unbind.push(on(window, 'scroll', throttle(update, 60), { passive: true }));
  update();
  links.forEach((a) => unbind.push(on(a, 'click', (e) => {
    const h = document.getElementById(a.dataset.tocLink);
    if (!h) return;
    e.preventDefault();
    window.scrollTo({ top: h.offsetTop - 90, behavior: 'smooth' });
    history.replaceState(null, '', '#' + a.dataset.tocLink);
    toc.classList.remove('is-open');
  })));
  const fab = qs('[data-toc-fab]');
  if (fab) {
    unbind.push(on(fab, 'click', () => toc.classList.toggle('is-open')));
    unbind.push(on(toc, 'click', (e) => { if (e.target === toc) toc.classList.remove('is-open'); }));
  }
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
