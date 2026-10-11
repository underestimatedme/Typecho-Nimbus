<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
use Nimbus\Options;
if (!$post->is('post')) return;
$items = Post::related($post, Options::int('RelatedCount'));
if (empty($items)) return;
?>
<section class="n-related n-reveal">
  <h4 class="n-section-title" data-eyebrow="<?= I18n::e('继续阅读') ?>"><?= View::icon('star') ?><?= I18n::e('相关文章') ?></h4>
  <div class="n-related-grid">
    <?php foreach ($items as $r): ?>
    <a class="n-related-item" href="<?= View::e($r['permalink']) ?>">
      <i style="background-image:url('<?= View::e(Post::rowThumb($r)) ?>')"></i>
      <span><b><?= View::e(View::t($r['title'])) ?></b><time><?= View::date('Y-m-d', (int)$r['created']) ?></time></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
