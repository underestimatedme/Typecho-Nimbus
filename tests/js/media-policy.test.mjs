import { test } from 'node:test';
import assert from 'node:assert/strict';
import { isAllowedOnDevice } from '../../Nimbus/assets/js/modules/media-policy.js';

test('media settings are unrestricted on desktop', () => {
  assert.equal(isAllowedOnDevice({ mobile: false }, false), true);
});

test('mobile media setting blocks loading when disabled', () => {
  assert.equal(isAllowedOnDevice({ mobile: false }, true), false);
});

test('mobile media setting permits loading when enabled', () => {
  assert.equal(isAllowedOnDevice({ mobile: true }, true), true);
});

test('missing media configuration remains disabled', () => {
  assert.equal(isAllowedOnDevice(null, false), false);
});
