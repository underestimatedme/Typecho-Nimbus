<?php
/**
 * 独立页面
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Post;
use Nimbus\Content;
$nimbusContent = $this->hidden ? ['html' => '', 'hasMath' => false, 'hasMermaid' => false, 'hasCode' => false, 'toc' => []] : Content::render($this);
$showToc = Options::bool('TocEnable') && !Post::hidden($this, 'toc') && count($nimbusContent['toc']) >= 3 && !$this->hidden;
View::partial('header', ['archive' => $this, 'nimbusContent' => $nimbusContent]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-page" data-side="<?= $showToc ? 'toc' : View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <article class="n-post n-page n-main" id="n-post" data-cid="<?= (int)$this->cid ?>">
      <?php View::partial('post-head', ['archive' => $this, 'post' => $this]); ?>
      <?php if ($this->hidden): ?>
        <?php View::partial('password', ['archive' => $this, 'post' => $this]); ?>
      <?php else: ?>
        <div class="n-prose" id="n-prose"><?= $nimbusContent['html'] ?></div>
        <?php View::partial('post-foot', ['archive' => $this, 'post' => $this]); ?>
      <?php endif; ?>
      <?php $this->need('comments.php'); ?>
    </article>
    <?php if ($showToc) View::partial('toc', ['archive' => $this, 'toc' => $nimbusContent['toc']]); else View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
