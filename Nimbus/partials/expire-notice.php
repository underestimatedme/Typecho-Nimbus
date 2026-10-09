<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Post;
if (!$post->is('post') || $post->hidden) return;
$days = Options::int('ExpireDays');
$own = Post::field($post, 'expireNotice');
if ($own !== null && is_numeric($own)) $days = (int)$own;
if ($days <= 0) return;
$age = (int)floor((time() - (int)$post->modified) / 86400);
if ($age < $days) return;
?>
<div class="n-notice n-notice-warn"><?= View::icon('alert') ?><span>本文最后更新于 <?= $age ?> 天前，其中的信息可能已经过时，请谨慎参考。</span></div>
