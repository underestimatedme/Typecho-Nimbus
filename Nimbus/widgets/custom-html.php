<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
$html = (string)Options::get('CustomWidgetHtml', '');
if (trim($html) === '') return;
$title = (string)Options::get('CustomWidgetTitle', '');
?>
<section class="n-widget n-widget-custom n-reveal">
  <?php if ($title !== ''): ?><h4 class="n-widget-title"><?= View::icon('edit') ?><?= View::e($title) ?></h4><?php endif; ?>
  <div class="n-widget-body"><?= $html ?></div>
</section>
