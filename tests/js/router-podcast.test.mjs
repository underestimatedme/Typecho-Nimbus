import { test } from 'node:test';
import assert from 'node:assert/strict';
import { navigate } from '../../Nimbus/assets/js/modules/router.js';

for (const podcast of [true, false]) {
  test(podcast ? 'podcast destinations reload before replacing content, including post-to-post navigation' : 'ordinary destinations retain soft navigation', async () => {
    const keys = ['window', 'document', 'location', 'history', 'fetch', 'DOMParser', 'matchMedia', 'CustomEvent'];
    const previous = Object.fromEntries(keys.map(key => [key, globalThis[key]]));
    let swaps = 0, pushes = 0, pages = 0;
    const target = 'https://blog.example/posts/listen';
    const main = { querySelectorAll: () => [] };
    const next = {
      title: 'Next post', body: { className: 'post', dataset: { page: 'post', cid: '2' } },
      getElementById: () => null,
      querySelector: selector => selector === '#n-main' ? main : selector === '.momobird-podcast' && podcast ? {} : null,
    };
    globalThis.location = { href: 'https://blog.example/posts/previous', origin: 'https://blog.example' };
    globalThis.window = { NimbusConfig: {} };
    globalThis.document = {
      title: 'Previous post', body: { className: 'post', dataset: {} },
      getElementById: () => null,
      querySelector: selector => selector === '#n-main' ? { replaceWith: () => { swaps++; } } : null,
      dispatchEvent: () => { pages++; },
    };
    globalThis.history = { pushState: () => { pushes++; } };
    globalThis.window.scrollTo = () => {};
    globalThis.fetch = async () => ({ ok: true, url: target, headers: { get: () => 'text/html' }, text: async () => '<html></html>' });
    globalThis.DOMParser = class { parseFromString() { return next; } };
    globalThis.CustomEvent = class {};
    try {
      await navigate(target);
      assert.equal(swaps, podcast ? 0 : 1, 'do not leave plugin placeholders on a partially replaced page');
      assert.equal(pushes, podcast ? 0 : 1);
      assert.equal(pages, podcast ? 0 : 1);
      assert.equal(globalThis.location.href, podcast ? target : 'https://blog.example/posts/previous');
      assert.equal(globalThis.document.title, podcast ? 'Previous post' : 'Next post');
    } finally {
      for (const key of keys) {
        if (previous[key] === undefined) delete globalThis[key];
        else globalThis[key] = previous[key];
      }
    }
  });
}
