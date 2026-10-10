// 分享海报：Canvas 绘制标题、摘要、作者、日期、站点名与二维码，弹窗预览并可下载 PNG
import { toast } from './util.js';
import { drawQr } from '../lib/qr.js';

const W = 600, PAD = 40, SCALE = 2;
let dlg = null, lastUrl = '';

const cssVar = (name, fallback) => (getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback);

// 中日韩字符逐字断行，拉丁单词整体断行
function wrap(ctx, text, maxW, maxLines) {
  const tokens = String(text).replace(/\s+/g, ' ').trim().match(/[⺀-鿿豈-﫿　-〿＀-￯]|[^\s⺀-鿿豈-﫿　-〿＀-￯]+|\s/g) || [];
  const lines = [];
  let cur = '';
  for (const t of tokens) {
    const next = cur + t;
    // 避头标点：句读不放在行首，允许略微超出
    if (ctx.measureText(next).width <= maxW || cur === '' || /^[，。、；：！？）》」』…,.!?;:)\]]$/.test(t)) { cur = next; continue; }
    lines.push(cur.trimEnd()); cur = t === ' ' ? '' : t;
    if (lines.length === maxLines) break;
  }
  if (lines.length < maxLines && cur) lines.push(cur.trimEnd());
  else if (lines.length === maxLines && (cur || tokens.length)) {
    const consumed = lines.join('').replace(/\s/g, '').length;
    if (consumed < String(text).replace(/\s/g, '').length) {
      let last = lines[maxLines - 1];
      while (last && ctx.measureText(last + '…').width > maxW) last = last.slice(0, -1);
      lines[maxLines - 1] = last + '…';
    }
  }
  return lines;
}

function loadImage(src) {
  return new Promise((resolve) => {
    if (!src) return resolve(null);
    const img = new Image();
    let same = false;
    try { same = new URL(src, location.href).origin === location.origin; } catch (e) {}
    if (!same) img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = () => resolve(null);
    img.src = src;
    setTimeout(() => resolve(null), 6000);
  });
}

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath();
  if (ctx.roundRect) ctx.roundRect(x, y, w, h, r);
  else { ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r); ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath(); }
}

async function render(data, withCover) {
  const fontTitle = cssVar('--font-title', 'serif'), fontSans = cssVar('--font-sans', 'sans-serif');
  try { await Promise.all([document.fonts.load(`700 34px ${fontTitle}`, data.title), document.fonts.load(`16px ${fontSans}`, data.excerpt || 'a')]); } catch (e) {}
  const c = { bg: cssVar('--card', '#fff'), fg: cssVar('--fg-strong', '#111'), soft: cssVar('--fg-soft', '#444'), muted: cssVar('--muted', '#888'), line: cssVar('--line', 'rgba(0,0,0,.1)'), a: cssVar('--accent', '#3b82f6'), b: cssVar('--orb-b', '#c084fc'), on: cssVar('--on-accent', '#fff') };
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  const coverH = 330;
  // 先测量文字高度以确定画布高度
  ctx.font = `700 34px ${fontTitle}`;
  const titleLines = wrap(ctx, data.title, W - PAD * 2, 3);
  ctx.font = `17px ${fontSans}`;
  const exLines = data.excerpt ? wrap(ctx, data.excerpt, W - PAD * 2, 4) : [];
  const H = coverH + 44 + titleLines.length * 46 + (exLines.length ? 18 + exLines.length * 30 : 0) + 40 + 170;
  canvas.width = W * SCALE; canvas.height = H * SCALE;
  ctx.scale(SCALE, SCALE);
  ctx.fillStyle = c.bg; ctx.fillRect(0, 0, W, H);

  // 封面：可读取时绘入，否则用皮肤渐变
  const img = withCover ? await loadImage(data.cover) : null;
  ctx.save();
  ctx.beginPath(); ctx.rect(0, 0, W, coverH); ctx.clip();
  const g = ctx.createLinearGradient(0, 0, W, coverH); g.addColorStop(0, c.a); g.addColorStop(1, c.b);
  ctx.fillStyle = g; ctx.fillRect(0, 0, W, coverH);
  if (img) {
    const r = Math.max(W / img.naturalWidth || 1, coverH / img.naturalHeight || 1);
    const iw = (img.naturalWidth || W) * r, ih = (img.naturalHeight || coverH) * r;
    ctx.drawImage(img, (W - iw) / 2, (coverH - ih) / 2, iw, ih);
  } else {
    ctx.globalAlpha = 0.18; ctx.fillStyle = '#fff';
    ctx.beginPath(); ctx.arc(W - 90, 70, 120, 0, Math.PI * 2); ctx.fill();
    ctx.beginPath(); ctx.arc(80, coverH - 30, 150, 0, Math.PI * 2); ctx.fill();
    ctx.globalAlpha = 1;
    ctx.fillStyle = 'rgba(255,255,255,.92)'; ctx.font = `700 120px ${fontTitle}`; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText((data.site || 'N').trim().slice(0, 1), W / 2, coverH / 2);
    ctx.textAlign = 'left';
  }
  const fade = ctx.createLinearGradient(0, coverH - 120, 0, coverH);
  fade.addColorStop(0, 'rgba(0,0,0,0)'); fade.addColorStop(1, 'rgba(0,0,0,.35)');
  ctx.fillStyle = fade; ctx.fillRect(0, coverH - 120, W, 120);
  ctx.restore();
  // 日期胶囊
  ctx.font = `600 13px ${fontSans}`; ctx.textBaseline = 'middle';
  const chip = data.date, cw = ctx.measureText(chip).width + 24;
  roundRect(ctx, PAD, coverH - 46, cw, 28, 14); ctx.fillStyle = 'rgba(255,255,255,.88)'; ctx.fill();
  ctx.fillStyle = '#222'; ctx.fillText(chip, PAD + 12, coverH - 32);

  // 标题与摘要
  let y = coverH + 44;
  ctx.textBaseline = 'alphabetic';
  ctx.fillStyle = c.fg; ctx.font = `700 34px ${fontTitle}`;
  titleLines.forEach((l) => { y += 34; ctx.fillText(l, PAD, y); y += 12; });
  if (exLines.length) {
    y += 18; ctx.fillStyle = c.soft; ctx.font = `17px ${fontSans}`;
    exLines.forEach((l) => { y += 22; ctx.fillText(l, PAD, y); y += 8; });
  }
  y += 40;
  ctx.strokeStyle = c.line; ctx.setLineDash([5, 6]); ctx.lineWidth = 1.5;
  ctx.beginPath(); ctx.moveTo(PAD, y); ctx.lineTo(W - PAD, y); ctx.stroke(); ctx.setLineDash([]);

  // 底部：站点、作者、提示 + 二维码
  const qs = 112, qx = W - PAD - qs, qy = y + 28;
  roundRect(ctx, qx - 8, qy - 8, qs + 16, qs + 16, 12); ctx.fillStyle = '#fff'; ctx.fill();
  ctx.strokeStyle = c.line; ctx.lineWidth = 1; ctx.stroke();
  drawQr(ctx, data.url, qx, qy, qs, '#111', '#fff');
  const accentBar = ctx.createLinearGradient(PAD, 0, PAD + 4, 0); accentBar.addColorStop(0, c.a); accentBar.addColorStop(1, c.b);
  ctx.fillStyle = accentBar; roundRect(ctx, PAD, qy + 4, 4, 40, 2); ctx.fill();
  ctx.fillStyle = c.fg; ctx.font = `700 22px ${fontTitle}`;
  const site = wrap(ctx, data.site || '', qx - PAD - 40, 1)[0] || '';
  ctx.fillText(site, PAD + 16, qy + 22);
  ctx.fillStyle = c.muted; ctx.font = `14px ${fontSans}`;
  ctx.fillText(wrap(ctx, [data.author, data.date].filter(Boolean).join(' · '), qx - PAD - 40, 1)[0] || '', PAD + 16, qy + 46);
  ctx.fillStyle = c.muted; ctx.font = `13px ${fontSans}`;
  ctx.fillText('长按或扫描二维码', PAD, qy + 86);
  ctx.fillText('阅读全文 →', PAD, qy + 106);
  return canvas;
}

