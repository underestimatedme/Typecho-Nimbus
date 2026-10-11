// 教程进度：读过的章节记在浏览器里（不需要登录），卡片 / 目录 / 章节导航显示进度与「继续学习」
import { qs, qsa, storage, tr } from './util.js';

const KEY = 'nimbus-tut';
const load = () => { try { const v = JSON.parse(storage.get(KEY) || '{}'); return v && typeof v === 'object' ? v : {}; } catch (e) { return {}; } };

export function init(root = document) {
  const state = load();
  // 当前章节记为已读
  const cur = qs('[data-tut-current]', root);
  if (cur) {
    try {
      const d = JSON.parse(cur.dataset.tutorial);
      const cid = +cur.dataset.tutCurrent;
      const set = new Set(state[d.slug] || []);
      if (!set.has(cid)) { set.add(cid); state[d.slug] = [...set].slice(-500); storage.set(KEY, JSON.stringify(state)); }
    } catch (e) {}
  }
  qsa('[data-tutorial]', root).forEach((el) => {
    let d;
    try { d = JSON.parse(el.dataset.tutorial); } catch (e) { return; }
    const read = new Set(state[d.slug] || []);
    const total = d.ch.length;
    const done = d.ch.filter(([cid]) => read.has(cid)).length;
    qsa('[data-tut-cid]', el).forEach((li) => li.classList.toggle('is-read', read.has(+li.dataset.tutCid)));
    const bar = qs('[data-tut-bar]', el);
    if (bar) bar.style.width = (total ? done / total * 100 : 0) + '%';
    const txt = qs('[data-tut-done]', el);
    if (txt) txt.textContent = done === 0 ? tr('还没开始') : done >= total ? tr('已学完 · 共 {n} 章', { n: total }) : tr('已学 {d} / {n} 章', { d: done, n: total });
    const go = qs('[data-tut-go]', el);
    if (go && total) {
      const idx = d.ch.findIndex(([cid]) => !read.has(cid));
      const label = go.firstChild;
      if (done === 0) { go.href = d.ch[0][1]; }
      else if (idx === -1) { go.href = d.ch[0][1]; if (label) label.textContent = tr('重新学习'); }
      else { go.href = d.ch[idx][1]; if (label) label.textContent = tr('继续第 {i} 章', { i: idx + 1 }); }
    }
    el.classList.toggle('is-started', done > 0);
  });
}

export function destroy() {}
