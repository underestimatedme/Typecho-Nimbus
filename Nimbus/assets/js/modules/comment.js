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
  // 提交态 / AJAX（NimbusKit 开启时）
  const errBox = (() => { let el = qs('[data-comment-error]', form); if (!el) { el = document.createElement('div'); el.className = 'n-comment-error'; el.dataset.commentError = ''; el.hidden = true; form.insertBefore(el, qs('.n-comment-tools', form)); } return el; })();
  const showError = (msg) => { errBox.textContent = msg; errBox.hidden = false; errBox.classList.remove('is-shake'); void errBox.offsetWidth; errBox.classList.add('is-shake'); };
  unbind.push(on(form, 'submit', async (e) => {
    const btn = qs('[data-comment-submit]', form);
    if (pv && !pv.hidden) { pv.hidden = true; ta.hidden = false; }
    errBox.hidden = true;
    if (form.dataset.ajax !== '1' || !config().api || !window.fetch) { btn && btn.classList.add('is-loading'); return; }
    e.preventDefault();
    if (btn) { btn.classList.add('is-loading'); btn.disabled = true; }
    try {
      const r = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
      const type = r.headers.get('content-type') || '';
      let coid = null, message = '评论成功', doc = null;
      if (type.includes('json')) {
        const d = await r.json();
        if (!d.ok) throw new Error(d.error || '提交失败');
        coid = d.data.coid; message = d.data.message || message;
      } else {
        const html = await r.text();
        doc = new DOMParser().parseFromString(html, 'text/html');
        if (!r.ok || !doc.querySelector('#comments')) {
          // Typecho 拒绝评论时返回错误页：取正文作为原因
          const box = doc.querySelector('.container') || doc.body;
          throw new Error((box ? box.textContent : '').replace(/\s+/g, ' ').trim().slice(0, 160) || '提交失败（' + r.status + '）');
        }
        const m = (r.url || '').match(/#comment-(\d+)/); if (m) coid = m[1];
      }
      if (!doc) {
        const page = await fetch(location.href.split('#')[0], { credentials: 'same-origin', headers: { 'X-Nimbus-Router': '1' } });
        doc = new DOMParser().parseFromString(await page.text(), 'text/html');
      }
      const fresh = doc.querySelector('#comments'), old = document.getElementById('comments');
      if (fresh && old) {
        old.replaceWith(fresh);
        init(document);
        const li = coid ? document.getElementById('comment-' + coid) : null;
        if (li) { li.classList.add('is-new'); li.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
      }
      toast(message);
    } catch (err) {
      showError(err.message || '提交失败，请重试');
    } finally {
      if (btn && document.contains(btn)) { btn.classList.remove('is-loading'); btn.disabled = false; }
    }
  }));
  // URL 带 replyTo 时定位
  const m = location.search.match(/replyTo=(\d+)/);
  if (m) window.TypechoComment.reply('comment-' + m[1]);
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; emojiLoaded = false; }
