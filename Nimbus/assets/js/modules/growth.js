// Optional, first-party aggregate collection. Navigation never waits for analytics.
let removeClick = null;

function destination(raw, base) {
  try {
    const url = new URL(raw, base);
    if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return null;
    url.search = ''; url.hash = '';
    return url.href.replace(/\/$/, '');
  } catch { return null; }
}

function browserContext() {
  return { document, location: window.location, navigator, config: window.NimbusConfig || {}, fetch: window.fetch?.bind(window) };
}

export function init(context = browserContext()) {
  if (removeClick) { removeClick(); removeClick = null; }
  const { document: doc, location, navigator: nav, config: cfg, fetch: sendFetch } = context;
  if (!cfg.growth || !cfg.api || typeof sendFetch !== 'function' || nav.doNotTrack === '1' || nav.globalPrivacyControl === true) return;
  let current, endpoint;
  try {
    current = new URL(location.href);
    endpoint = new URL(`${cfg.api.replace(/\/$/, '')}/growth`, current);
    if (endpoint.origin !== current.origin) return;
  } catch { return; }
  const candidate = current.searchParams.get('utm_source') || '';
  const sources = ['direct', 'juejin', 'wechat', 'weixin', 'xiaohongshu', 'xhs', 'github', 'v2ex', 'google', 'bing', 'baidu', 'other'];
  const utm = candidate ? (sources.includes(candidate.toLowerCase()) ? candidate.toLowerCase() : 'other') : '';
  let referrer = '';
  try { referrer = doc.referrer ? new URL(doc.referrer).hostname : ''; } catch { /* Missing referrer is expected. */ }
  const goals = (Array.isArray(cfg.growth.goals) ? cfg.growth.goals : []).map(goal => ({ key: goal.key, url: destination(goal.url, cfg.site || current.href) })).filter(goal => goal.url);
  const send = (kind, goal) => {
    const body = new URLSearchParams({ kind, utm, referrer });
    if (goal) body.set('goal', goal);
    try {
      Promise.resolve(sendFetch(endpoint.href, {
        method: 'POST', credentials: 'same-origin', keepalive: true, referrerPolicy: 'origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', Accept: 'application/json' }, body: body.toString(),
      })).catch(() => {});
    } catch { /* Collection failure must never stop reading or following links. */ }
  };
  send('entry');
  const click = event => {
    if (event.defaultPrevented || (event.button !== undefined && event.button !== 0)) return;
    const anchor = event.target?.closest?.('a[href]');
    if (!anchor || anchor.hasAttribute('download')) return;
    const url = destination(anchor.href, current.href);
    const goal = goals.find(g => g.url === url);
    if (goal) send('goal', goal.key);
  };
  doc.addEventListener('click', click, true);
  removeClick = () => doc.removeEventListener('click', click, true);
}
