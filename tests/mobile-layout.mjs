// Browser regression tests against the real local Typecho site (bash tests/serve.sh).
// Setup: npm install --prefix .dev/browser --no-save playwright
//        .dev/browser/node_modules/.bin/playwright install webkit
// Run: node --test tests/mobile-layout.mjs (requires Chrome, or set CHROME).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';

const require = createRequire(new URL('../.dev/browser/package.json', import.meta.url));
const { chromium, webkit } = require('playwright');
const base = process.env.BASE || 'http://localhost:8099';
const shots = process.env.NIMBUS_SHOTS;
if (shots) await mkdir(shots, { recursive: true });

for (const [name, engine] of Object.entries({ chromium, webkit })) {
  test(`${name}: mobile layout`, async (t) => {
    const browser = await engine.launch({
      headless: true,
      ...(name === 'chromium' ? { executablePath: process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' } : {}),
    });
    t.after(() => browser.close());
    for (const [width, height] of [[320, 568], [375, 667], [390, 700], [430, 932], [844, 390]]) {
      await t.test(`${width} × ${height}`, async (t) => {
        const page = await browser.newPage({ viewport: { width, height }, isMobile: true, hasTouch: true });
        page.setDefaultTimeout(10000);
        t.after(() => page.close());
        await page.route('**/*', (route) => new URL(route.request().url()).origin === new URL(base).origin ? route.continue() : route.abort());
        await page.goto(base, { waitUntil: 'domcontentloaded' });
        await page.waitForFunction(() => window.NimbusConfig && document.querySelector('.n-bento-feature').style.getPropertyValue('--carousel-ms'));
        await page.waitForFunction(() => document.fonts.status === 'loaded');
        await page.evaluate(() => {
          document.documentElement.dataset.motion = 'off';
          document.querySelectorAll('[data-carousel]').forEach((el) => el.dispatchEvent(new Event('mouseenter')));
          document.querySelectorAll('.n-bento-feature-title').forEach((el) => {
            el.textContent = '奇点临近：一个程序员在加速时代的心白，以及面对技术变化时的思考与选择';
          });
        });

        await t.test('long featured titles and enlarged text stay inside the card', async () => {
          for (const scale of [1, 1.6]) {
            await page.evaluate((scale) => {
              const nodes = [...document.querySelectorAll('.n-bento-feature-body *')];
              nodes.forEach((el) => el.style.fontSize = '');
              const sizes = nodes.map((el) => parseFloat(getComputedStyle(el).fontSize));
              nodes.forEach((el, i) => el.style.fontSize = `${sizes[i] * scale}px`);
            }, scale);
            await page.locator('.n-bento-feature').scrollIntoViewIfNeeded();
            if (shots && width === 390) await page.locator('.n-bento-feature').screenshot({ path: resolve(shots, `${name}-feature-${scale}.png`) });
            const layout = await page.evaluate(() => {
              const tile = document.querySelector('.n-bento-feature');
              const card = tile.getBoundingClientRect();
              const slide = tile.querySelector('.n-slide.is-on');
              const body = slide.querySelector('.n-bento-feature-body').getBoundingClientRect();
              const title = slide.querySelector('.n-bento-feature-title');
              const titleRect = title.getBoundingClientRect();
              return {
                contentFits: body.top >= card.top - 1 && body.bottom <= card.bottom + 1,
                titleFits: titleRect.top >= card.top + 12 && titleRect.bottom <= card.bottom - 12,
                titleHeight: titleRect.height,
                lineHeight: parseFloat(getComputedStyle(title).lineHeight),
                horizontalFits: [...slide.querySelectorAll('.n-bento-feature-body, .n-bento-feature-meta, .n-bento-go')].every((el) => {
                  const r = el.getBoundingClientRect();
                  return r.left >= card.left && r.right <= card.right + 1;
                }),
              };
            });
            assert.ok(layout.contentFits, `featured content is clipped at ${scale}× text size`);
            assert.ok(layout.titleFits, 'title is partly outside the card');
            assert.ok(layout.horizontalFits, 'metadata or read button overflows horizontally');
            assert.ok(layout.titleHeight <= layout.lineHeight * 2 + 1, 'long title should stop after two complete lines');
          }
        });

        await t.test('long menus scroll inside the panel and keep close/footer controls visible', async () => {
          await page.evaluate(() => {
            const menu = document.querySelector('.n-drawer-menu');
            const template = menu.firstElementChild;
            const items = Array.from({ length: 28 }, (_, i) => {
              const li = template.cloneNode(true);
              li.className = 'n-menu-item';
              li.querySelector('.n-menu-label').textContent = `第 ${i + 1} 项：这是一个很长的导航菜单名称，用来检查手机端边界`;
              return li;
            });
            const sub = document.createElement('ul');
            sub.className = 'n-submenu';
            const child = items[1].cloneNode(true);
            child.querySelector('.n-menu-label').textContent = 'Agent 从入门到放弃：一个很长的课程子菜单标题';
            sub.append(child);
            items[0].append(sub);
            menu.replaceChildren(...items);
            window.scrollTo(0, 0);
          });
          await page.locator('[data-nav] [data-drawer-open]').click();
          await page.locator('[data-drawer][open]').waitFor();
          await page.evaluate(() => {
            const menu = document.querySelector('.n-drawer-menu');
            menu.scrollTop = menu.scrollHeight;
          });
          if (shots && width === 390) await page.screenshot({ path: resolve(shots, `${name}-drawer.png`) });
          const layout = await page.evaluate(() => {
            const panel = document.querySelector('.n-drawer-in');
            const bounds = panel.getBoundingClientRect();
            const menu = panel.querySelector('.n-drawer-menu');
            const view = menu.getBoundingClientRect();
            const last = menu.lastElementChild.getBoundingClientRect();
            const controls = ['.n-drawer-head', '.n-drawer-foot'].map((s) => panel.querySelector(s).getBoundingClientRect());
            return {
              panelFits: bounds.top >= 0 && bounds.bottom <= window.innerHeight + 1,
              menuFits: view.top >= bounds.top && view.bottom <= bounds.bottom,
              canScroll: menu.scrollTop > 0,
              lastVisible: last.top >= view.top - 1 && last.bottom <= view.bottom + 1,
              controlsVisible: controls.every((r) => r.top >= bounds.top && r.bottom <= bounds.bottom + 1),
              horizontalFits: panel.scrollWidth <= panel.clientWidth && menu.scrollWidth <= menu.clientWidth,
            };
          });
          assert.ok(layout.panelFits && layout.menuFits, 'menu extends beyond the visible panel');
          assert.ok(layout.canScroll && layout.lastVisible, 'last menu item cannot be scrolled into view');
          assert.ok(layout.controlsVisible, 'close button or footer is pushed off screen');
          assert.ok(layout.horizontalFits, 'long labels overflow horizontally');
          await page.locator('[data-drawer-close]').click();
          assert.equal(await page.locator('[data-drawer]').getAttribute('open'), null);
        });
      });
    }
  });
}
