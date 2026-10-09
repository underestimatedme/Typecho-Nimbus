// 首页 / 列表页模块
import * as hero from '../modules/hero.js';
import * as carousel from '../modules/carousel.js';
import * as infinite from '../modules/infinite.js';

export async function init(root = document) {
  hero.init(root);
  carousel.init(root);
  infinite.init(root);
}
export function destroy() { hero.destroy(); carousel.destroy(); infinite.destroy(); }
