// 横向时间线：滚轮转横向、拖拽、方向键、左右按钮
import { qs, on } from './util.js';
let unbind = [];
export function init(root = document) {
  destroy();
  const river = qs('[data-river]', root);
  if (!river) return;
  const prev = qs('[data-river-prev]', root), next = qs('[data-river-next]', root);
  const horizontal = () => getComputedStyle(river).overflowX === 'auto';
  const sync = () => {
    if (!prev || !next) return;
    prev.disabled = river.scrollLeft <= 4;
    next.disabled = river.scrollLeft + river.clientWidth >= river.scrollWidth - 4;
  };
  const step = () => Math.max(280, river.clientWidth * 0.7);
  unbind.push(on(river, 'wheel', (e) => {
    if (!horizontal() || Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
    const atStart = river.scrollLeft <= 0 && e.deltaY < 0, atEnd = river.scrollLeft + river.clientWidth >= river.scrollWidth - 1 && e.deltaY > 0;
    if (atStart || atEnd) return; // 到头后让页面继续纵向滚动
    e.preventDefault();
    river.scrollLeft += e.deltaY;
  }, { passive: false }));
  let down = null, moved = false;
  unbind.push(on(river, 'pointerdown', (e) => { if (!horizontal() || e.pointerType !== 'mouse') return; down = { x: e.clientX, left: river.scrollLeft }; moved = false; }));
  unbind.push(on(window, 'pointermove', (e) => {
    if (!down) return;
    const dx = e.clientX - down.x;
    if (Math.abs(dx) > 4) { moved = true; river.classList.add('is-dragging'); }
    river.scrollLeft = down.left - dx;
  }));
  unbind.push(on(window, 'pointerup', () => { down = null; setTimeout(() => river.classList.remove('is-dragging'), 0); }));
  // 拖拽结束时不触发卡片点击
  unbind.push(on(river, 'click', (e) => { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true));
  unbind.push(on(river, 'keydown', (e) => {
    if (e.key === 'ArrowRight') { e.preventDefault(); river.scrollLeft += step(); }
    if (e.key === 'ArrowLeft') { e.preventDefault(); river.scrollLeft -= step(); }
  }));
  if (prev) unbind.push(on(prev, 'click', () => { river.scrollLeft -= step(); }));
  if (next) unbind.push(on(next, 'click', () => { river.scrollLeft += step(); }));
  unbind.push(on(river, 'scroll', sync, { passive: true }));
  sync();
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
