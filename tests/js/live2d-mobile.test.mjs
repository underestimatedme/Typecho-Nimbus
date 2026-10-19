import { test } from 'node:test';
import assert from 'node:assert/strict';

test('Live2D idle load rechecks the mobile setting before requesting its script', async () => {
  const previous = {
    window: globalThis.window,
    document: globalThis.document,
    matchMedia: globalThis.matchMedia,
    requestIdleCallback: globalThis.requestIdleCallback,
  };
  let mobile = false;
  let idle;
  const scripts = [];
  globalThis.window = { NimbusConfig: { live2d: { script: 'https://cdn.example.com/live2d.js', mobile: false } } };
  globalThis.matchMedia = (query) => ({ matches: query.includes('prefers-reduced-motion') ? false : mobile });
  globalThis.requestIdleCallback = (fn) => { idle = fn; };
  globalThis.window.requestIdleCallback = globalThis.requestIdleCallback;
  globalThis.document = {
    createElement: () => ({ set src(value) { this.url = value; } }),
    head: { appendChild: (script) => scripts.push(script) },
    documentElement: { classList: { add() {} } },
  };

  try {
    const live2d = await import(`../../Nimbus/assets/js/modules/live2d.js?mobile-test=${Date.now()}`);
    live2d.init();
    assert.equal(typeof idle, 'function');
    mobile = true;
    idle();
    assert.equal(scripts.length, 0);
  } finally {
    for (const [key, value] of Object.entries(previous)) {
      if (value === undefined) delete globalThis[key];
      else globalThis[key] = value;
    }
  }
});
