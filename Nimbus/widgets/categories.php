<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
$w = \Typecho_Widget::widget('Widget_Metas_Category_List', 'ignoreZeroCount=1');
if (!$w->have()) return;
?>
<section class="n-widget n-widget-cats n-reveal">
  <h4 class="n-widget-title"><?= View::icon('folder') ?><?= I18n::e('分类') ?></h4>
  <ul class="n-cats">
    <?php while ($w->next()): ?>
    <li><a href="<?= View::e($w->permalink) ?>"><span><?= View::e($w->name) ?></span><i><?= (int)$w->count ?></i></a></li>
    <?php endwhile; ?>
  </ul>
</section>
