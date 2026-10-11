<?php
/**
 * 时间线
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
use Nimbus\Options;

$o = \Helper::options();
// 全部文章逐条取分类、字段与摘要开销大，结果按文章指纹缓存（文章或相关设置变化时自动重建）
$cacheKey = \Nimbus\Cache::postsKey([(string)$o->index, (string)$o->siteUrl, json_encode(is_array($o->routingTable) ? ($o->routingTable['post'] ?? '') : ''), (string)Options::get('ThumbPool', ''), (string)Options::get('ExcerptLength', '')]);
$items = \Nimbus\Cache::remember('timeline', $cacheKey, function () {
    $posts = \Typecho_Widget::widget('Widget_Contents_Post_Recent@nimbus-timeline', 'pageSize=10000');
    $items = [];
    while ($posts->next()) {
        $cat = isset($posts->categories[0]) ? $posts->categories[0]['name'] : '';
        $items[] = ['title' => View::t($posts->title), 'url' => $posts->permalink, 'created' => (int)$posts->created, 'cat' => $cat, 'excerpt' => Post::excerpt($posts, 70), 'thumb' => Post::hasOwnImage($posts) ? Post::thumb($posts) : '', 'comments' => (int)$posts->commentsNum];
    }
    return $items;
});
if (!is_array($items)) $items = [];
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-timeline-page">
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('clock') ?> <?= I18n::e('时间线') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= I18n::e('时光河流 · 共 {n} 篇文章', ['n' => count($items)]) ?></p></header>
    <?php if (empty($items)): ?>
      <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('还没有文章。') ?></p></div>
    <?php else:
      $years = [];
      foreach ($items as $it) $years[View::date('Y', $it['created'])][] = $it;
    ?>
    <div class="n-tl" data-river>
      <span class="n-tl-spine" aria-hidden="true"><i data-tl-fill></i></span>
      <?php $k = 0; foreach ($years as $y => $list): ?>
      <section class="n-tl-year">
        <h2 class="n-tl-year-head"><span><?= View::e((string)$y) ?></span><small><?= I18n::e('{n} 篇', ['n' => count($list)]) ?></small></h2>
        <ol class="n-tl-list">
          <?php foreach ($list as $it): $side = $k++ % 2 ? 'is-right' : 'is-left'; ?>
          <li class="n-tl-item <?= $side ?> n-reveal">
            <span class="n-tl-dot" aria-hidden="true"></span>
            <time class="n-tl-date" datetime="<?= View::date('c', $it['created']) ?>"><b><?= View::date('m.d', $it['created']) ?></b><span><?= View::date('H:i', $it['created']) ?></span></time>
            <a class="n-tl-card" href="<?= View::e($it['url']) ?>">
              <?php if ($it['thumb'] !== ''): ?><span class="n-tl-thumb" style="background-image:url('<?= View::e($it['thumb']) ?>')"></span><?php endif; ?>
              <span class="n-tl-body">
                <b><?= View::e($it['title']) ?></b>
                <?php if ($it['excerpt'] !== ''): ?><em><?= View::e($it['excerpt']) ?></em><?php endif; ?>
                <span class="n-tl-meta"><?php if ($it['cat'] !== ''): ?><span class="n-chip"><?= View::e($it['cat']) ?></span><?php endif; ?><span><?= View::icon('comment') ?><?= (int)$it['comments'] ?></span><span class="n-tl-go"><?= View::icon('arrow-right') ?></span></span>
              </span>
            </a>
          </li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endforeach; ?>
      <div class="n-tl-end" aria-hidden="true"><span><?= View::icon('star') ?></span></div>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
