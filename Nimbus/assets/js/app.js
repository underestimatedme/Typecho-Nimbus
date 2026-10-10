// Nimbus 前端入口：读取配置，初始化全局模块，按页面懒加载页面模块。
import * as theme from './modules/theme.js';
import * as nav from './modules/nav.js';
import * as progress from './modules/progress.js';
import * as search from './modules/search.js';
import * as reveal from './modules/reveal.js';
import * as count from './modules/count.js';
import * as menuFit from './modules/menu-fit.js';

function readConfig() {
  const el = document.getElementById('n-config');
  try { window.NimbusConfig = el ? JSON.parse(el.textContent) : {}; } catch (e) { window.NimbusConfig = {}; }
  return window.NimbusConfig;
}

let pageModule = null;

async function boot(root = document) {
  const cfg = readConfig();
  if (cfg.debug) console.table(cfg);
  theme.init(root);
  nav.init();
  menuFit.init();
  progress.init();
  search.init(root);
  reveal.init(root);
  count.init(root);
  if (pageModule && pageModule.destroy) { try { pageModule.destroy(); } catch (e) {} }
  pageModule = null;
  const page = (document.body.dataset.page || 'index');
  try {
    pageModule = await import(`./pages/${page}.js`);
    if (pageModule.init) await pageModule.init(root);
  } catch (e) {
    if (cfg.debug) console.warn('[nimbus] page module', page, e);
  }
}

document.addEventListener('nimbus:page', (e) => boot(e.detail && e.detail.root ? e.detail.root : document));
boot();

const cfg = window.NimbusConfig || {};
if (cfg.router && 'fetch' in window) {
  import('./modules/router.js').then((m) => m.init()).catch(() => {});
}
