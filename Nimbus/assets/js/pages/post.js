// 文章 / 页面模块
import * as toc from '../modules/toc.js';
import * as code from '../modules/code.js';
import * as lightbox from '../modules/lightbox.js';
import * as math from '../modules/math.js';
import * as mermaid from '../modules/mermaid.js';
import * as share from '../modules/share.js';
import * as comment from '../modules/comment.js';
import * as hero from '../modules/hero.js';
import * as like from '../modules/like.js';
import * as views from '../modules/views.js';

export async function init(root = document) {
  hero.init(root);
  like.init(root);
  views.init();
  toc.init(root);
  share.init(root);
  comment.init(root);
  const cfg = window.NimbusConfig || {};
  if (cfg.lightbox !== false) lightbox.init(root);
  await Promise.all([code.init(root), math.init(root), mermaid.init(root)]);
}
export function destroy() { like.destroy(); hero.destroy(); toc.destroy(); share.destroy(); comment.destroy(); lightbox.destroy(); mermaid.destroy(); }
