// 音乐播放器（单例）：悬浮播放器 + 音乐页视图共享同一个 <audio>
// 播放器位于 #n-main 之外，无刷新切换页面时不会被替换，也不会重复初始化
import { qs, qsa, on, config, storage, prefersReduced, tr } from './util.js';

const KEY = 'nimbus-music';
const S = { ready: false, loading: null, list: [], i: 0, audio: null, subs: new Set(), ctx: null, analyser: null, wired: false, sameOrigin: false, lrc: new Map(), page: null };

const emit = () => S.subs.forEach((f) => { try { f(S); } catch (e) {} });
const save = () => storage.set(KEY, JSON.stringify({ i: S.i, t: S.audio ? Math.floor(S.audio.currentTime) : 0, v: S.audio ? S.audio.volume : 0.8 }));
export const track = () => S.list[S.i] || null;

function normalize(items) {
  return (Array.isArray(items) ? items : []).map((x) => ({
    title: String(x.title || x.name || tr('未命名')), artist: String(x.artist || x.author || ''),
    url: String(x.url || ''), cover: String(x.cover || x.pic || ''), lrc: String(x.lrc || ''),
  })).filter((x) => /^(https?:)?\/\//.test(x.url) || x.url.startsWith('/'));
}

async function loadList(cfg) {
  if (cfg.source === 'meting' && cfg.meting) {
    try { const r = await fetch(cfg.meting); return normalize(await r.json()); } catch (e) { return []; }
  }
  return normalize(cfg.list);
}

function load(i, autoplay) {
  if (!S.list.length) return;
  S.i = (i + S.list.length) % S.list.length;
  const t = track();
  S.audio.src = t.url;
  S.audio.load();
  if ('mediaSession' in navigator) {
    try { navigator.mediaSession.metadata = new MediaMetadata({ title: t.title, artist: t.artist, artwork: t.cover ? [{ src: t.cover, sizes: '512x512' }] : [] }); } catch (e) {}
  }
  emit();
  save();
  if (autoplay) play();
}

function wireAnalyser() {
  // 只有全部曲目同源时才接入 Web Audio：跨域且无 CORS 的音频经过 MediaElementSource 会变成静音
  if (S.wired || !S.sameOrigin) return;
  try {
    S.ctx = new (window.AudioContext || window.webkitAudioContext)();
    const src = S.ctx.createMediaElementSource(S.audio);
    S.analyser = S.ctx.createAnalyser();
    S.analyser.fftSize = 256;
    S.analyser.smoothingTimeConstant = 0.78;
    src.connect(S.analyser); S.analyser.connect(S.ctx.destination);
    S.wired = true;
  } catch (e) { S.analyser = null; }
}

export function play() { S.want = true; wireAnalyser(); if (S.ctx && S.ctx.state === 'suspended') S.ctx.resume(); return S.audio.play().catch(() => {}); }
export function toggle() { if (!S.audio) return; if (S.audio.paused) play(); else { S.want = false; S.audio.pause(); } }
export function next() { load(S.i + 1, true); }
export function prev() { if (S.audio && S.audio.currentTime > 4) { S.audio.currentTime = 0; return; } load(S.i - 1, true); }
export function seek(ratio) { if (S.audio && isFinite(S.audio.duration)) S.audio.currentTime = Math.max(0, Math.min(1, ratio)) * S.audio.duration; }
export function pick(i) { i === S.i ? toggle() : load(i, true); }
export function subscribe(fn) { S.subs.add(fn); fn(S); return () => S.subs.delete(fn); }
export function analyser() { return S.analyser; }
export function state() { return S; }

export async function lyrics(t) {
  if (!t || !t.lrc) return [];
  if (S.lrc.has(t.url)) return S.lrc.get(t.url);
  let text = '';
  if (t.lrc.trim().startsWith('[')) text = t.lrc;
  else { try { const r = await fetch(t.lrc); if (r.ok) text = await r.text(); } catch (e) {} }
  const lines = [];
  text.split(/\r?\n/).forEach((row) => {
    const tags = [...row.matchAll(/\[(\d{1,2}):(\d{1,2}(?:\.\d{1,3})?)\]/g)];
    const words = row.replace(/\[[^\]]*\]/g, '').trim();
    tags.forEach((m) => lines.push({ t: parseInt(m[1], 10) * 60 + parseFloat(m[2]), text: words }));
  });
  lines.sort((a, b) => a.t - b.t);
  const out = lines.filter((l) => l.text);
  S.lrc.set(t.url, out);
  return out;
}

