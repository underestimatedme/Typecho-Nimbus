<?php
/** 图文交错卡片。变量：$post, $index */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
use Nimbus\Options;
use Nimbus\Kit;
$thumb = Post::thumb($post);
$words = Post::wordCount((string)$post->text);
$views = Kit::views((int)$post->cid);
$cat = isset($post->categories[0]) ? $post->categories[0] : null;
?>
<article class="n-card n-reveal" data-cid="<?= (int)$post->cid ?>">
  <a class="n-card-img" href="<?= View::e($post->permalink) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= View::e($thumb) ?>" alt="" decoding="async"<?= $index > 1 ? ' loading="lazy"' : '' ?>>
  </a>
  <div class="n-card-body">
    <div class="n-meta">
      <?php if ($cat): ?><a class="n-chip" href="<?= View::e($cat['permalink']) ?>"><?= View::e($cat['name']) ?></a><?php endif; ?>
      <time datetime="<?= date('c', (int)$post->created) ?>"><?= date('Y-m-d', (int)$post->created) ?></time>
    </div>
    <h3 class="n-card-title"><a href="<?= View::e($post->permalink) ?>"><?= View::e(View::t($post->title)) ?></a></h3>
    <p class="n-card-excerpt"><?= View::e(Post::excerpt($post)) ?></p>
    <div class="n-card-foot">
      <?php if ($views !== null): ?><span><?= View::icon('eye') ?><?= View::num($views) ?></span><?php endif; ?>
      <span><?= View::icon('comment') ?><?= (int)$post->commentsNum ?></span>
      <?php if (Options::bool('ShowReadingTime')): ?><span><?= View::icon('clock') ?><?= I18n::e('{n} 分钟', ['n' => Post::readingTime($words)]) ?></span><?php endif; ?>
      <span class="n-card-more"><?= I18n::e('阅读全文') ?><?= View::icon('arrow-right') ?></span>
    </div>
  </div>
</article>
