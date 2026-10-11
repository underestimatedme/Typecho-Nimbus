<?php
/** 置顶轮播。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
$cids = Options::list('TopPosts');
$posts = empty($cids) ? [] : Post::byCids($archive, $cids);
if (empty($posts)) return;
?>
<div class="n-feature n-reveal" data-carousel data-interval="5000" aria-roledescription="<?= I18n::e('轮播') ?>">
  <?php foreach ($posts as $i => $p): ?>
  <a class="n-slide<?= $i === 0 ? ' is-on' : '' ?>" href="<?= View::e($p['permalink']) ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
    <div class="n-slide-bg" style="background-image:url('<?= View::e(Post::rowThumb($p)) ?>')"></div>
    <div class="n-slide-in">
      <span class="n-slide-tag"><?= View::icon('star') ?><?= I18n::e('置顶') ?></span>
      <h3 class="n-slide-title"><?= View::e(View::t($p['title'])) ?></h3>
      <p class="n-slide-desc"><?= View::e(Post::excerptFrom(null, $p['text'], 80)) ?></p>
    </div>
  </a>
  <?php endforeach; ?>
  <?php if (count($posts) > 1): ?>
  <div class="n-dots" role="tablist"><?php foreach ($posts as $i => $p): ?><button type="button" class="n-dot<?= $i === 0 ? ' is-on' : '' ?>" role="tab" aria-label="<?= I18n::e('第 {n} 张', ['n' => $i + 1]) ?>" data-dot="<?= $i ?>"></button><?php endforeach; ?></div>
  <button type="button" class="n-slide-arrow n-slide-prev" data-prev aria-label="<?= I18n::e('上一张') ?>"><?= View::icon('arrow-left') ?></button>
  <button type="button" class="n-slide-arrow n-slide-next" data-next aria-label="<?= I18n::e('下一张') ?>"><?= View::icon('arrow-right') ?></button>
  <?php endif; ?>
</div>
