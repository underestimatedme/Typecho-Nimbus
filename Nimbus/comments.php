<?php
/**
 * 评论区
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Comment;
use Nimbus\Kit;

if (!function_exists('threadedComments')) {
    function threadedComments($comments, $options)
    {
        Comment::render($comments, $options);
    }
}
$comments = $this->comments();
$total = (int)$this->commentsNum;
$allow = $this->allow('comment');
?>
<section class="n-comments" id="comments">
  <div class="n-comments-head">
    <h3 class="n-section-title"><?= View::icon('comment') ?><?= $total > 0 ? $total . ' 条评论' : '评论' ?></h3>
  </div>
  <?php if ($comments->have()): ?>
  <?php $comments->listComments(['before' => '<ol class="n-comment-list" id="n-comment-list">', 'after' => '</ol>', 'commentStatus' => '您的评论正在等待审核']); ?>
  <?php $comments->pageNav('‹', '›', 1, '…', ['wrapTag' => 'nav', 'wrapClass' => 'n-pagination n-comment-nav', 'itemTag' => 'span', 'textTag' => 'span', 'currentClass' => 'is-current']); ?>
  <?php elseif ($allow): ?>
  <div class="n-comments-empty"><?= View::icon('smile') ?><p>还没有评论，来说点什么吧。</p></div>
  <?php endif; ?>

  <?php if ($allow): ?>
  <div class="n-respond" id="<?php $this->respondId(); ?>" data-respond>
    <div class="n-respond-head">
      <h4 class="n-respond-title" data-respond-title>发表评论</h4>
      <?php $comments->cancelReply('取消回复'); ?>
    </div>
    <form class="n-comment-form" id="n-comment-form" method="post" action="<?php $this->commentUrl(); ?>" data-comment-form data-ajax="<?= Kit::enabled() ? '1' : '0' ?>">
      <input type="hidden" name="_" value="<?= View::e($this->security->getToken($this->request->getRequestUrl())) ?>">
      <?php if ($this->user->hasLogin()): ?>
      <p class="n-comment-logged">以 <b><?= View::e($this->user->screenName) ?></b> 的身份评论 · <a href="<?php $this->options->logoutUrl(); ?>" data-no-router>退出</a></p>
      <?php else: ?>
      <div class="n-comment-fields">
        <label><span>昵称<i>*</i></span><input type="text" name="author" value="<?php $this->remember('author'); ?>" required placeholder="怎么称呼你"></label>
        <label><span>邮箱<i>*</i></span><input type="email" name="mail" value="<?php $this->remember('mail'); ?>" <?= $this->options->commentsRequireMail ? 'required' : '' ?> placeholder="不会公开"></label>
        <label><span>网址</span><input type="url" name="url" value="<?php $this->remember('url'); ?>" <?= $this->options->commentsRequireURL ? 'required' : '' ?> placeholder="https://"></label>
      </div>
      <?php endif; ?>
      <div class="n-comment-editor">
        <textarea name="text" id="n-comment-text" rows="5" required placeholder="说点什么…（支持 Markdown）" data-comment-text><?php $this->remember('text'); ?></textarea>
        <div class="n-comment-preview" data-comment-preview hidden></div>
      </div>
      <div class="n-comment-tools">
        <button type="button" class="n-btn n-btn-ghost n-btn-sm" data-emoji-toggle><?= View::icon('smile') ?>表情</button>
        <button type="button" class="n-btn n-btn-ghost n-btn-sm" data-preview-toggle><?= View::icon('eye') ?>预览</button>
        <span class="n-comment-tip">Ctrl + Enter 快速提交</span>
        <button type="submit" class="n-btn n-btn-primary" data-comment-submit><?= View::icon('arrow-right') ?>发表</button>
      </div>
      <div class="n-emoji-panel" data-emoji-panel hidden></div>
    </form>
  </div>
  <?php else: ?>
  <div class="n-comments-closed"><?= View::icon('lock') ?><span>评论已关闭</span></div>
  <?php endif; ?>
</section>
