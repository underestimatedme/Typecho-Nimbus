// 分享菜单、复制链接、打赏弹窗
import { qs, qsa, on, toast, config, tr } from './util.js';
let unbind = [];
function copy(text) {
  const done = () => toast(tr('链接已复制'));
  if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text).then(done).catch(() => fallback(text, done));
  return fallback(text, done);
}
function fallback(text, done) {
  const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
  document.body.appendChild(ta); ta.select();
  try { document.execCommand('copy'); done(); } catch (e) { toast(tr('复制失败')); }
  ta.remove();
}
export function init(root = document) {
  destroy();
  qsa('[data-share]', root).forEach((box) => {
    const t = qs('[data-share-toggle]', box);
    t && unbind.push(on(t, 'click', (e) => { e.stopPropagation(); box.classList.toggle('is-open'); }));
    const c = qs('[data-share-copy]', box);
    c && unbind.push(on(c, 'click', () => { copy(box.dataset.url); box.classList.remove('is-open'); }));
    const p = qs('[data-share-poster]', box);
    p && unbind.push(on(p, 'click', () => {
      box.classList.remove('is-open');
      import('./poster.js').then((m) => m.open(box)).catch(() => toast(tr('海报生成失败')));
    }));
  });
  unbind.push(on(document, 'click', () => qsa('.n-share.is-open').forEach((b) => b.classList.remove('is-open'))));
  qsa('[data-copy-link]', root).forEach((a) => unbind.push(on(a, 'click', (e) => { e.preventDefault(); copy(a.href); })));
  const reward = qs('[data-reward]', root);
  if (reward) {
    qsa('[data-reward-open]', root).forEach((b) => unbind.push(on(b, 'click', () => reward.showModal())));
    qsa('[data-reward-close]', reward).forEach((b) => unbind.push(on(b, 'click', () => reward.close())));
    unbind.push(on(reward, 'click', (e) => { if (e.target === reward) reward.close(); }));
    qsa('[data-reward-tab]', reward).forEach((b) => unbind.push(on(b, 'click', () => {
      qsa('[data-reward-tab]', reward).forEach((x) => x.classList.toggle('is-on', x === b));
      qsa('[data-reward-code]', reward).forEach((f) => f.classList.toggle('is-on', f.dataset.rewardCode === b.dataset.rewardTab));
    })));
  }
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
