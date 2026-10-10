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

$posts = \Typecho_Widget::widget('Widget_Contents_Post_Recent', 'pageSize=10000');
$items = [];
while ($posts->next()) {
    $cat = isset($posts->categories[0]) ? $posts->categories[0]['name'] : '';
    $items[] = ['title' => View::t($posts->title), 'url' => $posts->permalink, 'created' => (int)$posts->created, 'cat' => $cat, 'excerpt' => Post::excerpt($posts, 70), 'thumb' => Post::hasOwnImage($posts) ? Post::thumb($posts) : '', 'comments' => (int)$posts->commentsNum];
}
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-timeline-page">
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('clock') ?> <?= I18n::e('时间线') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= I18n::e('时光河流 · 共 {n} 篇文章', ['n' => count($items)]) ?></p></header>
    <?php if (empty($items)): ?>
      <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('还没有文章。') ?></p></div>
    <?php else: ?>
    <div class="n-river-wrap">
      <button type="button" class="n-river-nav n-river-prev" data-river-prev aria-label="<?= I18n::e('向前') ?>"><?= View::icon('arrow-left') ?></button>
      <div class="n-river" data-river tabindex="0" aria-label="<?= I18n::e('文章时间线，可左右滚动') ?>">
        <div class="n-river-track">
          <?php $lastYear = null; foreach ($items as $i => $it): $y = date('Y', $it['created']); ?>
          <?php if ($y !== $lastYear): $lastYear = $y; ?><div class="n-river-year"><span><?= $y ?></span></div><?php endif; ?>
          <a class="n-river-item<?= $i % 2 ? ' is-down' : '' ?>" href="<?= View::e($it['url']) ?>">
            <i class="n-river-dot" aria-hidden="true"></i>
            <span class="n-river-card">
              <?php if ($it['thumb'] !== ''): ?><span class="n-river-thumb" style="background-image:url('<?= View::e($it['thumb']) ?>')"></span><?php endif; ?>
              <time datetime="<?= date('c', $it['created']) ?>"><?= date('Y.n.j', $it['created']) ?></time>
              <b><?= View::e($it['title']) ?></b>
              <em><?= View::e($it['excerpt']) ?></em>
              <span class="n-river-meta"><?php if ($it['cat'] !== ''): ?><span class="n-chip"><?= View::e($it['cat']) ?></span><?php endif; ?><span><?= View::icon('comment') ?><?= $it['comments'] ?></span></span>
            </span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="button" class="n-river-nav n-river-next" data-river-next aria-label="<?= I18n::e('向后') ?>"><?= View::icon('arrow-right') ?></button>
    </div>
    <p class="n-river-hint"><?= I18n::e('左右滑动、拖拽或使用方向键浏览 · 点击卡片阅读') ?></p>
    <?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
