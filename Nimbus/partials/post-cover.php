<?php
/** 杂志风文章封面：有配图用配图 + 极光叠色，没有配图用极光舞台。变量：$post */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Post;
use Nimbus\Kit;
use Nimbus\Comment;

$o = \Helper::options();
$img = Post::hasOwnImage($post) ? Post::thumb($post) : '';
$words = Post::wordCount((string)$post->text);
$views = Kit::views((int)$post->cid);
$cat = isset($post->categories[0]) ? $post->categories[0] : null;
// 导语只用作者写的摘要（自定义字段 summary）；自动摘要会和正文第一段重复
$lead = $post->hidden ? '' : (string)(Post::field($post, 'summary') ?? '');
$avatar = Comment::avatarUrl((string)$post->author->mail, 64, (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/'));
?>
<header class="n-cover n-post-cover<?= $img !== '' ? ' has-image' : '' ?>" data-hero data-cover>
  <div class="n-cover-bg" aria-hidden="true">
    <?php if ($img !== ''): ?><img class="n-cover-img" src="<?= View::e($img) ?>" alt="" fetchpriority="high" decoding="async"><?php endif; ?>
    <i class="n-aurora n-aurora-a"></i><i class="n-aurora n-aurora-b"></i><i class="n-aurora n-aurora-c"></i>
    <i class="n-cover-grid"></i><i class="n-cover-grain"></i>
  </div>
  <div class="n-container n-post-cover-in">
    <nav class="n-crumbs" aria-label="<?= I18n::e('位置') ?>">
      <a href="<?= View::e((string)$o->siteUrl) ?>"><?= View::icon('home') ?><?= I18n::e('首页') ?></a>
      <?php if ($cat): ?><i>/</i><a href="<?= View::e($cat['permalink']) ?>"><?= View::e($cat['name']) ?></a><?php endif; ?>
    </nav>
    <?php if (!Post::hidden($post, 'title')): ?><h1 class="n-post-cover-title" itemprop="headline"><?= View::e(View::t($post->title)) ?></h1><?php endif; ?>
    <?php if ($lead !== ''): ?><p class="n-post-cover-lead"><?= View::e($lead) ?></p><?php endif; ?>
    <div class="n-post-cover-meta">
      <span class="n-pill n-pill-author"><i style="background-image:url('<?= View::e($avatar) ?>')"></i><?= View::e($post->author->screenName) ?></span>
      <time class="n-pill" datetime="<?= View::date('c', (int)$post->created) ?>"><?= View::icon('calendar') ?><?= View::date('Y-m-d', (int)$post->created) ?></time>
      <?php if (!$post->hidden && Options::bool('ShowReadingTime')): ?><span class="n-pill"><?= View::icon('clock') ?><?= I18n::e('{n} 分钟', ['n' => Post::readingTime($words)]) ?></span><?php endif; ?>
      <?php if (!$post->hidden && Options::bool('ShowWordCount')): ?><span class="n-pill"><?= View::icon('edit') ?><?= I18n::e('{n} 字', ['n' => $words]) ?></span><?php endif; ?>
      <?php if ($views !== null): ?><span class="n-pill"><?= View::icon('eye') ?><b data-views><?= View::num($views) ?></b></span><?php endif; ?>
      <a class="n-pill" href="#comments"><?= View::icon('comment') ?><?= (int)$post->commentsNum ?></a>
    </div>
  </div>
</header>
