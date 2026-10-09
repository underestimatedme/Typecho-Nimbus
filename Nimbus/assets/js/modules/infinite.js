// 加载更多 / 无限滚动：fetch 下一页，追加 #n-list 子节点
import { qs, on, config } from './util.js';
import * as reveal from './reveal.js';
let unbind = [], io = null, loading = false;

async function load(btn) {
  const next = btn.dataset.next;
  if (!next || loading) return;
  loading = true;
  const t = config().i18n || {};
  const label = btn.textContent;
  btn.classList.add('is-loading'); btn.textContent = t.loading || '加载中…';
  try {
    const r = await fetch(next, { headers: { 'X-Nimbus-Router': '1' } });
    if (!r.ok) throw new Error(r.status);
    const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
    const list = qs('#n-list'), items = doc.querySelector('#n-list');
    if (list && items) {
      const frag = document.createDocumentFragment();
      Array.from(items.children).forEach((c) => frag.appendChild(c));
      list.appendChild(frag);
      reveal.init(list);
      document.dispatchEvent(new CustomEvent('nimbus:append', { detail: { root: list } }));
    }
    const nb = doc.querySelector('[data-load-more]');
    if (nb && nb.dataset.next) { btn.dataset.next = nb.dataset.next; btn.textContent = label; }
    else { btn.textContent = t.noMore || '没有更多了'; btn.disabled = true; btn.removeAttribute('data-next'); if (io) io.disconnect(); }
  } catch (e) {
    btn.textContent = label;
  } finally {
    btn.classList.remove('is-loading'); loading = false;
  }
}

export function init(root = document) {
  destroy();
  const btn = qs('[data-load-more]', root);
  if (!btn) return;
  unbind.push(on(btn, 'click', () => load(btn)));
  if (btn.hasAttribute('data-infinite') && 'IntersectionObserver' in window) {
    io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting) && btn.dataset.next) load(btn); }, { rootMargin: '400px 0px' });
    io.observe(btn);
  }
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; if (io) io.disconnect(); io = null; loading = false; }
