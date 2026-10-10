// Hero：打字机副标题 + 滚动视差
import { qs, on, prefersReduced, decorAllowed } from './util.js';
import { active as fxActive } from './cyber-fx.js';
let timer = null, unbind = null, runId = 0;

const GLYPHS = 'アカサタナハマヤラワ01<>/\\|=+*#%$@&ABCDEFXYZ';
const sleep = (ms) => new Promise((r) => { timer = setTimeout(r, ms); });

// 赛博朋克皮肤：乱码逐字解码；其他皮肤：打字机
async function decode(el, s, id) {
  const frames = Math.min(26, 10 + s.length);
  for (let f = 0; f <= frames; f++) {
    if (id !== runId) return;
    const reveal = Math.floor(s.length * f / frames);
    el.textContent = s.slice(0, reveal);
    if (reveal < s.length) {
      const span = document.createElement('span');
      span.className = 'n-fx-scramble';
      span.textContent = Array.from(s.slice(reveal)).map((c) => (c.trim() ? GLYPHS[(Math.random() * GLYPHS.length) | 0] : c)).join('');
      el.appendChild(span);
    }
    await sleep(45);
  }
}
async function type(el, s, id, out) {
  for (let i = out ? s.length : 0; out ? i >= 0 : i <= s.length; i += out ? -1 : 1) {
    if (id !== runId) return;
    el.textContent = s.slice(0, i);
    await sleep(out ? 40 : 80);
  }
}
async function run(el, lines, id) {
  let li = 0, first = true;
  while (id === runId) {
    const s = lines[li];
    if (fxActive('decode')) {
      await decode(el, s, id);
      if (lines.length === 1) return;
      await sleep(2600);
    } else {
      if (!first) await type(el, s, id, false);
      if (lines.length === 1) return;
      await sleep(1500);
      await type(el, s, id, true);
      await sleep(300);
    }
    first = false;
    li = (li + 1) % lines.length;
  }
}

export function init(root = document) {
  destroy();
  const hero = qs('[data-hero]', root);
  if (!hero) return;
  const sub = qs('[data-typewriter]', hero);
  if (sub && !prefersReduced()) {
    let lines = [];
    try { lines = JSON.parse(sub.dataset.typewriter); } catch (e) {}
    lines = lines.filter((s) => typeof s === 'string' && s.trim());
    if (lines.length) run(sub.querySelector('span'), lines, ++runId);
  }
  const bg = qs('.n-hero-bg, .n-cover-img', hero);
  if (bg && decorAllowed()) {
    const h = hero.offsetHeight;
    let ticking = false;
    unbind = on(window, 'scroll', () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => { const y = window.scrollY; if (y < h) bg.style.translate = '0 ' + (y * 0.25) + 'px'; ticking = false; });
    }, { passive: true });
  }
}
export function destroy() { runId++; if (timer) clearTimeout(timer); timer = null; if (unbind) unbind(); unbind = null; }
