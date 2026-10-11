// 文章列表浏览方式：分页 / 滚动（访客可切换，记在浏览器里）
import { qs, qsa, on, storage } from './util.js';
import * as infinite from './infinite.js';

const KEY = 'nimbus-list-mode';
let offs = [];

function apply(pager, mode, reinit) {
  pager.dataset.mode = mode;
  qsa('[data-list-mode]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.listMode === mode)));
  const btn = qs('[data-load-more]', pager);
  if (btn) btn.toggleAttribute('data-infinite', mode === 'scroll' && pager.dataset.auto === '1');
  if (reinit) { infinite.destroy(); infinite.init(document); }
}

export function init(root = document) {
  destroy();
  const pager = qs('[data-pager]', root);
  if (!pager) return;
  // 只有站长开启了切换（页面上有切换按钮）时才采用访客自己的选择，否则一律按后台设置
  const canSwitch = qsa('[data-list-mode]').length > 0;
  const saved = canSwitch ? storage.get(KEY) : null;
  const mode = saved === 'page' || saved === 'scroll' ? saved : pager.dataset.default;
  apply(pager, mode, mode !== pager.dataset.mode || (qs('[data-load-more]', pager) && mode === 'scroll'));
  qsa('[data-list-mode]', root).forEach((b) => offs.push(on(b, 'click', () => {
    const m = b.dataset.listMode;
    storage.set(KEY, m);
    apply(pager, m, true);
    // 切到滚动且按钮已在视野内：立刻加载一页，给出反馈
    if (m === 'scroll') { const more = qs('[data-load-more]', pager); if (more && more.getBoundingClientRect().top < innerHeight) more.click(); }
  })));
}

export function destroy() { offs.forEach((f) => f()); offs = []; }