function renderDock() {
  const dock = qs('[data-player]');
  if (!dock) return;
  dock.hidden = false;
  requestAnimationFrame(() => dock.classList.add('is-in'));
  const ring = qs('[data-pl-ring]', dock), cover = qs('[data-pl-cover]', dock);
  const title = qs('[data-pl-title]', dock), artist = qs('[data-pl-artist]', dock), bar = qs('[data-pl-bar]', dock);
  const box = qs('[data-pl-listbox]', dock), items = qs('[data-pl-items]', dock);
  qsa('[data-pl-toggle]', dock).forEach((b) => on(b, 'click', toggle));
  on(qs('[data-pl-next]', dock), 'click', next);
  on(qs('[data-pl-prev]', dock), 'click', prev);
  on(qs('[data-pl-list]', dock), 'click', () => { box.hidden = !box.hidden; dock.classList.toggle('is-list', !box.hidden); });
  on(qs('[data-pl-seek]', dock), 'click', (e) => { const r = e.currentTarget.getBoundingClientRect(); seek((e.clientX - r.left) / r.width); });
  on(document, 'click', (e) => { if (!box.hidden && !dock.contains(e.target)) { box.hidden = true; dock.classList.remove('is-list'); } });
  items.innerHTML = '';
  S.list.forEach((t, i) => {
    const li = document.createElement('li');
    const b = document.createElement('button');
    b.type = 'button';
    const n = document.createElement('b'); n.textContent = t.title;
    const a = document.createElement('em'); a.textContent = t.artist;
    b.append(n, a);
    b.addEventListener('click', () => pick(i));
    li.appendChild(b);
    items.appendChild(li);
  });
  let lastIndex = -1;
  subscribe(() => {
    const t = track(); if (!t) return;
    const a = S.audio, p = a.duration ? a.currentTime / a.duration : 0;
    dock.classList.toggle('is-playing', !a.paused);
    if (ring) ring.style.strokeDashoffset = String(170 - 170 * p);
    if (bar) bar.style.width = (p * 100) + '%';
    if (lastIndex !== S.i) {
      lastIndex = S.i;
      title.textContent = t.title; artist.textContent = t.artist;
      cover.style.backgroundImage = t.cover ? `url("${t.cover.replace(/"/g, '%22')}")` : '';
      cover.classList.toggle('is-empty', !t.cover);
      qsa('li', items).forEach((li, k) => li.classList.toggle('is-on', k === S.i));
    }
  });
}

export async function init() {
  const cfg = config().music;
  if (!cfg) return;
  if (S.ready) { emit(); return; }
  if (S.loading) return S.loading;
  S.loading = (async () => {
    S.list = await loadList(cfg);
    if (!S.list.length) return;
    S.sameOrigin = S.list.every((t) => { try { return new URL(t.url, location.href).origin === location.origin; } catch (e) { return false; } });
    S.audio = new Audio();
    S.audio.preload = 'none'; // 不在每个页面预取音频；音乐页需要时长时再加载元数据
    let saved = {};
    try { saved = JSON.parse(storage.get(KEY) || '{}') || {}; } catch (e) {}
    S.audio.volume = typeof saved.v === 'number' ? Math.min(1, Math.max(0, saved.v)) : 0.8;
    ['timeupdate', 'play', 'pause', 'loadedmetadata', 'volumechange'].forEach((ev) => S.audio.addEventListener(ev, emit));
    S.audio.addEventListener('ended', next);
    S.audio.addEventListener('volumechange', save);
    // 播放失败时跳下一首；整张歌单都失败就停下，避免无限循环
    let fails = 0;
    S.audio.addEventListener('playing', () => { fails = 0; });
    S.audio.addEventListener('error', () => { if (S.list.length > 1 && ++fails < S.list.length && S.want) setTimeout(next, 800); emit(); });
    setInterval(() => { if (S.audio && !S.audio.paused) save(); }, 5000);
    window.addEventListener('pagehide', save);
    if ('mediaSession' in navigator) {
      try { navigator.mediaSession.setActionHandler('play', play); navigator.mediaSession.setActionHandler('pause', () => { S.want = false; S.audio.pause(); }); navigator.mediaSession.setActionHandler('nexttrack', next); navigator.mediaSession.setActionHandler('previoustrack', prev); } catch (e) {}
    }
    const start = Number.isInteger(saved.i) && saved.i < S.list.length ? saved.i : 0;
    load(start, false);
    if (saved.t && start === saved.i) S.audio.addEventListener('loadedmetadata', () => { if (saved.t < S.audio.duration - 2) S.audio.currentTime = saved.t; }, { once: true });
    renderDock();
    S.ready = true;
    if (cfg.autoplay && !prefersReduced()) play();
  })();
  return S.loading;
}
