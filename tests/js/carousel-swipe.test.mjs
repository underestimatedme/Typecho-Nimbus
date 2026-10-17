import { test } from 'node:test';
import assert from 'node:assert/strict';
import { init, destroy } from '../../Nimbus/assets/js/modules/carousel.js';

class FakeElement {
  constructor() {
    this.listeners = new Map();
    this.dataset = { interval: '60000' };
    this.attributes = {};
    this.classes = new Set();
    this.classList = {
      toggle: (name, on) => on ? this.classes.add(name) : this.classes.delete(name),
      add: (name) => this.classes.add(name),
      remove: (name) => this.classes.delete(name),
    };
    this.style = { setProperty() {} };
    this.slides = [new FakeElementChild(), new FakeElementChild(), new FakeElementChild()];
    this.slides[0].classList.add('is-on');
    this.dots = [];
  }

  querySelectorAll(selector) {
    if (selector === '[data-carousel]') return [this];
    if (selector === '.n-slide') return this.slides;
    if (selector === '[data-dot]') return this.dots;
    return [];
  }

  querySelector() { return null; }
  addEventListener(type, callback, options) {
    const listeners = this.listeners.get(type) || [];
    listeners.push({ callback, options });
    this.listeners.set(type, listeners);
  }
  removeEventListener(type, callback) {
    this.listeners.set(type, (this.listeners.get(type) || []).filter((item) => item.callback !== callback));
  }
  dispatch(type, event) {
    for (const { callback } of this.listeners.get(type) || []) callback(event);
  }
}

class FakeElementChild {
  constructor() {
    this.classes = new Set();
    this.attributes = {};
    this.classList = {
      toggle: (name, on) => on ? this.classes.add(name) : this.classes.delete(name),
      add: (name) => this.classes.add(name),
      remove: (name) => this.classes.delete(name),
    };
  }
  setAttribute(name, value) { this.attributes[name] = value; }
  get offsetWidth() { return 0; }
}

function touch(x, y = 0) { return { clientX: x, clientY: y }; }
function mount() {
  const carousel = new FakeElement();
  init(carousel);
  return carousel;
}

test('left swipe advances to the next featured article', () => {
  const carousel = mount();
  try {
    carousel.dispatch('touchstart', { touches: [touch(180)] });
    carousel.dispatch('touchend', { changedTouches: [touch(100)] });
    assert.equal(carousel.slides[1].classes.has('is-on'), true);
  } finally { destroy(); }
});

test('right swipe returns to the previous featured article', () => {
  const carousel = mount();
  try {
    carousel.dispatch('touchstart', { touches: [touch(100)] });
    carousel.dispatch('touchend', { changedTouches: [touch(180)] });
    assert.equal(carousel.slides[2].classes.has('is-on'), true);
  } finally { destroy(); }
});

test('a horizontal swipe suppresses the click generated at touch end', () => {
  const carousel = mount();
  try {
    carousel.dispatch('touchstart', { touches: [touch(180)] });
    carousel.dispatch('touchend', { changedTouches: [touch(100)] });
    const click = {
      prevented: false,
      stopped: false,
      preventDefault() { this.prevented = true; },
      stopImmediatePropagation() { this.stopped = true; },
    };
    carousel.dispatch('click', click);
    assert.equal(click.prevented, true);
    assert.equal(click.stopped, true);
  } finally { destroy(); }
});

test('vertical movement does not change slides or suppress the following click', () => {
  const carousel = mount();
  try {
    carousel.dispatch('touchstart', { touches: [touch(100, 0)] });
    carousel.dispatch('touchend', { changedTouches: [touch(150, 100)] });
    assert.equal(carousel.slides[0].classes.has('is-on'), true);
    const click = { prevented: false, preventDefault() { this.prevented = true; }, stopImmediatePropagation() {} };
    carousel.dispatch('click', click);
    assert.equal(click.prevented, false);
  } finally { destroy(); }
});
