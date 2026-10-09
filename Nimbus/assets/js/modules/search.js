// 搜索弹窗：Ctrl/Cmd+K 打开；无 Kit 时跳转原生搜索路径；有 Kit 时即时搜索
import { qs, qsa, on, config, debounce } from './util.js';
let unbind = [];
let bound = false;

function open() { const d = qs('[data-search]'); if (d && !d.open) { d.showModal(); const i = qs('[data-search-input]', d); i && setTimeout(() => i.focus(), 50); } }
function close() { const d = qs('[data-search]'); if (d && d.open) d.close(); }

export function init(root = document) {
  qsa('[data-search-open]', root).forEach((b) => { if (!b.dataset.bound) { b.dataset.bound = '1'; b.addEventListener('click', open); } });
  if (bound) return;
  bound = true;
  const d = qs('[data-search]');
  if (!d) return;
  const form = qs('[data-search-form]', d), input = qs('[data-search-input]', d), results = qs('[data-search-results]', d);
  const { index = '', api = null } = config();
  on(document, 'keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); d.open ? close() : open(); }
    if (e.key === '/' && !/input|textarea/i.test(document.activeElement.tagName) && !d.open) { e.preventDefault(); open(); }
  });
  qsa('[data-search-close]', d).forEach((b) => on(b, 'click', close));
  on(d, 'click', (e) => { if (e.target === d) close(); });
  on(form, 'submit', (e) => {
    e.preventDefault();
    const kw = input.value.trim();
    if (!kw) return;
    const active = results.querySelector('a.is-active');
    if (active) { location.href = active.href; return; }
    location.href = index + '/search/' + encodeURIComponent(kw) + '/';
  });
  if (api) {
    const run = debounce(async () => {
      const kw = input.value.trim();
      if (kw.length < 2) { results.hidden = true; results.innerHTML = ''; return; }
      try {
        const r = await fetch(api + '/search?q=' + encodeURIComponent(kw), { headers: { Accept: 'application/json' } });
        const data = await r.json();
        const list = Array.isArray(data.data) ? data.data : [];
        results.innerHTML = list.length ? list.map((p, i) => `<a href="${p.url}" class="${i === 0 ? 'is-active' : ''}">${p.title}<small>${p.excerpt || ''}</small></a>`).join('') : '<div class="n-search-hint">没有找到相关文章，回车使用站内搜索</div>';
        results.hidden = false;
      } catch (err) { results.hidden = true; }
    }, 200);
    on(input, 'input', run);
    on(input, 'keydown', (e) => {
      const items = qsa('a', results); if (!items.length) return;
      let i = items.findIndex((a) => a.classList.contains('is-active'));
      if (e.key === 'ArrowDown') { e.preventDefault(); i = (i + 1) % items.length; }
      else if (e.key === 'ArrowUp') { e.preventDefault(); i = (i - 1 + items.length) % items.length; }
      else return;
      items.forEach((a, k) => a.classList.toggle('is-active', k === i));
    });
  }
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
