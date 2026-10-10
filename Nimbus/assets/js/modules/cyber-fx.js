// 赛博朋克特效：首页字符雨、鼠标霓虹拖尾（标题故障与霓虹描边为纯 CSS，副标题解码见 hero.js）
// 只在 cyber 皮肤生效；切换皮肤时自动启停；尊重「动效强度」与系统减少动态设置。
import { qs, prefersReduced } from './util.js';

const fx = () => (document.documentElement.dataset.fx || '').split(/\s+/);
export const active = (name) => document.documentElement.dataset.theme === 'cyber' && fx().includes(name) && !prefersReduced() && document.documentElement.dataset.motion !== 'reduced';

let rain = null, trail = null, bound = false;

function startRain() {
  const hero = qs('[data-hero]');
  if (!hero || (rain && rain.hero === hero)) return;
  stopRain();
  const c = document.createElement('canvas');
  c.className = 'n-fx-rain';
  c.setAttribute('aria-hidden', 'true');
  hero.insertBefore(c, qs('.n-hero-in', hero));
  const ctx = c.getContext('2d');
  const chars = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワン0123456789NIMBUS';
  const size = 16;
  let cols = [], w = 0, h = 0, raf = 0, last = 0, visible = true;
  const resize = () => {
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    w = hero.clientWidth; h = hero.clientHeight;
    c.width = w * dpr; c.height = h * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    cols = Array.from({ length: Math.ceil(w / size) }, () => Math.random() * -h / size);
  };
  resize();
  const color = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#22d3ee';
  const draw = (ts) => {
    raf = requestAnimationFrame(draw);
    if (!visible || document.hidden || ts - last < 50) return; // 约 20 帧，省电
    last = ts;
    ctx.fillStyle = 'rgba(0, 0, 0, 0.12)';
    ctx.globalCompositeOperation = 'destination-out';
    ctx.fillRect(0, 0, w, h);
    ctx.globalCompositeOperation = 'source-over';
    ctx.font = `${size}px ui-monospace, monospace`;
    for (let i = 0; i < cols.length; i++) {
      const y = cols[i] * size;
      ctx.fillStyle = Math.random() > 0.975 ? '#fff' : color;
      ctx.fillText(chars[(Math.random() * chars.length) | 0], i * size, y);
      cols[i] = y > h && Math.random() > 0.972 ? 0 : cols[i] + 1;
    }
  };
  raf = requestAnimationFrame(draw);
  const io = new IntersectionObserver((es) => { visible = es[0].isIntersecting; });
  io.observe(hero);
  const onResize = () => resize();
  window.addEventListener('resize', onResize);
  rain = { hero, stop: () => { cancelAnimationFrame(raf); io.disconnect(); window.removeEventListener('resize', onResize); c.remove(); } };
}
function stopRain() { if (rain) rain.stop(); rain = null; }

function startTrail() {
  if (trail || !matchMedia('(pointer: fine)').matches) return;
  const c = document.createElement('canvas');
  c.className = 'n-fx-trail';
  c.setAttribute('aria-hidden', 'true');
  document.body.appendChild(c);
  const ctx = c.getContext('2d');
  const pts = [];
  let raf = 0;
  const resize = () => { const d = Math.min(2, window.devicePixelRatio || 1); c.width = innerWidth * d; c.height = innerHeight * d; ctx.setTransform(d, 0, 0, d, 0, 0); };
  resize();
  const tick = () => {
    ctx.clearRect(0, 0, innerWidth, innerHeight);
    for (let i = pts.length - 1; i >= 0; i--) {
      const p = pts[i];
      p.life -= 0.045;
      if (p.life <= 0) { pts.splice(i, 1); continue; }
      ctx.beginPath();
      ctx.fillStyle = `hsla(${p.hue}, 100%, 65%, ${p.life * 0.8})`;
      ctx.shadowColor = `hsla(${p.hue}, 100%, 60%, ${p.life})`;
      ctx.shadowBlur = 12;
      ctx.arc(p.x, p.y, 2.2 + p.life * 2.5, 0, Math.PI * 2);
      ctx.fill();
    }
    raf = pts.length ? requestAnimationFrame(tick) : 0;
  };
  let hue = 180;
  const move = (e) => {
    hue = (hue + 3) % 360;
    pts.push({ x: e.clientX, y: e.clientY, life: 1, hue: 180 + Math.sin(hue / 57) * 70 });
    if (pts.length > 40) pts.shift();
    if (!raf) raf = requestAnimationFrame(tick);
  };
  window.addEventListener('pointermove', move, { passive: true });
  window.addEventListener('resize', resize);
  trail = { stop: () => { cancelAnimationFrame(raf); window.removeEventListener('pointermove', move); window.removeEventListener('resize', resize); c.remove(); } };
}
function stopTrail() { if (trail) trail.stop(); trail = null; }

export function sync() {
  if (active('rain')) startRain(); else stopRain();
  if (active('trail')) startTrail(); else stopTrail();
}

export function init() {
  // 页面切换后 hero 是新节点：旧画布随旧 DOM 移除，重新挂载
  if (rain && !document.contains(rain.hero)) stopRain();
  sync();
  if (!bound) { bound = true; document.addEventListener('nimbus:theme', () => setTimeout(sync, 50)); }
}
