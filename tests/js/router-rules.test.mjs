import { test } from 'node:test';
import assert from 'node:assert/strict';
import { shouldIntercept } from '../../Nimbus/assets/js/modules/router-rules.js';

const loc = { origin: 'http://me.com', pathname: '/', hash: '', href: 'http://me.com/' };
const a = (href, extra = {}) => ({ href, target: '', download: '', hash: '', hasAttribute: (n) => extra[n] !== undefined, ...extra });

test('same origin page', () => assert.equal(shouldIntercept(a('http://me.com/archives/1/'), loc, '/admin/'), true));
test('external', () => assert.equal(shouldIntercept(a('http://x.com/'), loc, '/admin/'), false));
test('blank target', () => assert.equal(shouldIntercept(a('http://me.com/a', { target: '_blank' }), loc, '/admin/'), false));
test('self target ok', () => assert.equal(shouldIntercept(a('http://me.com/a', { target: '_self' }), loc, '/admin/'), true));
test('download', () => assert.equal(shouldIntercept(a('http://me.com/a.zip'), loc, '/admin/'), false));
test('download attr', () => assert.equal(shouldIntercept(a('http://me.com/a', { download: '' }), loc, '/admin/'), false));
test('admin', () => assert.equal(shouldIntercept(a('http://me.com/admin/'), loc, '/admin/'), false));
test('hash only same page', () => assert.equal(shouldIntercept(a('http://me.com/#top', { hash: '#top' }), loc, '/admin/'), false));
test('hash on other page intercepts', () => assert.equal(shouldIntercept(a('http://me.com/a/#x', { hash: '#x' }), loc, '/admin/'), true));
test('data-no-router', () => assert.equal(shouldIntercept(a('http://me.com/a', { 'data-no-router': true }), loc, '/admin/'), false));
test('mailto', () => assert.equal(shouldIntercept(a('mailto:a@b.com'), loc, '/admin/'), false));
test('feed xml', () => assert.equal(shouldIntercept(a('http://me.com/feed/'), loc, '/admin/'), false));
test('image', () => assert.equal(shouldIntercept(a('http://me.com/usr/uploads/a.png'), loc, '/admin/'), false));
test('action url', () => assert.equal(shouldIntercept(a('http://me.com/index.php/action/logout'), loc, '/admin/'), false));
