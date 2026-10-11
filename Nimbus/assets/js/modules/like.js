// 点赞：乐观更新 + 爱心飞散动效；已赞状态记在 localStorage
import { qsa, on, config, storage, toast, prefersReduced, tr } from './util.js';
let unbind = [];

function burst(btn) {
  if (prefersReduced()) return;
  const r = btn.getBoundingClientRect();
  for (let i = 0; i < 8; i++) {
    const s = document.createElement('i');
    s.className = 'n-like-burst';
    const a = (Math.PI * 2 * i) / 8 + Math.random() * 0.5;
    const d = 26 + Math.random() * 22;
    s.style.left = r.left + r.width / 2 + 'px';
    s.style.top = r.top + r.height / 2 + 'px';
    s.style.setProperty('--dx', Math.cos(a) * d + 'px');
    s.style.setProperty('--dy', Math.sin(a) * d - 10 + 'px');
    document.body.appendChild(s);
    setTimeout(() => s.remove(), 750);
  }
}

export function init(root = document) {
  if (root === document) destroy();
  const { api, features = [] } = config();
  if (!api || !features.includes('likes')) return;
  qsa('[data-like]', root).forEach((btn) => {
    if (btn.dataset.likeBound) return;
    btn.dataset.likeBound = '1';
    const id = btn.dataset.like, type = btn.dataset.likeType || 'post';
    const key = `nimbus-liked-${type}-${id}`;
    const count = btn.querySelector('[data-like-count]');
    if (storage.get(key)) btn.classList.add('is-on');
    // 同一篇文章可能有多个点赞按钮（文末 + 侧边操作栏），状态与计数保持一致
    const peers = () => qsa(`[data-like="${id}"]`).filter((b) => (b.dataset.likeType || 'post') === type);
    const setAll = (on, n) => peers().forEach((b) => { b.classList.toggle('is-on', on); const c = b.querySelector('[data-like-count]'); if (c && n !== null) c.textContent = String(n); });
    unbind.push(on(btn, 'click', async () => {
      burst(btn);
      if (btn.classList.contains('is-on') || btn.dataset.busy) return;
      btn.dataset.busy = '1';
      const before = count ? parseInt(count.textContent, 10) || 0 : 0;
      setAll(true, before + 1);
      try {
        const r = await fetch(api + '/like', { method: 'POST', body: new URLSearchParams({ cid: id, type }), credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!d.ok) throw new Error(d.error || tr('点赞失败'));
        setAll(true, d.data.likes);
        storage.set(key, '1');
      } catch (e) {
        setAll(false, before);
        toast(tr('点赞失败，请稍后再试'));
      } finally {
        delete btn.dataset.busy;
      }
    }));
  });
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; document.querySelectorAll('[data-like-bound]').forEach((b) => delete b.dataset.likeBound); }
