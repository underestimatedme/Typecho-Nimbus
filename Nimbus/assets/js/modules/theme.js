// 皮肤切换：按钮、圆形扩散过渡、theme-color 同步
import { qsa, storage, config, prefersReduced } from './util.js';

const KEY = 'nimbus-theme';
let bound = false;

export function current() { return document.documentElement.dataset.theme; }

export function apply(theme, origin) {
  const root = document.documentElement;
  const run = () => { root.dataset.theme = theme; syncMeta(); };
  if (document.startViewTransition && !prefersReduced() && origin) {
    root.style.setProperty('--vt-x', origin.x + 'px');
    root.style.setProperty('--vt-y', origin.y + 'px');
    root.classList.add('n-vt-theme');
    const t = document.startViewTransition(run);
    t.finished.finally(() => root.classList.remove('n-vt-theme'));
  } else {
    run();
  }
  storage.set(KEY, theme);
  document.dispatchEvent(new CustomEvent('nimbus:theme', { detail: { theme } }));
}

export function toggle(origin) {
  const { themes = {} } = config();
  const light = themes.light || 'sky', dark = themes.dark || 'dusk';
  apply(current() === light ? dark : light, origin);
}

function syncMeta() {
  const bg = getComputedStyle(document.documentElement).getPropertyValue('--bg').trim();
  let m = document.querySelector('meta[name="theme-color"]');
  if (!m) { m = document.createElement('meta'); m.name = 'theme-color'; document.head.appendChild(m); }
  if (bg) m.content = bg;
}

export function init(root = document) {
  qsa('[data-theme-toggle]', root).forEach((btn) => {
    if (btn.dataset.bound) return;
    btn.dataset.bound = '1';
    btn.addEventListener('click', (e) => {
      const r = btn.getBoundingClientRect();
      toggle({ x: r.left + r.width / 2, y: r.top + r.height / 2 });
    });
  });
  if (!bound) {
    bound = true;
    syncMeta();
    const { themes = {} } = config();
    if (themes.follow && !storage.get(KEY)) {
      matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        document.documentElement.dataset.theme = e.matches ? (themes.dark || 'dusk') : (themes.light || 'sky');
        syncMeta();
      });
    }
  }
}
