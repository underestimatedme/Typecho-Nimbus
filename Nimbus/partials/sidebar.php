<?php
/** 侧栏。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\Widgets;
if ((string)Options::get('SidebarPosition', 'right') === 'none') return;
?>
<aside class="n-side" id="n-side">
  <div class="n-side-sticky">
    <?php foreach (Widgets::order((string)Options::get('Widgets', '')) as $w) Widgets::render($w, $archive); ?>
  </div>
</aside>
