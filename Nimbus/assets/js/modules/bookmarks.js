// 收藏夹：分组标签 + 即时过滤 + 卡片光斑跟随鼠标
import { qs, qsa, on } from './util.js';
let unbind = [];
export function init(root = document) {
  destroy();
  const box = qs('[data-bookmarks]', root);
  if (!box) return;
  const tabs = qsa('[data-bm-tab]', box), input = qs('[data-bm-filter]', box), none = qs('.n-bm-none', box);
  let group = '';
  const apply = () => {
    const q = (input ? input.value : '').trim().toLowerCase();
    let shown = 0;
    qsa('[data-bm-group]', box).forEach((g) => {
      let n = 0;
      qsa('.n-bm', g).forEach((a) => {
        const ok = (!group || g.dataset.bmGroup === group) && (!q || a.dataset.bmText.includes(q));
        a.classList.toggle('is-hidden', !ok);
        if (ok) { n++; a.classList.add('is-in'); }
      });
      g.classList.toggle('is-hidden', n === 0);
      shown += n;
    });
    if (none) none.hidden = shown > 0;
  };
  tabs.forEach((t) => unbind.push(on(t, 'click', () => { group = t.dataset.bmTab; tabs.forEach((x) => x.classList.toggle('is-on', x === t)); apply(); })));
  if (input) unbind.push(on(input, 'input', apply));
  qsa('.n-bm', box).forEach((a) => unbind.push(on(a, 'pointermove', (e) => {
    const r = a.getBoundingClientRect();
    a.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    a.style.setProperty('--my', (e.clientY - r.top) + 'px');
  })));
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
