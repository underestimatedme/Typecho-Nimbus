<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
$url = (string)$post->permalink;
$title = View::t($post->title);
?>
<div class="n-share" data-share data-url="<?= View::e($url) ?>" data-title="<?= View::e($title) ?>">
  <button type="button" class="n-btn n-share-btn" data-share-toggle aria-label="分享"><?= View::icon('share') ?>分享</button>
  <div class="n-share-menu">
    <a href="https://service.weibo.com/share/share.php?url=<?= rawurlencode($url) ?>&title=<?= rawurlencode($title) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon('weibo') ?>微博</a>
    <a href="https://connect.qq.com/widget/shareqq/index.html?url=<?= rawurlencode($url) ?>&title=<?= rawurlencode($title) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon('qq') ?>QQ</a>
    <button type="button" data-share-copy><?= View::icon('copy') ?>复制链接</button>
  </div>
</div>
