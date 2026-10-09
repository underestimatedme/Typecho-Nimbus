<?php
/** 三栏网格卡片。变量：$post, $index */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Post;
use Nimbus\Kit;
$thumb = Post::thumb($post);
$views = Kit::views((int)$post->cid);
$cat = isset($post->categories[0]) ? $post->categories[0] : null;
?>
<article class="n-card n-card-grid n-reveal" data-cid="<?= (int)$post->cid ?>">
  <a class="n-card-img" href="<?= View::e($post->permalink) ?>" tabindex="-1" aria-hidden="true"><img src="<?= View::e($thumb) ?>" alt="" decoding="async" loading="lazy"></a>
  <div class="n-card-body">
    <div class="n-meta">
      <?php if ($cat): ?><a class="n-chip" href="<?= View::e($cat['permalink']) ?>"><?= View::e($cat['name']) ?></a><?php endif; ?>
      <time datetime="<?= date('c', (int)$post->created) ?>"><?= date('m-d', (int)$post->created) ?></time>
    </div>
    <h3 class="n-card-title"><a href="<?= View::e($post->permalink) ?>"><?= View::e(View::t($post->title)) ?></a></h3>
    <p class="n-card-excerpt"><?= View::e(Post::excerpt($post, 70)) ?></p>
    <div class="n-card-foot">
      <?php if ($views !== null): ?><span><?= View::icon('eye') ?><?= View::num($views) ?></span><?php endif; ?>
      <span><?= View::icon('comment') ?><?= (int)$post->commentsNum ?></span>
      <span class="n-card-more"><?= View::icon('arrow-right') ?></span>
    </div>
  </div>
</article>
