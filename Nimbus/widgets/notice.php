<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
$html = (string)Options::get('NoticeHtml', '');
if (trim($html) === '') return;
?>
<section class="n-widget n-widget-notice n-reveal">
  <h4 class="n-widget-title"><?= View::icon('info') ?><?= I18n::e('公告') ?></h4>
  <div class="n-widget-body n-notice"><?= $html ?></div>
</section>
