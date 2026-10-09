<?php
/**
 * 友链
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Links;
$links = Links::all($this);
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="<?= View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main n-links-page">
      <header class="n-list-head n-reveal"><div class="n-kicker">友链</div><h1><?= View::e($this->title) ?></h1><p>共 <?= count($links) ?> 位朋友</p></header>
      <?php if (!empty($links)): ?>
      <div class="n-links">
        <?php foreach ($links as $i => $l): ?>
        <a class="n-link-card n-reveal" href="<?= View::e($l['url']) ?>" target="_blank" rel="noopener" data-no-router>
          <span class="n-link-av" data-letter="<?= View::e(Links::initial($l['name'])) ?>"><?php if ($l['avatar'] !== ''): ?><img src="<?= View::e($l['avatar']) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></span>
          <span class="n-link-body"><b><?= View::e($l['name']) ?></b><em><?= View::e($l['desc'] !== '' ? $l['desc'] : preg_replace('#^https?://#', '', $l['url'])) ?></em></span>
          <?= View::icon('external', 'n-link-ext') ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="n-empty n-reveal"><?= View::icon('link') ?><p>还没有友链。在本页编辑界面的「友链数据」字段填入 JSON 即可。</p></div>
      <?php endif; ?>
      <?php if ($this->content && trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal n-links-intro" id="n-prose"><?= $this->content ?></div><?php endif; ?>
      <?php $this->need('comments.php'); ?>
    </section>
    <?php View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
