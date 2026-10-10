// 代码块：Prism 高亮、头部（语言 / 复制）、折叠展开、行号
import { qsa, config, loadScript, toast, tr } from './util.js';

function copyText(text) {
  if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
  return new Promise((resolve, reject) => {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); resolve(); } catch (e) { reject(e); }
    ta.remove();
  });
}

function decorate(pre) {
  if (pre.dataset.decorated) return;
  pre.dataset.decorated = '1';
  const cfg = config();
  const code = pre.querySelector('code');
  const head = document.createElement('div');
  head.className = 'n-code-head';
  head.innerHTML = '<span class="n-code-dots"><i></i><i></i><i></i></span><span class="n-code-lang">' + (pre.dataset.lang || 'code') + '</span><button type="button" class="n-code-copy"><svg class="n-ico" viewBox="0 0 24 24"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg><span>' + tr('复制') + '</span></button>';
  pre.prepend(head);
  const btn = head.querySelector('.n-code-copy');
  btn.addEventListener('click', () => {
    copyText(code ? code.textContent : pre.textContent).then(() => {
      btn.classList.add('is-done'); btn.querySelector('span').textContent = tr('已复制');
      setTimeout(() => { btn.classList.remove('is-done'); btn.querySelector('span').textContent = tr('复制'); }, 1600);
    }).catch(() => toast(tr('复制失败，请手动选择')));
  });
  if (pre.hasAttribute('data-fold')) {
    const ex = document.createElement('button');
    ex.type = 'button'; ex.className = 'n-code-expand'; ex.textContent = tr('展开全部 · {n} 行', { n: pre.dataset.lines });
    ex.addEventListener('click', () => { const open = pre.classList.toggle('is-open'); ex.textContent = open ? tr('收起') : tr('展开全部 · {n} 行', { n: pre.dataset.lines }); });
    pre.appendChild(ex);
  }
  if (cfg.codeLines !== false && code) pre.classList.add('line-numbers');
}

export async function init(root = document) {
  const pres = qsa('#n-prose pre', root);
  if (!pres.length) return;
  pres.forEach(decorate);
  const cfg = config();
  try {
    await loadScript(cfg.assets + '/vendor/prism/prism.js');
    if (window.Prism) pres.forEach((pre) => { const c = pre.querySelector('code'); if (c) window.Prism.highlightElement(c); });
  } catch (e) { if (cfg.debug) console.warn('[nimbus] prism', e); }
}
export function destroy() {}
