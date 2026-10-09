// 数字滚动计数：[data-count]
export function init(root = document) {
  if (document.documentElement.dataset.motion === 'off') return;
  root.querySelectorAll('[data-count]').forEach((b) => {
    if (b.dataset.counted) return;
    b.dataset.counted = '1';
    const to = parseInt(b.dataset.count, 10) || 0;
    if (to === 0) return;
    let t0;
    const step = (ts) => {
      if (t0 === undefined) t0 = ts;
      const p = Math.min((ts - t0) / 1200, 1);
      b.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))).toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    };
    const io = new IntersectionObserver((es) => { if (es[0].isIntersecting) { io.disconnect(); requestAnimationFrame(step); } });
    io.observe(b);
  });
}
