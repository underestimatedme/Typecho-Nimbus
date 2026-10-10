// 灯箱：自写，支持上一张 / 下一张 / Esc / 点击遮罩
import { qs, qsa, on, tr } from './util.js';
let unbind = [], dlg = null, items = [], cur = 0;

function ensure() {
  if (dlg) return dlg;
  dlg = document.createElement('dialog');
  dlg.className = 'n-lightbox';
  dlg.innerHTML = '<img alt=""><button type="button" class="n-lightbox-close" aria-label="' + tr('关闭') + '"><svg class="n-ico" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg></button><button type="button" class="n-lightbox-prev" aria-label="' + tr('上一张') + '"><svg class="n-ico" viewBox="0 0 24 24"><path d="M19 12H5M11 6l-6 6 6 6"/></svg></button><button type="button" class="n-lightbox-next" aria-label="' + tr('下一张') + '"><svg class="n-ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button><div class="n-lightbox-count"></div>';
  document.body.appendChild(dlg);
  on(dlg, 'click', (e) => { if (e.target === dlg || e.target.tagName === 'IMG') close(); });
  on(qs('.n-lightbox-close', dlg), 'click', close);
  on(qs('.n-lightbox-prev', dlg), 'click', (e) => { e.stopPropagation(); show(cur - 1); });
  on(qs('.n-lightbox-next', dlg), 'click', (e) => { e.stopPropagation(); show(cur + 1); });
  on(document, 'keydown', (e) => { if (!dlg.open) return; if (e.key === 'ArrowLeft') show(cur - 1); if (e.key === 'ArrowRight') show(cur + 1); });
  return dlg;
}
function show(i) {
  cur = (i + items.length) % items.length;
  const img = qs('img', dlg);
  img.src = items[cur].href; img.alt = (items[cur].querySelector('img') || {}).alt || '';
  qs('.n-lightbox-count', dlg).textContent = (cur + 1) + ' / ' + items.length;
  dlg.toggleAttribute('data-single', items.length < 2);
  if (!dlg.open) dlg.showModal();
}
function close() { if (dlg && dlg.open) dlg.close(); }

export function init(root = document) {
  destroy();
  qsa('[data-lightbox]', root).forEach((a) => unbind.push(on(a, 'click', (e) => {
    e.preventDefault();
    // 同一 [data-gallery] 内的图片为一组；没有分组时整页为一组
    const group = a.closest('[data-gallery]');
    items = qsa('[data-lightbox]', group || document);
    ensure();
    show(Math.max(0, items.indexOf(a)));
  })));
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; close(); }
