// 纵向时间线：中轴随滚动填充，经过的节点点亮
import { qs, qsa, on } from './util.js';
let unbind = [];
export function init(root = document) {
  destroy();
  const tl = qs('[data-river]', root);
  if (!tl) return;
  const fill = qs('[data-tl-fill]', tl), dots = qsa('.n-tl-item', tl);
  let raf = 0;
  const tick = () => {
    raf = 0;
    const r = tl.getBoundingClientRect(), mid = innerHeight * 0.55;
    const p = Math.min(1, Math.max(0, (mid - r.top) / r.height));
    if (fill) fill.style.transform = `scaleY(${p})`;
    dots.forEach((d) => d.classList.toggle('is-passed', d.getBoundingClientRect().top < mid));
  };
  unbind.push(on(window, 'scroll', () => { if (!raf) raf = requestAnimationFrame(tick); }, { passive: true }));
  unbind.push(on(window, 'resize', () => { if (!raf) raf = requestAnimationFrame(tick); }));
  tick();
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
