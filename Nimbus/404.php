<?php
/**
 * 404
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Post;
$recent = Post::recent($this, 20);
shuffle($recent);
$recent = array_slice($recent, 0, 5);
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-error" id="n-error">
    <div class="n-error-art"><img src="<?= View::e(preg_replace('/\?v=.*$/', '', View::asset('img/404.svg'))) ?>" alt="404"></div>
    <h1 class="n-error-title">页面不见了</h1>
    <p class="n-error-desc">你访问的地址可能已被移动或删除。试试搜索，或者看看这些文章。</p>
    <form class="n-error-search" action="<?= View::e((string)$this->options->siteUrl) ?>" method="get">
      <?= View::icon('search') ?><input type="search" name="s" placeholder="搜索文章…" required>
      <button type="submit" class="n-btn n-btn-primary">搜索</button>
    </form>
    <div class="n-error-actions"><a class="n-btn" href="<?= View::e((string)$this->options->siteUrl) ?>"><?= View::icon('home') ?>返回首页</a></div>
    <?php if (!empty($recent)): ?>
    <div class="n-error-posts">
      <h4 class="n-section-title"><?= View::icon('star') ?>随便看看</h4>
      <ul>
        <?php foreach ($recent as $r): ?><li><a href="<?= View::e($r['permalink']) ?>"><?= View::icon('arrow-right') ?><?= View::e(View::t($r['title'])) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
