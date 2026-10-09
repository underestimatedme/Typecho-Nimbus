// 导航：悬浮胶囊显隐、返回顶部、抽屉
import { qs, qsa, on, throttle } from './util.js';

let unbind = [];

function threshold() {
  const hero = qs('[data-hero]');
  if (hero) return hero.offsetHeight - 80;
  const nav = qs('[data-nav]');
  return nav ? nav.offsetHeight + 40 : 120;
}

export function init() {
  destroy();
  const float = qs('[data-nav-float]');
  const totop = qs('[data-totop]');
  const solid = !!qs('[data-nav].n-nav-solid');
  const update = () => {
    const y = window.scrollY;
    const t = threshold();
    const showFloat = !solid && y > t;
    float && float.classList.toggle('is-show', showFloat);
    float && float.setAttribute('aria-hidden', showFloat ? 'false' : 'true');
    totop && totop.classList.toggle('is-show', y > 400);
  };
  unbind.push(on(window, 'scroll', throttle(update, 80), { passive: true }));
  unbind.push(on(window, 'resize', throttle(update, 200)));
  update();
  if (totop) unbind.push(on(totop, 'click', () => window.scrollTo({ top: 0, behavior: 'smooth' })));

  const drawer = qs('[data-drawer]');
  if (drawer) {
    qsa('[data-drawer-open]').forEach((b) => unbind.push(on(b, 'click', () => { if (!drawer.open) drawer.showModal(); })));
    qsa('[data-drawer-close]', drawer).forEach((b) => unbind.push(on(b, 'click', () => drawer.close())));
    unbind.push(on(drawer, 'click', (e) => { if (e.target === drawer) drawer.close(); }));
    qsa('a', drawer).forEach((a) => unbind.push(on(a, 'click', () => drawer.close())));
  }
  // 子菜单键盘可达
  qsa('.n-menu-item.has-children > a').forEach((a) => {
    unbind.push(on(a, 'keydown', (e) => { if (e.key === 'ArrowDown') { e.preventDefault(); const f = a.parentElement.querySelector('.n-submenu a'); f && f.focus(); } }));
  });
}

export function destroy() { unbind.forEach((f) => f()); unbind = []; }
