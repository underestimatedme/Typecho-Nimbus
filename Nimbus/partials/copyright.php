<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
?>
<div class="n-copyright">
  <div class="n-copyright-row"><span>本文作者</span><b><?= View::e($post->author->screenName) ?></b></div>
  <div class="n-copyright-row"><span>本文链接</span><a href="<?= View::e($post->permalink) ?>" data-copy-link><?= View::e($post->permalink) ?></a></div>
  <div class="n-copyright-row"><span>版权声明</span><em><?= View::e((string)Options::get('CopyrightText', '')) ?></em></div>
  <?= View::icon('edit', 'n-copyright-ico') ?>
</div>
