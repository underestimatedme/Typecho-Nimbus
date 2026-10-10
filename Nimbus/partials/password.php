<?php
/** 密码保护表单 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
$action = '';
if (preg_match('/action="([^"]+)"/', (string)$post->text, $m)) $action = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
$hint = Post::field($post, 'passwordHint');
?>
<div class="n-password">
  <?= View::icon('lock', 'n-password-ico') ?>
  <h3><?= I18n::e('这篇文章受密码保护') ?></h3>
  <p><?= $hint !== null ? View::e($hint) : I18n::e('请输入密码查看内容。') ?></p>
  <form class="n-password-form" action="<?= View::e($action) ?>" method="post" data-no-router>
    <input type="password" name="protectPassword" placeholder="<?= I18n::e('密码') ?>" required autocomplete="off">
    <input type="hidden" name="protectCID" value="<?= (int)$post->cid ?>">
    <button type="submit" class="n-btn n-btn-primary"><?= I18n::e('解锁') ?></button>
  </form>
</div>
