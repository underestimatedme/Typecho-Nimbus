<?php
/** 纯文字列表项。变量：$post, $index */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Post;
$cat = isset($post->categories[0]) ? $post->categories[0] : null;
?>
<article class="n-plain n-reveal" data-cid="<?= (int)$post->cid ?>">
  <time datetime="<?= date('c', (int)$post->created) ?>"><?= date('Y-m-d', (int)$post->created) ?></time>
  <h3 class="n-plain-title"><a href="<?= View::e($post->permalink) ?>"><?= View::e($post->title) ?></a></h3>
  <p class="n-plain-excerpt"><?= View::e(Post::excerpt($post, 90)) ?></p>
  <div class="n-plain-meta">
    <?php if ($cat): ?><a class="n-chip" href="<?= View::e($cat['permalink']) ?>"><?= View::e($cat['name']) ?></a><?php endif; ?>
    <span><?= View::icon('comment') ?><?= (int)$post->commentsNum ?></span>
  </div>
</article>
