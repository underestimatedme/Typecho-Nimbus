// QR 编码器结构测试；解码正确性已在浏览器 BarcodeDetector 中对版本 1–15（含中文）逐一验证
import test from 'node:test';
import assert from 'node:assert/strict';
import { qrMatrix } from '../../Nimbus/assets/js/lib/qr.js';

const finderOk = (m, x0, y0) => {
  for (let y = 0; y < 7; y++) for (let x = 0; x < 7; x++) {
    const d = Math.max(Math.abs(x - 3), Math.abs(y - 3));
    if (m[y0 + y][x0 + x] !== (d !== 2)) return false;
  }
  return true;
};

test('版本随内容长度增长', () => {
  assert.equal(qrMatrix('a').length, 21);
  assert.equal(qrMatrix('x'.repeat(30)).length, 29);
  assert.equal(qrMatrix('x'.repeat(200)).length, 4 * 10 + 17);
  assert.equal(qrMatrix('x'.repeat(400)).length, 4 * 15 + 17);
});

test('三个定位图形与时序图形', () => {
  for (const s of ['https://example.com/', '中文链接'.repeat(20)]) {
    const m = qrMatrix(s), n = m.length;
    assert.ok(finderOk(m, 0, 0) && finderOk(m, n - 7, 0) && finderOk(m, 0, n - 7));
    for (let i = 8; i < n - 8; i++) { assert.equal(m[6][i], i % 2 === 0); assert.equal(m[i][6], i % 2 === 0); }
    assert.equal(m[n - 8][8], true, '固定暗模块');
  }
});

test('相同输入结果确定，超长输入报错', () => {
  assert.deepEqual(qrMatrix('nimbus'), qrMatrix('nimbus'));
  assert.throws(() => qrMatrix('x'.repeat(500)));
});
