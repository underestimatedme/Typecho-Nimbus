// 音乐页视图：大封面黑胶、频谱、LRC 歌词、歌单。与悬浮播放器共用 player.js 的同一个 <audio>
import { qs, qsa, on, prefersReduced, tr } from './util.js';

let view = null;

const fmt = (t) => { t = Math.max(0, Math.floor(t || 0)); return Math.floor(t / 60) + ':' + String(t % 60).padStart(2, '0'); };

export async function init(root = document) {
  const el = qs('[data-music-page]', root);
  if (!el) return;
  destroy();
  const P = await import('./player.js');
  await P.init();
  const S = P.state();
  if (!S.ready || !S.list.length) { el.classList.add('is-empty'); return; }
  el.classList.add('is-ready');
  if (S.audio.preload === 'none' && S.audio.readyState === 0) { S.audio.preload = 'metadata'; S.audio.load(); }
  const offs = [];
  const $ = (s) => qs(s, el);
  const cover = $('[data-mp-cover]'), title = $('[data-mp-title]'), artist = $('[data-mp-artist]');
  const cur = $('[data-mp-cur]'), dur = $('[data-mp-dur]'), seek = $('[data-mp-seek]'), vol = $('[data-mp-vol]');
  const lrcBox = $('[data-mp-lrc]'), list = $('[data-mp-list]'), canvas = $('[data-mp-viz]');
  qsa('[data-mp-toggle]', el).forEach((b) => offs.push(on(b, 'click', P.toggle)));
  offs.push(on($('[data-mp-next]'), 'click', P.next));
  offs.push(on($('[data-mp-prev]'), 'click', P.prev));
  let dragging = false;
  offs.push(on(seek, 'input', () => { dragging = true; cur.textContent = fmt(seek.value / 1000 * (S.audio.duration || 0)); }));
  offs.push(on(seek, 'change', () => { dragging = false; P.seek(seek.value / 1000); }));
  vol.value = String(Math.round(S.audio.volume * 100));
  offs.push(on(vol, 'input', () => { S.audio.volume = vol.value / 100; }));

  list.innerHTML = '';
  S.list.forEach((t, i) => {
    const li = document.createElement('li');
    const b = document.createElement('button');
    b.type = 'button';
    const n = document.createElement('span'); n.className = 'n-mp-no'; n.textContent = String(i + 1).padStart(2, '0');
    const tt = document.createElement('b'); tt.textContent = t.title;
    const a = document.createElement('em'); a.textContent = t.artist;
    b.append(n, tt, a);
    b.addEventListener('click', () => P.pick(i));
    li.appendChild(b);
    list.appendChild(li);
  });

  let lines = [], lineIdx = -1, shownIndex = -1, token = 0;
  async function loadLyrics(t) {
    const my = ++token;
    lrcBox.innerHTML = '';
    lines = []; lineIdx = -1;
    const got = await P.lyrics(t);
    if (my !== token) return;
    lines = got;
    if (!lines.length) { const p = document.createElement('p'); p.className = 'n-mp-nolrc'; p.textContent = t.lrc ? tr('歌词加载失败') : tr('纯音乐，请欣赏'); lrcBox.appendChild(p); return; }
    lines.forEach((l) => { const p = document.createElement('p'); p.textContent = l.text; lrcBox.appendChild(p); });
  }

  const unsub = P.subscribe(() => {
    const t = P.track(); if (!t) return;
    const a = S.audio;
    el.classList.toggle('is-playing', !a.paused);
    if (shownIndex !== S.i) {
      shownIndex = S.i;
      title.textContent = t.title; artist.textContent = t.artist || tr('未知艺术家');
      cover.style.backgroundImage = t.cover ? `url("${t.cover.replace(/"/g, '%22')}")` : '';
      cover.classList.toggle('is-empty', !t.cover);
      qsa('li', list).forEach((li, k) => li.classList.toggle('is-on', k === S.i));
      loadLyrics(t);
    }
    if (!dragging) {
      cur.textContent = fmt(a.currentTime);
      seek.value = a.duration ? String(Math.round(a.currentTime / a.duration * 1000)) : '0';
    }
    dur.textContent = fmt(a.duration);
    if (lines.length) {
      let k = -1;
      for (let j = 0; j < lines.length; j++) { if (lines[j].t <= a.currentTime + 0.25) k = j; else break; }
      if (k !== lineIdx) {
        lineIdx = k;
        const ps = lrcBox.children;
        for (let j = 0; j < ps.length; j++) ps[j].classList.toggle('is-on', j === k);
        const on = ps[k];
        if (on) lrcBox.scrollTo({ top: on.offsetTop - lrcBox.clientHeight / 2 + on.clientHeight / 2, behavior: prefersReduced() ? 'auto' : 'smooth' });
      }
    }
  });

  // 频谱：同源曲目使用真实 AnalyserNode；跨域时用平滑的模拟波形（不会把音频变成静音）
  const ctx = canvas.getContext('2d');
  let raf = 0, phase = 0;
  const bars = 48, data = new Uint8Array(128), sim = new Float32Array(bars);
  function draw() {
    raf = requestAnimationFrame(draw);
    const dpr = window.devicePixelRatio || 1;
    const w = canvas.clientWidth, h = canvas.clientHeight;
    if (!w || !h) return;
    if (canvas.width !== w * dpr) { canvas.width = w * dpr; canvas.height = h * dpr; }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    const an = P.analyser(), playing = !S.audio.paused;
    if (an) an.getByteFrequencyData(data);
    phase += 0.05;
    const style = getComputedStyle(el);
    const c1 = style.getPropertyValue('--mp-a').trim() || '#6aa8ff', c2 = style.getPropertyValue('--mp-b').trim() || '#b18cff';
    const g = ctx.createLinearGradient(0, h, 0, 0); g.addColorStop(0, c1); g.addColorStop(1, c2);
    ctx.fillStyle = g;
    const bw = w / bars;
    for (let i = 0; i < bars; i++) {
      let v;
      if (an) v = data[Math.min(data.length - 1, Math.floor(Math.pow(i / bars, 1.6) * data.length * 0.7))] / 255;
      else {
        const target = playing ? 0.25 + 0.45 * Math.abs(Math.sin(phase * (1 + i % 5 * 0.21) + i * 0.6)) * (1 - i / bars * 0.55) : 0.04;
        sim[i] += (target - sim[i]) * 0.12; v = sim[i];
      }
      const bh = Math.max(2, v * h);
      const x = i * bw + bw * 0.18, rw = bw * 0.64;
      ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(x, h - bh, rw, bh, Math.min(rw / 2, 4)); else ctx.rect(x, h - bh, rw, bh);
      ctx.fill();
    }
  }
  if (!prefersReduced()) draw();

  view = { offs, unsub, stop: () => cancelAnimationFrame(raf) };
}

export function destroy() {
  if (!view) return;
  view.offs.forEach((f) => f());
  view.unsub();
  view.stop();
  view = null;
}
