<?php
/**
 * 收藏夹
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Kit;

$groups = Kit::bookmarks();
$total = 0;
if ($groups) foreach ($groups as $g) $total += count($g['items']);
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-full" data-side="none">
    <section class="n-main n-bookmarks-page" data-bookmarks>
      <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('star') ?> <?= I18n::e('收藏夹') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $groups ? I18n::e('{n} 个站点 · {g} 个分组', ['n' => $total, 'g' => count($groups)]) : I18n::e('收集常用的好用站点和工具') ?></p></header>
      <?php if ($groups === null): ?>
        <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('收藏夹功能需要启用配套插件 NimbusKit。') ?></p></div>
      <?php elseif (empty($groups)): ?>
        <div class="n-empty"><?= View::icon('star') ?><p><?= I18n::e('还没有收藏，去后台「管理 → Nimbus → 收藏夹」添加吧。') ?></p></div>
      <?php else: ?>
      <div class="n-bm-bar n-reveal">
        <nav class="n-bm-tabs" role="tablist">
          <button type="button" class="is-on" data-bm-tab=""><?= I18n::e('全部') ?><small><?= $total ?></small></button>
          <?php foreach ($groups as $g): ?><button type="button" data-bm-tab="<?= View::e($g['name']) ?>"><?= View::e($g['name']) ?><small><?= count($g['items']) ?></small></button><?php endforeach; ?>
        </nav>
        <label class="n-bm-search"><?= View::icon('search') ?><input type="search" placeholder="<?= I18n::e('过滤站点…') ?>" data-bm-filter></label>
      </div>
      <?php foreach ($groups as $g): ?>
      <section class="n-bm-group" data-bm-group="<?= View::e($g['name']) ?>">
        <h2 class="n-bm-title"><?= View::e($g['name']) ?><small><?= count($g['items']) ?></small></h2>
        <div class="n-bm-grid">
          <?php foreach ($g['items'] as $b): $host = (string)parse_url($b['url'], PHP_URL_HOST); ?>
          <a class="n-bm n-reveal" href="<?= View::e($b['url']) ?>" target="_blank" rel="noopener" data-no-router data-bm-text="<?= View::e(mb_strtolower($b['name'] . ' ' . $b['desc'] . ' ' . $host)) ?>">
            <span class="n-bm-icon" style="--h:<?= View::hue($b['name']) ?>" data-letter="<?= View::e(View::initial($b['name'])) ?>"><?php if ($b['icon'] !== ''): ?><img src="<?= View::e($b['icon']) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></span>
            <span class="n-bm-body"><b><?= View::e($b['name']) ?></b><em><?= View::e($b['desc'] !== '' ? $b['desc'] : $host) ?></em></span>
            <?= View::icon('external', 'n-bm-ext') ?>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>
      <div class="n-empty n-bm-none" hidden><?= View::icon('search') ?><p><?= I18n::e('没有匹配的站点。') ?></p></div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
