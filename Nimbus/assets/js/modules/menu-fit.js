// 导航「优先级」菜单：放不下的项依次收进「更多」下拉
import { qsa, on, debounce } from './util.js';
let unbind = [];

function fit(menu) {
  const wrap = menu.parentElement;
  let more = menu.querySelector(':scope > .n-menu-more');
  if (!more) {
    more = document.createElement('li');
    more.className = 'n-menu-item has-children n-menu-more';
    more.hidden = true;
    more.innerHTML = '<a role="button" tabindex="0" aria-haspopup="true">更多<svg class="n-ico n-menu-caret" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg></a><ul class="n-submenu"></ul>';
    menu.appendChild(more);
  }
  const sub = more.querySelector('.n-submenu');
  // 先全部还原
  qsa(':scope > li[data-moved]', sub).forEach((li) => { li.removeAttribute('data-moved'); li.className = li.dataset.cls || 'n-menu-item'; menu.insertBefore(li, more); });
  more.hidden = true;
  if (!wrap || wrap.clientWidth === 0) return;
  const avail = wrap.clientWidth;
  const items = qsa(':scope > li:not(.n-menu-more)', menu);
  if (menu.scrollWidth <= avail) return;
  more.hidden = false;
  for (let i = items.length - 1; i > 0 && menu.scrollWidth > avail; i--) {
    const li = items[i];
    li.dataset.moved = '1';
    li.dataset.cls = li.className;
    sub.prepend(li);
  }
  more.classList.toggle('is-current', !!sub.querySelector('.is-current'));
}

export function init() {
  destroy();
  const menus = qsa('.n-nav-menu > .n-menu');
  const run = () => menus.forEach(fit);
  run();
  unbind.push(on(window, 'resize', debounce(run, 120)));
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(run);
}
export function destroy() { unbind.forEach((f) => f()); unbind = []; }
