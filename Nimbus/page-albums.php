<?php
/**
 * 相册
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Kit;

$albums = Kit::albums();
$id = (int)$this->request->get('album');
$current = null;
if ($albums && $id) foreach ($albums as $a) if ((int)$a['id'] === $id) $current = $a;
$base = (string)$this->permalink;
$link = function (int $aid) use ($base) { return $base . (strpos($base, '?') === false ? '?' : '&') . 'album=' . $aid; };
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap n-wrap-full" data-side="none">
    <section class="n-main n-albums-page">
      <?php if ($current): ?>
        <header class="n-list-head n-reveal">
          <a class="n-back" href="<?= View::e($base) ?>"><?= View::icon('arrow-left') ?><?= I18n::e('全部相册') ?></a>
          <h1><?= View::e($current['title']) ?></h1>
          <p><?= I18n::e('{n} 张照片', ['n' => count($current['photos'])]) ?><?= $current['description'] !== '' ? ' · ' . View::e($current['description']) : '' ?></p>
        </header>
        <?php if (empty($current['photos'])): ?><div class="n-empty"><?= View::icon('image') ?><p><?= I18n::e('这个相册还没有照片。') ?></p></div><?php endif; ?>
        <div class="n-photos" data-gallery>
          <?php foreach ($current['photos'] as $p): ?>
          <figure class="n-photo n-reveal">
            <a href="<?= View::e($p['url']) ?>" data-lightbox data-no-router><img src="<?= View::e($p['url']) ?>" alt="<?= View::e($p['caption']) ?>" loading="lazy" decoding="async"></a>
            <?php if ($p['caption'] !== ''): ?><figcaption><?= View::e($p['caption']) ?></figcaption><?php endif; ?>
          </figure>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <header class="n-list-head n-reveal"><div class="n-kicker"><?= I18n::e('相册') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $albums ? I18n::e('{n} 个相册', ['n' => count($albums)]) : I18n::e('用照片记录时光') ?></p></header>
        <?php if ($albums === null): ?>
          <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('相册功能需要启用配套插件 NimbusKit。') ?></p></div>
        <?php elseif (empty($albums)): ?>
          <div class="n-empty"><?= View::icon('image') ?><p><?= I18n::e('还没有相册，去后台「管理 → Nimbus」新建一个吧。') ?></p></div>
        <?php else: ?>
        <div class="n-albums">
          <?php foreach ($albums as $a): ?>
          <a class="n-album n-reveal" href="<?= View::e($link((int)$a['id'])) ?>">
            <i class="n-album-cover" style="background-image:url('<?= View::e($a['cover']) ?>')"></i>
            <span class="n-album-stack" aria-hidden="true"></span>
            <span class="n-album-in"><b><?= View::e($a['title']) ?></b><small><?= I18n::e('{n} 张', ['n' => count($a['photos'])]) ?><?= $a['description'] !== '' ? ' · ' . View::e(mb_substr($a['description'], 0, 40)) : '' ?></small></span>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!$current && trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal" id="n-prose" style="margin-top:var(--space-6)"><?= $this->content ?></div><?php endif; ?>
    </section>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
