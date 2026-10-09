// Mermaid 懒加载，皮肤切换时重渲染
import { qsa, config, loadScript, on } from './util.js';
let unbind = null, sources = new Map();
function isDark() { return document.documentElement.dataset.theme !== (config().themes || {}).light; }
async function render(root) {
  const nodes = qsa('.mermaid', root);
  if (!nodes.length) return;
  nodes.forEach((n) => { if (!sources.has(n)) sources.set(n, n.textContent); n.removeAttribute('data-processed'); n.innerHTML = ''; n.textContent = sources.get(n); });
  window.mermaid.initialize({ startOnLoad: false, theme: isDark() ? 'dark' : 'default', securityLevel: 'loose', fontFamily: 'inherit' });
  try { await window.mermaid.run({ nodes }); } catch (e) { if (config().debug) console.warn('[nimbus] mermaid', e); }
}
export async function init(root = document) {
  destroy();
  const cfg = config();
  const mode = cfg.mermaid || 'auto';
  if (mode === 'off' || (mode === 'auto' && !cfg.hasMermaid)) return;
  if (!qsa('.mermaid', root).length) return;
  try {
    await loadScript(cfg.assets + '/vendor/mermaid/mermaid.min.js');
    await render(root);
    unbind = on(document, 'nimbus:theme', () => render(root));
  } catch (e) { if (cfg.debug) console.warn('[nimbus] mermaid load', e); }
}
export function destroy() { if (unbind) unbind(); unbind = null; sources = new Map(); }
