// Live2D 看板娘：站长开启后在浏览器空闲时从 CDN 加载第三方挂件（Nimbus 不打包任何相关文件）
// 只加载一次；挂件挂在 body 上，无刷新切换页面不受影响。失败静默。
import { config, loadScript } from './util.js';
import { isAllowedOnDevice } from './media-policy.js';

let started = false, pending = false;

export function init() {
  const c = config().live2d;
  if (!c || !c.script || started || pending || !isAllowedOnDevice(c)) return;
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  pending = true;
  const run = () => {
    pending = false;
    if (!isAllowedOnDevice(c) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    started = true;
    loadScript(c.script).then(() => document.documentElement.classList.add('has-live2d')).catch(() => {});
  };
  if ('requestIdleCallback' in window) requestIdleCallback(run, { timeout: 5000 });
  else setTimeout(run, 2500);
}
