// 无刷新页面切换：fetch + View Transitions，失败回退整页跳转
import { qs, config, prefersReduced } from './util.js';
import { shouldIntercept } from './router-rules.js';

let current = null;

function progressStart() { const b = qs('#n-progress'); if (b) { b.style.transition = 'width 8s cubic-bezier(.1,.6,.3,1)'; b.style.width = '70%'; } }
function progressEnd() { const b = qs('#n-progress'); if (b) { b.style.transition = 'width .2s'; b.style.width = '100%'; setTimeout(() => { b.style.transition = ''; b.style.width = '0'; }, 250); } }

function swap(doc, url) {
  const newMain = doc.querySelector('#n-main'), oldMain = qs('#n-main');
  if (!newMain || !oldMain) throw new Error('no main');
  document.title = doc.title;
  const nb = doc.body, ob = document.body;
  ob.className = nb.className; ob.dataset.page = nb.dataset.page || 'index'; ob.dataset.cid = nb.dataset.cid || '0';
  const cfg = doc.getElementById('n-config'), oc = document.getElementById('n-config');
  if (cfg && oc) oc.textContent = cfg.textContent;
  // 导航（实色 / 透明）与 Hero 位于 main 之外，整体替换 header
  [['#n-nav'], ['[data-nav-float]'], ['#n-drawer']].forEach(([sel]) => {
    const n = doc.querySelector(sel), o = qs(sel);
    if (n && o) o.replaceWith(n);
  });
  oldMain.replaceWith(newMain);
  // 执行新内容里的内联脚本
  newMain.querySelectorAll('script').forEach((s) => { const n = document.createElement('script'); [...s.attributes].forEach((a) => n.setAttribute(a.name, a.value)); n.textContent = s.textContent; s.replaceWith(n); });
  // 皮肤相关 meta
  const vt = doc.querySelector('meta[name="theme-color"]');
  return newMain;
}

export async function navigate(url, push = true) {
  if (current) current.abort();
  const ctrl = new AbortController(); current = ctrl;
  progressStart();
  // 服务器响应太慢时不让用户干等：4 秒内拿不到响应就改为普通跳转
  let slow = false;
  const timer = setTimeout(() => { slow = true; ctrl.abort(); }, 4000);
  try {
    const r = await fetch(url, { headers: { 'X-Nimbus-Router': '1', Accept: 'text/html' }, signal: ctrl.signal, credentials: 'same-origin' });
    const type = r.headers.get('content-type') || '';
    if (!r.ok && r.status !== 404) throw new Error('status ' + r.status);
    if (!type.includes('text/html')) throw new Error('not html');
    clearTimeout(timer);
    const html = await r.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    if (!doc.querySelector('#n-main')) throw new Error('foreign page');
    const finalUrl = r.url || url;
    const run = () => { swap(doc, finalUrl); };
    if (document.startViewTransition && !prefersReduced()) {
      document.documentElement.classList.add('n-vt-page');
      const t = document.startViewTransition(run);
      t.ready.catch(() => {});
      await t.finished.catch(() => {});
      document.documentElement.classList.remove('n-vt-page');
    } else { run(); }
    if (push) history.pushState({ nimbus: true }, '', finalUrl);
    const hash = new URL(finalUrl, location.origin).hash;
    const target = hash ? document.getElementById(hash.slice(1)) : null;
    if (target) target.scrollIntoView(); else window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
    document.dispatchEvent(new CustomEvent('nimbus:page', { detail: { root: document, url: finalUrl } }));
  } catch (e) {
    clearTimeout(timer);
    if (e.name === 'AbortError' && !slow) return;
    if (config().debug) console.warn('[nimbus] router fallback', e);
    location.href = url;
    return;
  } finally {
    progressEnd();
    if (current === ctrl) current = null;
  }
}

export function init() {
  if (window.__nimbusRouter) return;
  window.__nimbusRouter = true;
  history.replaceState({ nimbus: true }, '', location.href);
  const adminPath = (() => { try { return new URL(config().admin || '/admin/', location.origin).pathname; } catch (e) { return '/admin/'; } })();
  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const a = e.target.closest('a[href]');
    if (!a) return;
    if (!shouldIntercept(a, location, adminPath)) return;
    e.preventDefault();
    navigate(a.href, true);
  });
  window.addEventListener('popstate', () => navigate(location.href, false));
}