function toBlob(canvas) {
  return new Promise((resolve, reject) => {
    try { canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('empty'))), 'image/png'); } catch (e) { reject(e); }
  });
}

function ensureDialog() {
  if (dlg && document.body.contains(dlg)) return dlg;
  dlg = document.createElement('dialog');
  dlg.className = 'n-modal n-poster';
  dlg.setAttribute('aria-label', '分享海报');
  dlg.innerHTML = '<div class="n-modal-in n-poster-in"><div class="n-poster-view" data-pv><span class="n-poster-loading">正在生成海报…</span></div><div class="n-poster-act"><a class="n-btn n-btn-primary" data-pd download>下载图片</a><button type="button" class="n-btn" data-pc>关闭</button></div><p class="n-poster-tip">手机上可长按图片保存或分享</p></div>';
  document.body.appendChild(dlg);
  dlg.querySelector('[data-pc]').addEventListener('click', () => dlg.close());
  dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
  dlg.addEventListener('close', () => { if (lastUrl) { URL.revokeObjectURL(lastUrl); lastUrl = ''; } });
  return dlg;
}

export async function open(box) {
  let extra = {};
  try { extra = JSON.parse(box.dataset.poster || '{}') || {}; } catch (e) {}
  const data = { url: box.dataset.url || location.href, title: box.dataset.title || document.title, ...extra };
  const d = ensureDialog();
  const view = d.querySelector('[data-pv]'), dl = d.querySelector('[data-pd]');
  view.innerHTML = '<span class="n-poster-loading">正在生成海报…</span>';
  dl.removeAttribute('href');
  if (!d.open) d.showModal();
  let blob;
  try { blob = await toBlob(await render(data, true)); }
  catch (e) {
    // 封面跨域导致画布被污染时，改用皮肤渐变重画
    try { blob = await toBlob(await render(data, false)); } catch (e2) { toast('海报生成失败'); d.close(); return; }
  }
  if (lastUrl) URL.revokeObjectURL(lastUrl);
  lastUrl = URL.createObjectURL(blob);
  const img = new Image();
  img.alt = data.title;
  img.src = lastUrl;
  view.innerHTML = '';
  view.appendChild(img);
  dl.href = lastUrl;
  dl.download = 'poster-' + (data.title || 'nimbus').replace(/[\\/:*?"<>|\s]+/g, '-').slice(0, 40) + '.png';
}
