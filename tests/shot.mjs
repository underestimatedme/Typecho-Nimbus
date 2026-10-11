// 无头 Chrome 截图（DevTools 协议）：node tests/shot.mjs <输出.png> <URL> [宽] [高] [等待毫秒] [滚动Y]
// 比 --screenshot 稳定：按真实时间等待页面加载，截整页指定高度。
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync, renameSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const [out, url, w = '1440', h = '1200', wait = '2500', scrollY = '0'] = process.argv.slice(2);
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const prof = mkdtempSync(join(tmpdir(), 'nimbus-shot-'));
const port = 9300 + Math.floor(Math.random() * 500);
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--hide-scrollbars', `--remote-debugging-port=${port}`, `--user-data-dir=${prof}`, `--window-size=${w},${h}`, 'about:blank'], { stdio: 'ignore' });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const done = (code) => { try { chrome.kill('SIGKILL'); } catch (e) {} setTimeout(() => { try { rmSync(prof, { recursive: true, force: true }); } catch (e) {} process.exit(code); }, 300); };
setTimeout(() => { console.error("timeout"); done(1); }, 90000);
try {
  let target;
  for (let i = 0; i < 150 && !target; i++) {
    await sleep(200);
    try { target = (await (await fetch(`http://127.0.0.1:${port}/json`)).json()).find((t) => t.type === 'page'); } catch (e) {}
  }
  const ws = new WebSocket(target.webSocketDebuggerUrl);
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));
  let id = 0; const pending = new Map();
  ws.addEventListener('message', (e) => { const m = JSON.parse(e.data); if (m.id && pending.has(m.id)) { pending.get(m.id)(m.result || m.error); pending.delete(m.id); } });
  const send = (method, params = {}) => new Promise((r) => { const i = ++id; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
  await send('Emulation.setDeviceMetricsOverride', { width: +w, height: +h, deviceScaleFactor: 1, mobile: +w < 768 });
  await send('Page.enable');
  await send('Page.navigate', { url });
  await sleep(+wait);
  if (+scrollY) { await send('Runtime.evaluate', { expression: `window.scrollTo(0, ${+scrollY})` }); await sleep(1200); }
  const shot = await send('Page.captureScreenshot', { format: 'png' });
  const tmp = out + '.tmp';
  writeFileSync(tmp, Buffer.from(shot.data, 'base64'));
  renameSync(tmp, out);
  console.log(out);
  done(0);
} catch (e) { console.error(e.message); done(1); }
