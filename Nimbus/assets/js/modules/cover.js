// 杂志风首页交互：卡片堆随鼠标倾斜、Bento 卡片光斑跟随指针、音乐卡片控制全站播放器
import { qs, qsa, on, prefersReduced, config } from './util.js';
import { isAllowedOnDevice } from './media-policy.js';

let offs = [], unsub = null;

export function init(root = document) {
  destroy();
  const stack = qs('[data-cover-stack]', root);
  if (stack && !prefersReduced() && matchMedia('(pointer: fine)').matches) {
    let raf = 0;
    offs.push(on(stack.closest('[data-cover]') || stack, 'pointermove', (e) => {
      if (raf) return;
      raf = requestAnimationFrame(() => {
        raf = 0;
        const r = stack.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
        stack.style.setProperty('--rx', (x * 14).toFixed(2) + 'deg');
        stack.style.setProperty('--ry', (-y * 10).toFixed(2) + 'deg');
      });
    }));
    offs.push(on(stack.closest('[data-cover]') || stack, 'pointerleave', () => { stack.style.removeProperty('--rx'); stack.style.removeProperty('--ry'); }));
  }
  qsa('.n-bento-tile', root).forEach((t) => offs.push(on(t, 'pointermove', (e) => {
    const r = t.getBoundingClientRect();
    t.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    t.style.setProperty('--my', (e.clientY - r.top) + 'px');
  })));
  const music = qs('[data-bento-music]', root);
  if (music && isAllowedOnDevice(config().music)) {
    import('./player.js').then(async (P) => {
      await P.init();
      const btn = qs('[data-bento-play]', music);
      if (btn) offs.push(on(btn, 'click', (e) => { e.preventDefault(); P.toggle(); }));
      const title = qs('[data-bento-title]', music), artist = qs('[data-bento-artist]', music), cover = qs('[data-bento-cover]', music), state = qs('[data-bento-state]', music);
      const idle = state ? state.textContent : '';
      unsub = P.subscribe((S) => {
        const t = P.track(); if (!t || !S.audio) return;
        const playing = !S.audio.paused;
        music.classList.toggle('is-playing', playing);
        if (title) title.textContent = t.title;
        if (artist) artist.textContent = t.artist;
        if (cover && t.cover) cover.style.backgroundImage = `url("${t.cover.replace(/"/g, '%22')}")`;
        if (state) state.textContent = playing ? (state.dataset.on || 'Now playing') : idle;
      });
    }).catch(() => {});
  }
}

export function destroy() { offs.forEach((f) => f()); offs = []; if (unsub) unsub(); unsub = null; }
