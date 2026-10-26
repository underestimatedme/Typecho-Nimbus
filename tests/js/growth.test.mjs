import { test } from 'node:test';
import assert from 'node:assert/strict';

async function runtime(overrides = {}) {
  const listeners = new Map();
  const sent = [];
  const doc = { referrer: 'https://link.juejin.cn/?target=private', addEventListener: (kind, fn) => listeners.set(kind, fn), removeEventListener: (kind, fn) => { if (listeners.get(kind) === fn) listeners.delete(kind); } };
  const context = { document: doc, location: { href: 'https://blog.example/?utm_source=juejin&token=secret' }, navigator: {}, fetch: async (url, options) => { sent.push({url,options}); return {ok:true}; }, config: { api: 'https://blog.example/nimbus-api', site: 'https://blog.example', growth: { goals: [{key:'code',label:'Source',url:'https://github.com/example/theme'},{key:'intro',label:'Intro',url:'/intro.html'}] } }, ...overrides };
  const mod = await import(`../../Nimbus/assets/js/modules/growth.js?case=${Math.random()}`);
  const click = (url, extra = {}) => { const anchor = {href:url,hasAttribute:name=>name==='download'&&extra.download}; const event = {button:0,defaultPrevented:false,target:{closest:()=>anchor},...extra}; listeners.get('click')?.(event); return event; };
  return { mod, context, listeners, sent, click };
}

test('collector only transmits bounded campaign and referrer host, never the URL query', async () => {
  const r=await runtime();r.mod.init(r.context);
  assert.equal(r.sent.length,1);const sent=r.sent[0];const body=new URLSearchParams(sent.options.body);
  assert.equal(body.get('utm'),'juejin');assert.equal(body.get('referrer'),'link.juejin.cn');
  assert.equal(body.get('kind'),'entry');assert.equal([...body.keys()].sort().join(','),'kind,referrer,utm');
  assert.equal(sent.options.referrerPolicy,'origin');assert.equal(sent.options.credentials,'same-origin');assert.equal(sent.options.keepalive,true);
});
test('configured source and internal goals count without changing navigation', async () => {
  const r=await runtime();r.mod.init(r.context);const event=r.click('https://blog.example/intro.html?utm_source=x#part');
  assert.equal(r.sent.length,2);assert.equal(new URLSearchParams(r.sent[1].options.body).get('goal'),'intro');
  assert.equal(event.defaultPrevented,false);r.click('https://github.com/example/theme');assert.equal(new URLSearchParams(r.sent[2].options.body).get('goal'),'code');
});
test('unconfigured lookalike URLs, downloads and cancelled clicks do not count', async () => {
  const r=await runtime();r.mod.init(r.context);r.click('https://github.com.evil.test/example/theme');r.click('https://blog.example/intro.html',{download:true});r.click('https://blog.example/intro.html',{defaultPrevented:true});
  assert.equal(r.sent.length,1);
});
test('router reinitialization replaces the click listener rather than multiplying counts', async () => {
  const r=await runtime();r.mod.init(r.context);r.mod.init(r.context);const before=r.sent.length;r.click('https://blog.example/intro.html');assert.equal(r.sent.length,before+1);assert.equal(r.listeners.size,1);
});
test('disabled collection, DNT and GPC send no events or click listeners', async () => {
  for(const mode of ['disabled','dnt','gpc']){
    const r=await runtime();if(mode==='disabled')r.context.config.growth=null;if(mode==='dnt')r.context.navigator.doNotTrack='1';if(mode==='gpc')r.context.navigator.globalPrivacyControl=true;
    r.mod.init(r.context);assert.equal(r.sent.length,0);assert.equal(r.listeners.size,0);
  }
});
test('storage-free collection works when tracking transport fails', async () => {
  const r=await runtime({fetch:()=>Promise.reject(new Error('offline'))});assert.doesNotThrow(()=>r.mod.init(r.context));r.click('https://blog.example/intro.html');await new Promise(resolve=>setImmediate(resolve));
});
test('transport never sends analytics to a misconfigured external API', async () => {
  const r=await runtime();r.context.config.api='https://external.example/nimbus-api';r.mod.init(r.context);assert.equal(r.sent.length,0);
});
