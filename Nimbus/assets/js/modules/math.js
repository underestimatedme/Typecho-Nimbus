// KaTeX 懒加载
import { config, loadScript, loadCss } from './util.js';
export async function init(root = document) {
  const cfg = config();
  const mode = cfg.math || 'auto';
  if (mode === 'off' || (mode === 'auto' && !cfg.hasMath)) return;
  const prose = root.querySelector('#n-prose') || root;
  try {
    const base = cfg.assets + '/vendor/katex/';
    await loadCss(base + 'katex.min.css');
    await loadScript(base + 'katex.min.js');
    await loadScript(base + 'auto-render.min.js');
    window.renderMathInElement(prose, {
      delimiters: [{ left: '$$', right: '$$', display: true }, { left: '\\(', right: '\\)', display: false }, { left: '\\[', right: '\\]', display: true }],
      ignoredTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
      throwOnError: false,
    });
  } catch (e) { if (cfg.debug) console.warn('[nimbus] katex', e); }
}
export function destroy() {}
