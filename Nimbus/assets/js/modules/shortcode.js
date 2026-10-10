// 短代码前端：标签页切换、GitHub 仓库卡片（读取公开 API，本地缓存 6 小时）
import { qsa, on, storage } from './util.js';
let unbind = [];
const TTL = 6 * 3600 * 1000;

function fmt(n) { return n >= 1000 ? (n / 1000).toFixed(n >= 10000 ? 0 : 1) + 'k' : String(n); }

async function github(card) {
  const repo = card.dataset.ghRepo, key = 'nimbus-gh-' + repo;
  let data = null;
  try { const c = JSON.parse(storage.get(key) || 'null'); if (c && Date.now() - c.t < TTL) data = c.d; } catch (e) {}
  if (!data) {
    try {
      const r = await fetch('https://api.github.com/repos/' + repo, { headers: { Accept: 'application/vnd.github+json' } });
      if (!r.ok) throw new Error(r.status);
      const j = await r.json();
      data = { desc: j.description || '', stars: j.stargazers_count || 0, forks: j.forks_count || 0, lang: j.language || '' };
      storage.set(key, JSON.stringify({ t: Date.now(), d: data }));
    } catch (e) { card.classList.add('is-offline'); return; }
  }
  const set = (sel, v) => { const el = card.querySelector(sel); if (el) el.textContent = v; };
  if (data.desc) set('[data-gh-desc]', data.desc);
  set('[data-gh-stars]', fmt(data.stars));
  set('[data-gh-forks]', fmt(data.forks));
  set('[data-gh-lang]', data.lang);
  card.classList.add('is-loaded');
}

export function init(root = document) {
  destroy();
  qsa('[data-tabs]', root).forEach((box) => {
    const btns = qsa(':scope > .n-sc-tabs-nav > button', box), panels = qsa(':scope > .n-sc-tab-panel', box);
    const show = (i) => {
      btns.forEach((b, k) => { b.classList.toggle('is-on', k === i); b.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
      panels.forEach((p, k) => p.classList.toggle('is-on', k === i));
    };
    btns.forEach((b, i) => unbind.push(on(b, 'click', () => show(i))));
    box.classList.add('is-ready');
    show(0);
  });
  qsa('[data-gh-repo]', root).forEach(github);
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
