// 滚动进场：.n-reveal → .is-in，视口内按序错落
let io = null;
export function init(root = document) {
  if (!('IntersectionObserver' in window) || document.documentElement.dataset.motion === 'off') {
    root.querySelectorAll('.n-reveal').forEach((el) => el.classList.add('is-in'));
    return;
  }
  if (!io) {
    io = new IntersectionObserver((entries) => {
      let i = 0;
      entries.forEach((e) => {
        if (!e.isIntersecting) return;
        e.target.style.transitionDelay = (i++ % 4) * 80 + 'ms';
        e.target.classList.add('is-in');
        io.unobserve(e.target);
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
  }
  root.querySelectorAll('.n-reveal:not(.is-in)').forEach((el) => io.observe(el));
}
