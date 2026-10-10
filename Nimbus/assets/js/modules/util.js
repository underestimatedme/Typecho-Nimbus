// 通用工具
export const qs = (sel, root = document) => root.querySelector(sel);
export const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));
export const on = (el, ev, fn, opts) => { el.addEventListener(ev, fn, opts); return () => el.removeEventListener(ev, fn, opts); };
export const storage = {
  get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
  set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
};
export const prefersReduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.dataset.motion === 'off';
export const decorAllowed = () => !prefersReduced() && document.documentElement.dataset.motion !== 'reduced';
export const config = () => window.NimbusConfig || {};
const loaded = new Map();
export function loadScript(url) {
  if (loaded.has(url)) return loaded.get(url);
  const p = new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = url; s.async = true; s.onload = resolve; s.onerror = () => reject(new Error('load failed: ' + url));
    document.head.appendChild(s);
  });
  loaded.set(url, p);
  return p;
}
export function loadCss(url) {
  if (loaded.has(url)) return loaded.get(url);
  const p = new Promise((resolve) => {
    const l = document.createElement('link');
    l.rel = 'stylesheet'; l.href = url; l.onload = resolve; l.onerror = resolve;
    document.head.appendChild(l);
  });
  loaded.set(url, p);
  return p;
}
export function toast(text, ms = 1800) {
  let box = qs('#n-toast');
  if (!box) { box = document.createElement('div'); box.id = 'n-toast'; box.className = 'n-toast'; document.body.appendChild(box); }
  const item = document.createElement('div');
  item.className = 'n-toast-item'; item.textContent = text;
  box.appendChild(item);
  requestAnimationFrame(() => item.classList.add('is-in'));
  setTimeout(() => { item.classList.remove('is-in'); setTimeout(() => item.remove(), 300); }, ms);
}
export function debounce(fn, ms = 150) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
export function throttle(fn, ms = 100) { let last = 0, t; return (...a) => { const now = Date.now(); if (now - last >= ms) { last = now; fn(...a); } else { clearTimeout(t); t = setTimeout(() => { last = Date.now(); fn(...a); }, ms - (now - last)); } }; }
// 界面文案：源码写中文原文，英文模式下从 NimbusConfig.i18n 查译文；{name} 为占位符
export function tr(zh, args) {
  const d = config().i18n || {};
  let s = typeof d[zh] === 'string' ? d[zh] : zh;
  if (args) Object.keys(args).forEach((k) => { s = s.split('{' + k + '}').join(String(args[k])); });
  return s;
}
