<?php
/**
 * 文章页
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Post;
use Nimbus\Content;
$nimbusContent = $this->hidden ? ['html' => '', 'hasMath' => false, 'hasMermaid' => false, 'hasCode' => false, 'toc' => []] : Content::render($this);
$showToc = Options::bool('TocEnable') && !Post::hidden($this, 'toc') && count($nimbusContent['toc']) >= 2 && !$this->hidden;
$immersive = Post::immersive($this);
$nimbusTut = $this->hidden ? null : \Nimbus\Tutorial::forPost($this, (int)$this->cid);
$sidePos = (string)Options::get('SidebarPosition', 'right');
// 沉浸布局 + 有目录 + 开启侧栏：超宽屏三栏（目录｜正文｜侧栏）
$tri = $showToc && (string)Options::get('PostLayout', 'immersive') === 'immersive' && $sidePos !== 'none';
View::partial('header', ['archive' => $this, 'nimbusContent' => $nimbusContent]);
if (Post::magazine($this)):
    // 杂志风：封面 + 悬浮正文卡片 + 左侧目录 + 右侧操作栏
    View::partial('post-cover', ['archive' => $this, 'post' => $this, 'toc' => $showToc, 'tut' => $nimbusTut]);
?>
<div class="n-container">
  <div class="n-article<?= $showToc ? ' has-toc' : '' ?>">
    <?php if ($showToc) View::partial('toc', ['archive' => $this, 'toc' => $nimbusContent['toc']]); ?>
    <article class="n-post n-article-paper" id="n-post" data-cid="<?= (int)$this->cid ?>" itemscope itemtype="https://schema.org/BlogPosting">
      <?php View::partial('expire-notice', ['archive' => $this, 'post' => $this]); ?>
      <?php if ($this->hidden): ?>
        <?php View::partial('password', ['archive' => $this, 'post' => $this]); ?>
      <?php else: ?>
        <?php if ($nimbusTut) View::partial('tutorial-nav', ['tut' => $nimbusTut]); ?>
        <div class="n-prose n-prose-magazine" id="n-prose" itemprop="articleBody"><?= $nimbusContent['html'] ?></div>
        <?php View::partial('post-foot', ['archive' => $this, 'post' => $this]); ?>
      <?php endif; ?>
    </article>
    <?php if (!$this->hidden) View::partial('post-rail', ['archive' => $this, 'post' => $this]); ?>
  </div>
  <div class="n-article-after">
    <?php if (!$this->hidden) { View::partial('adjacent', ['archive' => $this, 'post' => $this, 'tut' => $nimbusTut]); View::partial('related', ['archive' => $this, 'post' => $this]); } ?>
    <div class="n-article-comments"><?php $this->need('comments.php'); ?></div>
  </div>
</div>
<?php
    View::partial('footer', ['archive' => $this]);
    return;
endif;
if ($immersive) View::partial('post-hero', ['archive' => $this, 'post' => $this]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-post" data-side="<?= $showToc ? 'toc' : View::e($sidePos) ?>"<?= $tri ? ' data-layout="tri"' : '' ?>>
    <article class="n-post n-main" id="n-post" data-cid="<?= (int)$this->cid ?>" itemscope itemtype="https://schema.org/BlogPosting">
      <?php if (!$immersive) View::partial('post-head', ['archive' => $this, 'post' => $this]); ?>
      <?php View::partial('expire-notice', ['archive' => $this, 'post' => $this]); ?>
      <?php if ($this->hidden): ?>
        <?php View::partial('password', ['archive' => $this, 'post' => $this]); ?>
      <?php else: ?>
        <?php if ($nimbusTut) View::partial('tutorial-nav', ['tut' => $nimbusTut]); ?>
        <div class="n-prose" id="n-prose" itemprop="articleBody"><?= $nimbusContent['html'] ?></div>
        <?php View::partial('post-foot', ['archive' => $this, 'post' => $this]); ?>
        <?php View::partial('adjacent', ['archive' => $this, 'post' => $this, 'tut' => $nimbusTut]); ?>
        <?php View::partial('related', ['archive' => $this, 'post' => $this]); ?>
      <?php endif; ?>
      <?php $this->need('comments.php'); ?>
    </article>
    <?php if ($showToc) View::partial('toc', ['archive' => $this, 'toc' => $nimbusContent['toc']]); ?>
    <?php if (!$showToc || $tri) View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
