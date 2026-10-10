<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
$posts = Post::hot($archive, max(1, Options::int('HotCount')));
if (empty($posts)) return;
?>
<section class="n-widget n-widget-hot n-reveal">
  <h4 class="n-widget-title"><?= View::icon('star') ?><?= I18n::e('热门文章') ?></h4>
  <ul class="n-hot">
    <?php foreach ($posts as $i => $p): ?>
    <li><a href="<?= View::e($p['permalink']) ?>"><i><?= $i + 1 ?></i><span><?= View::e(View::t($p['title'])) ?></span></a></li>
    <?php endforeach; ?>
  </ul>
</section>
