// 统计页：点击热力图某天，弹出当天的文章列表（含当天归档链接）；热门文章「查看全部 / 收起」原地展开
import { qs, on, tr, toast } from './util.js';

let offs = [], pop = null;

function close() { if (pop) { pop.remove(); pop = null; } }

function open(heat, cell, data) {
  close();
  const day = cell.dataset.day;
  const list = (data.posts && data.posts[day]) || [];
  pop = document.createElement('div');
  pop.className = 'n-heat-pop';
  pop.setAttribute('role', 'dialog');
  const head = document.createElement('div');
  head.className = 'n-heat-pop-head';
  const [y, m, d] = day.split('-');
  head.textContent = tr('{y} 年 {m} 月 {d} 日', { y, m: +m, d: +d });
  const n = document.createElement('small');
  n.textContent = tr('{n} 篇', { n: list.length });
  head.appendChild(n);
  pop.appendChild(head);
  const ul = document.createElement('ul');
  list.forEach((p) => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = p.u; a.textContent = p.t;
    li.appendChild(a); ul.appendChild(li);
  });
  pop.appendChild(ul);
  if (data.day) {
    const all = document.createElement('a');
    all.className = 'n-heat-pop-all';
    all.href = data.day.replace('YYYY', y).replace('MM', m).replace('DD', d);
    all.textContent = tr('查看当天归档') + ' →';
    pop.appendChild(all);
  }
  heat.appendChild(pop);
  // 定位在格子上方，超出边界时左右收回
  const hr = heat.getBoundingClientRect(), cr = cell.getBoundingClientRect();
  const w = pop.offsetWidth, h = pop.offsetHeight;
  let left = cr.left - hr.left + heat.scrollLeft + cr.width / 2 - w / 2;
  left = Math.max(heat.scrollLeft + 4, Math.min(left, heat.scrollLeft + heat.clientWidth - w - 4));
  let top = cr.top - hr.top - h - 10;
  if (top < -hr.top + 70) top = cr.bottom - hr.top + 10;
  pop.style.left = left + 'px';
  pop.style.top = top + 'px';
  const first = pop.querySelector('a');
  if (first) first.focus({ preventScroll: true });
}

// 只取新页面里的 #hot 面板替换当前面板，不刷新、不跳动
async function toggleHot(link) {
  const panel = qs('#hot');
  if (!panel || panel.dataset.busy) return;
  panel.dataset.busy = '1';
  link.classList.add('is-loading');
  try {
    const r = await fetch(link.href, { headers: { 'X-Nimbus-Router': '1', Accept: 'text/html' }, credentials: 'same-origin' });
    if (!r.ok) throw new Error(r.status);
    const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
    const fresh = doc.getElementById('hot');
    if (!fresh) throw new Error('no panel');
    fresh.classList.add('is-in');
    const before = panel.getBoundingClientRect().top;
    panel.replaceWith(fresh);
    // 收起后如果面板顶部已在视口上方，滚回面板顶部
    if (before < 0) fresh.scrollIntoView({ block: 'start', behavior: 'smooth' });
    history.replaceState(history.state, '', link.href.replace(/#hot$/, ''));
    bindHot();
  } catch (e) {
    toast(tr('加载失败，请重试'));
  } finally {
    delete panel.dataset.busy;
    link.classList.remove('is-loading');
  }
}

let hotOff = null;
function bindHot() {
  if (hotOff) hotOff();
  const link = qs('#hot .n-st-more');
  hotOff = link ? on(link, 'click', (e) => { e.preventDefault(); toggleHot(link); }) : null;
}

export function init(root = document) {
  destroy();
  bindHot();
  const heat = qs('[data-heat]', root), el = qs('#n-heat-data', root);
  if (!heat || !el) return;
  let data = {};
  try { data = JSON.parse(el.textContent) || {}; } catch (e) {}
  offs.push(on(heat, 'click', (e) => {
    const cell = e.target.closest('[data-day]');
    if (cell) { e.stopPropagation(); open(heat, cell, data); }
  }));
  offs.push(on(heat, 'keydown', (e) => {
    const cell = e.target.closest && e.target.closest('[data-day]');
    if (cell && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); open(heat, cell, data); }
  }));
  offs.push(on(document, 'click', (e) => { if (pop && !pop.contains(e.target)) close(); }));
  offs.push(on(document, 'keydown', (e) => { if (e.key === 'Escape') close(); }));
}

export function destroy() { offs.forEach((f) => f()); offs = []; close(); if (hotOff) hotOff(); hotOff = null; }
