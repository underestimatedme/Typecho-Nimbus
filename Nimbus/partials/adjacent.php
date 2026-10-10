<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
if (!$post->is('post')) return;
$adj = Post::adjacent($post);
if (!$adj['prev'] && !$adj['next']) return;
?>
<nav class="n-adjacent n-reveal" aria-label="<?= I18n::e('上一篇 / 下一篇') ?>">
  <?php foreach (['prev' => I18n::t('上一篇'), 'next' => I18n::t('下一篇')] as $k => $label): $p = $adj[$k]; ?>
  <?php if ($p): ?>
  <a class="n-adj n-adj-<?= $k ?>" href="<?= View::e($p['permalink']) ?>">
    <i style="background-image:url('<?= View::e($p['thumb']) ?>')"></i>
    <span class="n-adj-in"><small><?= $k === 'prev' ? View::icon('arrow-left') : '' ?><?= $label ?><?= $k === 'next' ? View::icon('arrow-right') : '' ?></small><b><?= View::e(View::t($p['title'])) ?></b></span>
  </a>
  <?php else: ?><div class="n-adj n-adj-empty"></div><?php endif; ?>
  <?php endforeach; ?>
</nav>
