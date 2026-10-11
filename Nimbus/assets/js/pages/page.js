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
import * as infinite from '../modules/infinite.js';
import * as bookmarks from '../modules/bookmarks.js';
import * as river from '../modules/river.js';
import * as shortcode from '../modules/shortcode.js';
import * as musicPage from '../modules/music-page.js';
import * as stats from '../modules/stats.js';
import { on } from '../modules/util.js';
let offAppend = null;

export async function init(root = document) {
  hero.init(root);
  like.init(root);
  views.init();
  infinite.init(root);
  bookmarks.init(root);
  river.init(root);
  shortcode.init(root);
  musicPage.init(root);
  stats.init(root);
  offAppend = on(document, 'nimbus:append', (e) => { like.init(e.detail.root); if ((window.NimbusConfig || {}).lightbox !== false) lightbox.init(document); });
  toc.init(root);
  share.init(root);
  comment.init(root);
  const cfg = window.NimbusConfig || {};
  if (cfg.lightbox !== false) lightbox.init(root);
  await Promise.all([code.init(root), math.init(root), mermaid.init(root)]);
}
export function destroy() { if (offAppend) offAppend(); offAppend = null; infinite.destroy(); bookmarks.destroy(); river.destroy(); shortcode.destroy(); musicPage.destroy(); stats.destroy(); like.destroy(); hero.destroy(); toc.destroy(); share.destroy(); comment.destroy(); lightbox.destroy(); mermaid.destroy(); }
