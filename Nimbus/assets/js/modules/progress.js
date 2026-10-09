// 顶部阅读进度线 + 返回顶部进度环
import { qs, on, throttle } from './util.js';
let unbind = null;
export function init() {
  destroy();
  const bar = qs('#n-progress');
  const ring = qs('.n-totop-fg');
  const update = () => {
    const h = document.documentElement.scrollHeight - window.innerHeight;
    const p = h > 0 ? Math.min(1, window.scrollY / h) : 0;
    if (bar) bar.style.width = (p * 100) + '%';
    if (ring) ring.style.strokeDashoffset = String(148 - 148 * p);
  };
  unbind = on(window, 'scroll', throttle(update, 50), { passive: true });
  update();
}
export function destroy() { if (unbind) unbind(); unbind = null; }
