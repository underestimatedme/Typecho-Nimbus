// 无刷新跳转的纯判定逻辑（可单测）
const SKIP_EXT = /\.(pdf|zip|rar|7z|gz|tar|jpe?g|png|gif|webp|svg|avif|mp3|mp4|webm|xml|txt|json|css|js|woff2?|ttf)(\?|#|$)/i;

export function shouldIntercept(anchor, location, adminPath) {
  const href = anchor.href || '';
  if (!href || /^(mailto|tel|javascript|data):/i.test(href)) return false;
  if (anchor.target && anchor.target !== '_self') return false;
  if (anchor.hasAttribute && anchor.hasAttribute('download')) return false;
  if (anchor.hasAttribute && anchor.hasAttribute('data-no-router')) return false;
  let url;
  try { url = new URL(href, location.href || location.origin); } catch (e) { return false; }
  if (url.origin !== location.origin) return false;
  if (adminPath && url.pathname.startsWith(adminPath)) return false;
  if (SKIP_EXT.test(url.pathname)) return false;
  if (/\/(feed|action)(\/|$)/.test(url.pathname)) return false;
  if (url.hash && url.pathname === location.pathname && url.search === (location.search || '')) return false;
  return true;
}
