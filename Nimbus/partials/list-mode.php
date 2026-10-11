<?php
/** 文章列表浏览方式切换：分页 / 滚动。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
?>
<div class="n-list-mode" role="group" aria-label="<?= I18n::e('浏览方式') ?>">
  <button type="button" data-list-mode="page" aria-pressed="false"><?= View::icon('list') ?><?= I18n::e('分页') ?></button>
  <button type="button" data-list-mode="scroll" aria-pressed="false"><?= View::icon('arrow-up', 'n-ico-down') ?><?= I18n::e('滚动') ?></button>
</div>
