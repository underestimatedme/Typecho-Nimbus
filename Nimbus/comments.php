<?php
/**
 * 评论区
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
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
$wall = View::$commentLayout === 'wall';
$noun = $wall ? I18n::t('留言') : I18n::t('评论');
?>
<section class="n-comments<?= $wall ? ' n-comments-wall' : '' ?>" id="comments">
  <div class="n-comments-head">
    <h3 class="n-section-title"><?= View::icon('comment') ?><?= View::e($total > 0 ? ($wall ? I18n::t('{n} 条留言', ['n' => $total]) : I18n::t('{n} 条评论', ['n' => $total])) : $noun) ?></h3>
  </div>
  <?php ob_start(); ?>
  <?php if ($comments->have()): ?>
  <?php $comments->listComments(['before' => '<ol class="n-comment-list" id="n-comment-list">', 'after' => '</ol>', 'commentStatus' => I18n::t('您的评论正在等待审核')]); ?>
  <?php $comments->pageNav('‹', '›', 1, '…', ['wrapTag' => 'nav', 'wrapClass' => 'n-pagination n-comment-nav', 'itemTag' => 'span', 'textTag' => 'span', 'currentClass' => 'is-current']); ?>
  <?php elseif ($allow): ?>
  <div class="n-comments-empty"><?= View::icon('smile') ?><p><?= $wall ? I18n::e('还没有留言，来说点什么吧。') : I18n::e('还没有评论，来说点什么吧。') ?></p></div>
  <?php endif; ?>
  <?php $nimbusList = (string)ob_get_clean(); ob_start(); ?>

  <?php if ($allow): ?>
  <div class="n-respond" id="<?php $this->respondId(); ?>" data-respond>
    <div class="n-respond-head">
      <h4 class="n-respond-title" data-respond-title><?= $wall ? I18n::e('发表留言') : I18n::e('发表评论') ?></h4>
      <?php $comments->cancelReply(I18n::e('取消回复')); ?>
    </div>
    <form class="n-comment-form" id="n-comment-form" method="post" action="<?php $this->commentUrl(); ?>" data-comment-form data-ajax="<?= Kit::has('ajaxComment') ? '1' : '0' ?>">
      <input type="hidden" name="_" value="<?= View::e($this->security->getToken($this->request->getRequestUrl())) ?>">
      <?php $gh = $this->user->hasLogin() ? null : Kit::github($this->permalink . '#comments'); $ghUser = $gh['user'] ?? null; $gated = $gh && $gh['only'] && !$ghUser; ?>
      <?php if ($this->user->hasLogin()): ?>
      <p class="n-comment-logged"><?= I18n::t('以 {name} 的身份评论', ['name' => '<b>' . View::e($this->user->screenName) . '</b>']) ?> · <a href="<?php $this->options->logoutUrl(); ?>" data-no-router><?= I18n::e('退出') ?></a></p>
      <?php elseif ($ghUser): ?>
      <div class="n-gh-who">
        <img src="<?= View::e($ghUser['avatar'] . '&s=64') ?>" alt="" width="32" height="32" loading="lazy">
        <span><?= I18n::t('以 {name} 的身份评论', ['name' => '<b>' . View::e($ghUser['name']) . '</b> <em>@' . View::e($ghUser['login']) . '</em>']) ?></span>
        <a href="<?= View::e($gh['logout']) ?>" data-no-router><?= I18n::e('退出') ?></a>
        <input type="hidden" name="author" value="<?= View::e($ghUser['name']) ?>">
        <input type="hidden" name="mail" value="<?= View::e($ghUser['mail']) ?>">
        <input type="hidden" name="url" value="<?= View::e($ghUser['url']) ?>">
      </div>
      <?php elseif ($gh && $gh['only']): ?>
      <div class="n-gh-gate">
        <p><?= $wall ? I18n::e('本站仅允许 GitHub 用户留言，登录后即可发表。') : I18n::e('本站仅允许 GitHub 用户评论，登录后即可发表。') ?></p>
        <a class="n-btn n-btn-primary n-gh-btn" href="<?= View::e($gh['login']) ?>" data-no-router><?= View::icon('github') ?><?= I18n::e('使用 GitHub 登录') ?></a>
      </div>
      <?php else: ?>
      <?php if ($gh): ?><div class="n-gh-bar"><a class="n-btn n-btn-sm n-gh-btn" href="<?= View::e($gh['login']) ?>" data-no-router><?= View::icon('github') ?><?= I18n::e('GitHub 登录') ?></a><span><?= I18n::e('或直接填写昵称与邮箱') ?></span></div><?php endif; ?>
      <div class="n-comment-fields">
        <label><span><?= I18n::e('昵称') ?><i>*</i></span><input type="text" name="author" value="<?php $this->remember('author'); ?>" required placeholder="<?= I18n::e('怎么称呼你') ?>"></label>
        <label><span><?= I18n::e('邮箱') ?><i>*</i></span><input type="email" name="mail" value="<?php $this->remember('mail'); ?>" <?= $this->options->commentsRequireMail ? 'required' : '' ?> placeholder="<?= I18n::e('不会公开') ?>"></label>
        <label><span><?= I18n::e('网址') ?></span><input type="url" name="url" value="<?php $this->remember('url'); ?>" <?= $this->options->commentsRequireURL ? 'required' : '' ?> placeholder="https://"></label>
      </div>
      <?php endif; ?>
      <?php if (!$gated): ?>
      <div class="n-comment-editor">
        <textarea name="text" id="n-comment-text" rows="5" required placeholder="<?= I18n::e('说点什么…（支持 Markdown）') ?>" data-comment-text><?php $this->remember('text'); ?></textarea>
        <div class="n-comment-preview" data-comment-preview hidden></div>
      </div>
      <div class="n-comment-tools">
        <button type="button" class="n-btn n-btn-ghost n-btn-sm" data-emoji-toggle><?= View::icon('smile') ?><?= I18n::e('表情') ?></button>
        <button type="button" class="n-btn n-btn-ghost n-btn-sm" data-preview-toggle><?= View::icon('eye') ?><?= I18n::e('预览') ?></button>
        <span class="n-comment-tip"><?= I18n::e('Ctrl + Enter 快速提交') ?></span>
        <button type="submit" class="n-btn n-btn-primary" data-comment-submit><?= View::icon('arrow-right') ?><?= I18n::e('发表') ?></button>
      </div>
      <div class="n-emoji-panel" data-emoji-panel hidden></div>
      <?php endif; ?>
    </form>
  </div>
  <?php else: ?>
  <div class="n-comments-closed"><?= View::icon('lock') ?><span><?= I18n::e('评论已关闭') ?></span></div>
  <?php endif; ?>
<?php $nimbusForm = (string)ob_get_clean(); echo $wall ? $nimbusForm . $nimbusList : $nimbusList . $nimbusForm; ?>
</section>
