import test from 'node:test';
import assert from 'node:assert/strict';
import { CORPUS, tokenize, pickLine } from '../../Nimbus/assets/js/modules/code-rain.js';

test('三种语言都有语料', () => {
  for (const k of ['python', 'go', 'rust']) assert.ok(CORPUS[k].length >= 10, k);
});
test('拆分保留每个字符，空格为空位，关键字标记', () => {
  const t = tokenize('fn main() { let x = 1; }', 'rust');
  assert.equal(t.map((c) => c.ch || ' ').join(''), 'fn main() { let x = 1; }');
  assert.ok(t[0].kw && t[1].kw);
  assert.ok(!t[3].kw, 'main 不是关键字');
  assert.equal(t[2].ch, '');
  const p = tokenize('def fib(n): return n', 'python');
  assert.ok(p.filter((c) => c.kw).map((c) => c.ch).join('').includes('defreturn'));
});
test('指定语言只出该语言的代码', () => {
  const all = new Set(CORPUS.go.map((l) => l.replace(/\s/g, '')));
  for (let i = 0; i < 20; i++) assert.ok(all.has(pickLine('go').map((c) => c.ch).join('')));
  assert.ok(pickLine('unknown').length > 0, '未知设置回落到混合');
});
