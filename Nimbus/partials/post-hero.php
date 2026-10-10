<?php
/** 沉浸式通栏文章头图。变量：$post */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Post;
use Nimbus\Kit;
$isPost = $post->is('post');
$words = Post::wordCount((string)$post->text);
$views = Kit::views((int)$post->cid);
$cat = isset($post->categories[0]) ? $post->categories[0] : null;
?>
<header class="n-post-hero" data-hero>
  <div class="n-hero-bg" style="background-image:url('<?= View::e(Post::thumb($post)) ?>')"></div>
  <div class="n-container n-post-hero-in">
    <?php if ($cat && $isPost): ?><a class="n-chip n-post-hero-chip" href="<?= View::e($cat['permalink']) ?>"><?= View::e($cat['name']) ?></a><?php endif; ?>
    <?php if (!Post::hidden($post, 'title')): ?><h1 class="n-post-hero-title" itemprop="headline"><?= View::e(View::t($post->title)) ?></h1><?php endif; ?>
    <?php if ($isPost): ?>
    <div class="n-post-meta n-post-hero-meta">
      <span><?= View::icon('user') ?><?= View::e($post->author->screenName) ?></span>
      <time datetime="<?= View::date('c', (int)$post->created) ?>"><?= View::icon('calendar') ?><?= View::date('Y-m-d', (int)$post->created) ?></time>
      <?php if (Options::bool('ShowWordCount')): ?><span><?= View::icon('edit') ?><?= I18n::e('{n} 字', ['n' => $words]) ?></span><?php endif; ?>
      <?php if (Options::bool('ShowReadingTime')): ?><span><?= View::icon('clock') ?><?= I18n::e('{n} 分钟', ['n' => Post::readingTime($words)]) ?></span><?php endif; ?>
      <?php if ($views !== null): ?><span><?= View::icon('eye') ?><b data-views><?= View::num($views) ?></b></span><?php endif; ?>
      <a href="#comments"><?= View::icon('comment') ?><?= (int)$post->commentsNum ?></a>
    </div>
    <?php endif; ?>
  </div>
  <a class="n-hero-down" href="#n-post" aria-label="<?= I18n::e('开始阅读') ?>"></a>
</header>
