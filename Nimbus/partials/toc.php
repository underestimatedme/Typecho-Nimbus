<?php
/** 目录。变量：$toc（数组 id/text/level） */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
$min = 6;
foreach ($toc as $t) $min = min($min, $t['level']);
?>
<aside class="n-toc" id="n-toc" data-toc>
  <div class="n-toc-in">
    <h4 class="n-widget-title"><?= View::icon('list') ?>目录</h4>
    <nav class="n-toc-nav" id="n-toc-nav">
      <ul>
        <?php foreach ($toc as $t): ?><li class="n-toc-l<?= $t['level'] - $min + 1 ?>"><a href="#<?= View::e($t['id']) ?>" data-toc-link="<?= View::e($t['id']) ?>"><?= View::e($t['text']) ?></a></li><?php endforeach; ?>
      </ul>
    </nav>
    <div class="n-toc-progress"><i></i></div>
  </div>
</aside>
<button type="button" class="n-toc-fab" data-toc-fab aria-label="目录"><?= View::icon('list') ?></button>
