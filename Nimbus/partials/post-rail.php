<?php
/** 杂志风文章页右侧悬浮操作栏：点赞、评论、海报、复制链接、回到顶部。变量：$post */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Kit;
$likes = Kit::likes((int)$post->cid);
?>
<aside class="n-rail" aria-label="<?= I18n::e('文章操作') ?>">
  <div class="n-rail-in">
    <?php if ($likes !== null): ?><button type="button" class="n-rail-btn n-like" data-like="<?= (int)$post->cid ?>" data-like-type="post" aria-label="<?= I18n::e('点赞') ?>"><?= View::icon('heart') ?><span data-like-count><?= (int)$likes ?></span></button><?php endif; ?>
    <a class="n-rail-btn" href="#comments" aria-label="<?= I18n::e('评论') ?>"><?= View::icon('comment') ?><span><?= (int)$post->commentsNum ?></span></a>
    <?php if (Options::bool('ShareEnable')): ?><button type="button" class="n-rail-btn" data-rail-poster aria-label="<?= I18n::e('生成海报') ?>" title="<?= I18n::e('生成海报') ?>"><?= View::icon('image') ?></button><?php endif; ?>
    <a class="n-rail-btn" href="<?= View::e((string)$post->permalink) ?>" data-copy-link aria-label="<?= I18n::e('复制链接') ?>" title="<?= I18n::e('复制链接') ?>"><?= View::icon('link') ?></a>
    <span class="n-rail-line" aria-hidden="true"><i data-rail-progress></i></span>
    <button type="button" class="n-rail-btn" data-rail-top aria-label="<?= I18n::e('返回顶部') ?>"><?= View::icon('arrow-up') ?></button>
  </div>
</aside>
