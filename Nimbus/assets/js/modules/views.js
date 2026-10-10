// 浏览量上报：每个会话每篇文章一次（服务端另有 6 小时 cookie 去重）
import { qsa, config } from './util.js';
export async function init() {
  const { api, features = [], cid, page } = config();
  if (!api || !features.includes('views') || page !== 'post' || !cid) return;
  const key = 'nimbus-viewed-' + cid;
  try { if (sessionStorage.getItem(key)) return; sessionStorage.setItem(key, '1'); } catch (e) {}
  try {
    const r = await fetch(api + '/view', { method: 'POST', body: new URLSearchParams({ cid: String(cid) }), credentials: 'same-origin', headers: { Accept: 'application/json' } });
    const d = await r.json();
    if (d.ok && d.data.counted) qsa('[data-views]').forEach((el) => { el.textContent = String(d.data.views); });
  } catch (e) {}
}
export function destroy() {}
