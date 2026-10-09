// 评论：回复定位、表情面板、Markdown 预览、Ctrl+Enter、（Kit）AJAX 提交
import { qs, qsa, on, config, toast } from './util.js';
let unbind = [], emojiLoaded = false;

function mdPreview(src) {
  let s = src.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  s = s.replace(/```([\s\S]*?)```/g, (m, c) => '<pre><code>' + c.trim() + '</code></pre>');
  s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
  s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/\*([^*]+)\*/g, '<em>$1</em>');
  s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
  return s.split(/\n{2,}/).map((p) => '<p>' + p.replace(/\n/g, '<br>') + '</p>').join('');
}

function insertAt(ta, text) {
  const s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
  ta.value = v.slice(0, s) + text + v.slice(e);
  ta.selectionStart = ta.selectionEnd = s + text.length;
  ta.focus();
}

async function buildEmoji(panel, ta) {
  if (emojiLoaded) return;
  emojiLoaded = true;
  try {
    const r = await fetch(config().assets + '/img/emoji.json');
    const groups = await r.json();
    const names = Object.keys(groups);
    const tabs = document.createElement('div'); tabs.className = 'n-emoji-tabs';
    const grid = document.createElement('div'); grid.className = 'n-emoji-grid';
    const show = (name) => {
      qsa('button', tabs).forEach((b) => b.classList.toggle('is-on', b.textContent === name));
      grid.innerHTML = '';
      groups[name].forEach((ch) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = ch; b.addEventListener('click', () => insertAt(ta, ch)); grid.appendChild(b); });
    };
    names.forEach((n) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = n; b.addEventListener('click', () => show(n)); tabs.appendChild(b); });
    panel.append(tabs, grid);
    show(names[0]);
  } catch (e) { panel.textContent = '表情加载失败'; }
}

function moveForm(respond, li, cancel) {
  if (!respond.dataset.home) { respond.dataset.home = '1'; respond._home = respond.parentNode; respond._next = respond.nextSibling; }
  if (li) { li.appendChild(respond); } else if (respond._home) { respond._home.insertBefore(respond, respond._next); }
  const title = qs('[data-respond-title]', respond);
  if (title) title.textContent = li ? '回复 ' + (qs('.n-comment-name', li) || {}).textContent : '发表评论';
  if (cancel) cancel.style.display = li ? '' : 'none';
  const ta = qs('[data-comment-text]', respond); ta && setTimeout(() => ta.focus(), 50);
}

export function init(root = document) {
  destroy();
  const respond = qs('[data-respond]', root);
  if (!respond) return;
  const form = qs('[data-comment-form]', respond), ta = qs('[data-comment-text]', respond);
  const cancel = qs('#cancel-comment-reply-link', respond);
  // 接管 Typecho 的 TypechoComment
  window.TypechoComment = {
    reply(coid) {
      const li = document.getElementById(coid);
      let parent = qs('input[name=parent]', form);
      if (!parent) { parent = document.createElement('input'); parent.type = 'hidden'; parent.name = 'parent'; form.appendChild(parent); }
      parent.value = li ? li.dataset.coid : '';
      moveForm(respond, li, cancel);
      return false;
    },
    cancelReply() {
      const parent = qs('input[name=parent]', form); parent && parent.remove();
      moveForm(respond, null, cancel);
      return false;
    },
  };
  // 表情
  const panel = qs('[data-emoji-panel]', respond), et = qs('[data-emoji-toggle]', respond);
  if (panel && et) unbind.push(on(et, 'click', () => { panel.hidden = !panel.hidden; if (!panel.hidden) buildEmoji(panel, ta); }));
  // 预览
  const pv = qs('[data-comment-preview]', respond), pt = qs('[data-preview-toggle]', respond);
  if (pv && pt) unbind.push(on(pt, 'click', () => { const show = pv.hidden; pv.hidden = !show; ta.hidden = show; if (show) pv.innerHTML = mdPreview(ta.value); pt.classList.toggle('is-on', show); }));
  // Ctrl+Enter
  unbind.push(on(ta, 'keydown', (e) => { if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); } }));
  // 提交态 / AJAX（Kit）
  unbind.push(on(form, 'submit', async (e) => {
    const btn = qs('[data-comment-submit]', form);
    if (pv && !pv.hidden) { pv.hidden = true; ta.hidden = false; }
    if (form.dataset.ajax !== '1' || !config().api) { btn && btn.classList.add('is-loading'); return; }
    e.preventDefault();
    btn && btn.classList.add('is-loading');
    try {
      const r = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
      const data = await r.json();
      if (!r.ok || data.error) throw new Error(data.error || '提交失败');
      toast(data.message || '评论已提交');
      if (data.html) {
        const list = qs('#n-comment-list') || (() => { const ol = document.createElement('ol'); ol.className = 'n-comment-list'; ol.id = 'n-comment-list'; respond.before(ol); return ol; })();
        const parent = qs('input[name=parent]', form);
        const tpl = document.createElement('template'); tpl.innerHTML = data.html.trim();
        if (parent && parent.value) { const li = document.getElementById('comment-' + parent.value); let ol = li && li.querySelector(':scope > .n-comment-children'); if (li && !ol) { ol = document.createElement('ol'); ol.className = 'n-comment-children'; li.appendChild(ol); } (ol || list).appendChild(tpl.content); } else list.appendChild(tpl.content);
      }
      ta.value = '';
      window.TypechoComment.cancelReply();
    } catch (err) { toast(err.message || '提交失败，请重试'); }
    finally { btn && btn.classList.remove('is-loading'); }
  }));
  // URL 带 replyTo 时定位
  const m = location.search.match(/replyTo=(\d+)/);
  if (m) window.TypechoComment.reply('comment-' + m[1]);
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; emojiLoaded = false; }
