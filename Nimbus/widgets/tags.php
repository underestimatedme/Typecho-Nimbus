<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
$w = \Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=count&ignoreZeroCount=1&desc=1&limit=30');
if (!$w->have()) return;
?>
<section class="n-widget n-widget-tags n-reveal">
  <h4 class="n-widget-title"><?= View::icon('tag') ?><?= I18n::e('标签') ?></h4>
  <div class="n-tags">
    <?php while ($w->next()): ?><a class="n-tag" href="<?= View::e($w->permalink) ?>"><?= View::e($w->name) ?><small><?= (int)$w->count ?></small></a><?php endwhile; ?>
  </div>
</section>
