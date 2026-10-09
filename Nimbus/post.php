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
View::partial('header', ['archive' => $this, 'nimbusContent' => $nimbusContent]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-post" data-side="<?= $showToc ? 'toc' : View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <article class="n-post n-main" id="n-post" data-cid="<?= (int)$this->cid ?>" itemscope itemtype="https://schema.org/BlogPosting">
      <?php View::partial('post-head', ['archive' => $this, 'post' => $this]); ?>
      <?php View::partial('expire-notice', ['archive' => $this, 'post' => $this]); ?>
      <?php if ($this->hidden): ?>
        <?php View::partial('password', ['archive' => $this, 'post' => $this]); ?>
      <?php else: ?>
        <div class="n-prose" id="n-prose" itemprop="articleBody"><?= $nimbusContent['html'] ?></div>
        <?php View::partial('post-foot', ['archive' => $this, 'post' => $this]); ?>
        <?php View::partial('adjacent', ['archive' => $this, 'post' => $this]); ?>
        <?php View::partial('related', ['archive' => $this, 'post' => $this]); ?>
      <?php endif; ?>
      <?php $this->need('comments.php'); ?>
    </article>
    <?php if ($showToc): ?>
      <?php View::partial('toc', ['archive' => $this, 'toc' => $nimbusContent['toc']]); ?>
    <?php else: ?>
      <?php View::partial('sidebar', ['archive' => $this]); ?>
    <?php endif; ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
