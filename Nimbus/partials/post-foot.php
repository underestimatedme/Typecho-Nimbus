<?php
/** 文末区块：标签、版权、打赏、分享、点赞 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Post;
use Nimbus\Kit;
$isPost = $post->is('post');
$tags = $isPost ? (array)$post->tags : [];
$likes = $isPost ? Kit::likes((int)$post->cid) : null;
?>
<footer class="n-post-foot">
  <?php if (!empty($tags)): ?>
  <div class="n-post-tags">
    <?php foreach ($tags as $t): ?><a class="n-tag" href="<?= View::e($t['permalink']) ?>"><?= View::icon('tag') ?><?= View::e($t['name']) ?></a><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($isPost && Options::bool('CopyrightEnable') && !Post::hidden($post, 'copyright')) View::partial('copyright', ['archive' => $archive, 'post' => $post]); ?>
  <div class="n-post-actions">
    <?php if ($likes !== null): ?><button type="button" class="n-btn n-like" data-like="<?= (int)$post->cid ?>" data-like-type="post" aria-label="点赞"><?= View::icon('heart') ?><span data-like-count><?= (int)$likes ?></span></button><?php endif; ?>
    <?php if ($isPost && Options::bool('RewardEnable') && !Post::hidden($post, 'reward') && (Options::get('RewardAlipay') || Options::get('RewardWechat') || Options::get('RewardQQ'))): ?>
    <button type="button" class="n-btn n-btn-primary" data-reward-open><?= View::icon('coffee') ?>打赏</button>
    <?php endif; ?>
    <?php if (Options::bool('ShareEnable')) View::partial('share', ['archive' => $archive, 'post' => $post]); ?>
  </div>
  <?php if ($isPost && Options::bool('RewardEnable') && !Post::hidden($post, 'reward')) View::partial('reward', ['archive' => $archive, 'post' => $post]); ?>
</footer>
