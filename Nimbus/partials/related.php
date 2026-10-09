<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Post;
use Nimbus\Options;
if (!$post->is('post')) return;
$items = Post::related($post, Options::int('RelatedCount'));
if (empty($items)) return;
$pool = Options::list('ThumbPool');
$builtin = preg_replace('/\?v=.*$/', '', View::asset('img/thumbs'));
?>
<section class="n-related n-reveal">
  <h4 class="n-section-title"><?= View::icon('star') ?>相关文章</h4>
  <div class="n-related-grid">
    <?php foreach ($items as $r): ?>
    <a class="n-related-item" href="<?= View::e($r['permalink']) ?>">
      <i style="background-image:url('<?= View::e(Post::thumbFrom(null, $r['text'], $pool, (int)$r['cid'], $builtin)) ?>')"></i>
      <span><b><?= View::e($r['title']) ?></b><time><?= date('Y-m-d', (int)$r['created']) ?></time></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
