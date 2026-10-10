<?php
/** 文章头部。变量：$post */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Post;
use Nimbus\Kit;
$isPost = $post->is('post');
$hideTitle = Post::hidden($post, 'title');
$banner = $isPost && Post::hasOwnImage($post) ? Post::thumb($post) : '';
$words = $post->hidden ? 0 : Post::wordCount((string)$post->text);
$views = Kit::views((int)$post->cid);
$cat = isset($post->categories[0]) ? $post->categories[0] : null;
?>
<header class="n-post-head<?= $banner !== '' ? ' has-banner' : '' ?>">
  <?php if ($banner !== ''): ?><div class="n-post-banner"><i style="background-image:url('<?= View::e($banner) ?>')"></i></div><?php endif; ?>
  <div class="n-post-head-in">
    <?php if ($cat && $isPost): ?><a class="n-chip" href="<?= View::e($cat['permalink']) ?>"><?= View::e($cat['name']) ?></a><?php endif; ?>
    <?php if (!$hideTitle): ?><h1 class="n-post-title" itemprop="headline"><?= View::e(View::t($post->title)) ?></h1><?php endif; ?>
    <?php if ($isPost): ?>
    <div class="n-post-meta">
      <span><?= View::icon('user') ?><?= View::e($post->author->screenName) ?></span>
      <time datetime="<?= date('c', (int)$post->created) ?>" itemprop="datePublished"><?= View::icon('calendar') ?><?= date('Y-m-d', (int)$post->created) ?></time>
      <?php if (!$post->hidden && Options::bool('ShowWordCount')): ?><span><?= View::icon('edit') ?><?= I18n::e('{n} 字', ['n' => $words]) ?></span><?php endif; ?>
      <?php if (!$post->hidden && Options::bool('ShowReadingTime')): ?><span><?= View::icon('clock') ?><?= I18n::e('{n} 分钟', ['n' => Post::readingTime($words)]) ?></span><?php endif; ?>
      <?php if ($views !== null): ?><span><?= View::icon('eye') ?><b data-views><?= View::num($views) ?></b></span><?php endif; ?>
      <a href="#comments"><?= View::icon('comment') ?><?= (int)$post->commentsNum ?></a>
    </div>
    <?php endif; ?>
  </div>
</header>
