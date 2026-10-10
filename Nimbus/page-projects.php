<?php
/**
 * 项目
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Kit;

$projects = Kit::projects();
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-full" data-side="none">
    <section class="n-main n-projects-page">
      <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('edit') ?> <?= I18n::e('项目') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $projects ? I18n::e('{n} 个作品', ['n' => count($projects)]) : I18n::e('从零到一，用代码构建的每一份作品') ?></p></header>
      <?php if ($projects === null): ?>
        <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('项目展示需要启用配套插件 NimbusKit。') ?></p></div>
      <?php elseif (empty($projects)): ?>
        <div class="n-empty"><?= View::icon('edit') ?><p><?= I18n::e('还没有项目，去后台「管理 → Nimbus → 项目」添加吧。') ?></p></div>
      <?php else: ?>
      <div class="n-projects">
        <?php foreach ($projects as $p): ?>
        <article class="n-project n-reveal">
          <div class="n-project-cover" style="--h:<?= View::hue($p['name']) ?>"<?= $p['cover'] !== '' ? ' data-has-cover' : '' ?>>
            <?php if ($p['cover'] !== ''): ?><img src="<?= View::e($p['cover']) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span><?= View::e(View::initial($p['name'])) ?></span><?php endif; ?>
          </div>
          <div class="n-project-body">
            <h3><?= View::e($p['name']) ?></h3>
            <?php if ($p['description'] !== ''): ?><p><?= nl2br(View::e($p['description'])) ?></p><?php endif; ?>
            <?php if ($p['tags']): ?><div class="n-project-tags"><?php foreach ($p['tags'] as $t): ?><span><?= View::e($t) ?></span><?php endforeach; ?></div><?php endif; ?>
            <div class="n-project-links">
              <?php if ($p['url'] !== ''): ?><a class="n-btn n-btn-sm n-btn-primary" href="<?= View::e($p['url']) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon('external') ?><?= I18n::e('访问') ?></a><?php endif; ?>
              <?php if ($p['repo'] !== ''): ?><a class="n-btn n-btn-sm" href="<?= View::e($p['repo']) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon(strpos($p['repo'], 'github.com') !== false ? 'github' : 'link') ?><?= I18n::e('源码') ?></a><?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal" id="n-prose" style="margin-top:var(--space-6)"><?= $this->content ?></div><?php endif; ?>
    </section>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
