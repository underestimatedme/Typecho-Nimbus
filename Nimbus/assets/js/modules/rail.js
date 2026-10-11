// 杂志风文章页右侧操作栏：生成海报、回到顶部、阅读进度
import { qs, on } from './util.js';

let offs = [];

export function init(root = document) {
  destroy();
  const rail = qs('.n-rail', root);
  if (!rail) return;
  const poster = qs('[data-rail-poster]', rail);
  if (poster) offs.push(on(poster, 'click', () => { const b = qs('[data-share-poster]'); if (b) b.click(); }));
  const top = qs('[data-rail-top]', rail);
  if (top) offs.push(on(top, 'click', () => window.scrollTo({ top: 0, behavior: 'smooth' })));
  const bar = qs('[data-rail-progress]', rail), prose = qs('#n-prose');
  if (bar && prose) {
    let raf = 0;
    const tick = () => {
      raf = 0;
      const r = prose.getBoundingClientRect(), h = r.height - innerHeight * 0.6;
      const p = h > 0 ? Math.min(1, Math.max(0, -r.top / h)) : 1;
      bar.style.transform = `scaleY(${p})`;
    };
    offs.push(on(window, 'scroll', () => { if (!raf) raf = requestAnimationFrame(tick); }, { passive: true }));
    tick();
  }
}

export function destroy() { offs.forEach((f) => f()); offs = []; }
