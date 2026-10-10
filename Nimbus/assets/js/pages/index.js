// 首页 / 列表页模块
import * as hero from '../modules/hero.js';
import * as carousel from '../modules/carousel.js';
import * as infinite from '../modules/infinite.js';
import * as cover from '../modules/cover.js';

export async function init(root = document) {
  hero.init(root);
  carousel.init(root);
  infinite.init(root);
  cover.init(root);
}
export function destroy() { hero.destroy(); carousel.destroy(); infinite.destroy(); cover.destroy(); }
