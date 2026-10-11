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
  // 筛选：文章 / 教程 / 项目；隐藏空年份，可见条目重新左右交错
  const btns = qsa('[data-tl-filter]', root);
  const filter = (kind) => {
    btns.forEach((b) => b.classList.toggle('is-on', b.dataset.tlFilter === kind));
    let k = 0;
    qsa('.n-tl-year', tl).forEach((year) => {
      let any = false;
      qsa('.n-tl-item', year).forEach((li) => {
        const show = kind === 'all' || li.dataset.type === kind;
        li.hidden = !show;
        if (show) { any = true; li.classList.toggle('is-left', k % 2 === 0); li.classList.toggle('is-right', k % 2 === 1); li.classList.add('is-in'); k++; }
      });
      year.hidden = !any;
      const n = qs('.n-tl-year-head small b', year);
      if (n) n.textContent = String(qsa('.n-tl-item:not([hidden])', year).length);
    });
    tick();
  };
  btns.forEach((b) => unbind.push(on(b, 'click', () => filter(b.dataset.tlFilter))));
  tick();
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
