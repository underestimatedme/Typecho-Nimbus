<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
$url = (string)$post->permalink;
$title = View::t($post->title);
$poster = [
    'excerpt' => \Nimbus\Post::excerpt($post, 110),
    'author' => (string)$post->author->screenName,
    'date' => date('Y-m-d', (int)$post->created),
    'cover' => \Nimbus\Post::thumb($post),
    'site' => (string)\Helper::options()->title,
];
?>
<div class="n-share" data-share data-url="<?= View::e($url) ?>" data-title="<?= View::e($title) ?>" data-poster="<?= View::e(json_encode($poster, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <button type="button" class="n-btn n-share-btn" data-share-toggle aria-label="<?= I18n::e('分享') ?>"><?= View::icon('share') ?><?= I18n::e('分享') ?></button>
  <div class="n-share-menu">
    <a href="https://service.weibo.com/share/share.php?url=<?= rawurlencode($url) ?>&title=<?= rawurlencode($title) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon('weibo') ?><?= I18n::e('微博') ?></a>
    <a href="https://connect.qq.com/widget/shareqq/index.html?url=<?= rawurlencode($url) ?>&title=<?= rawurlencode($title) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon('qq') ?>QQ</a>
    <button type="button" data-share-poster><?= View::icon('image') ?><?= I18n::e('生成海报') ?></button>
    <button type="button" data-share-copy><?= View::icon('copy') ?><?= I18n::e('复制链接') ?></button>
  </div>
</div>
