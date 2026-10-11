<?php
/**
 * 闪念说说（兼容 Cuteen）
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Comment;
use Nimbus\Soliloquize;

$size = max(1, min(100, (int)$this->options->commentsPageSize));
$page = max(1, (int)$this->request->get('mp', 1));
$data = Soliloquize::data($this, $page, $size);
$next = $page * $size < $data['total'] ? (string)$this->permalink . (strpos((string)$this->permalink, '?') === false ? '?' : '&') . 'mp=' . ($page + 1) : '';
$canPublish = !$this->hidden && $this->allow('comment') && $this->user->hasLogin()
    && ((int)$this->user->uid === (int)$this->authorId || $this->user->pass('administrator', true));
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="<?= View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main n-moments-page">
      <header class="n-list-head n-reveal"><div class="n-kicker"><?= I18n::e('说说') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $this->hidden ? I18n::e('记录生活的碎片') : I18n::e('共 {n} 条', ['n' => $data['total']]) ?></p></header>
      <?php if ($this->hidden): ?>
        <?php View::partial('password', ['archive' => $this, 'post' => $this]); ?>
      <?php else: ?>
      <?php if (empty($data['items'])): ?><div class="n-empty"><?= View::icon('edit') ?><p><?= I18n::e('记录生活的碎片') ?></p></div><?php endif; ?>
      <div class="n-moments" id="n-list" data-list>
        <?php foreach ($data['items'] as $m):
            $avatar = (string)Options::get('AuthorAvatar', '');
            if ($avatar === '') $avatar = Comment::avatarUrl($m['mail'], 96, (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/'));
        ?>
        <article class="n-moment n-reveal" id="comment-<?= $m['id'] ?>">
          <i class="n-moment-av" style="background-image:url('<?= View::e($avatar) ?>')"></i>
          <div class="n-moment-card">
            <div class="n-moment-head"><b><?= View::e($m['author']) ?></b><time datetime="<?= View::date('c', $m['created']) ?>"><?= View::date('Y-m-d H:i', $m['created']) ?></time></div>
            <div class="n-moment-text n-prose"><?= $m['content'] ?></div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <div class="n-load-wrap" data-load-wrap>
        <?php if ($next !== ''): ?><button type="button" class="n-btn n-load-more" data-load-more data-next="<?= View::e($next) ?>"><?= I18n::e('加载更多') ?></button><?php else: ?><span class="n-load-end"><?= I18n::e('没有更多了') ?></span><?php endif; ?>
      </div>
      <?php if (trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal n-moments-intro" id="n-prose"><?= $this->content ?></div><?php endif; ?>
      <?php if ($canPublish): ?>
      <section class="n-comments" id="comments">
        <div class="n-respond" id="<?php $this->respondId(); ?>" data-respond>
          <h3 class="n-section-title"><?= I18n::e('发布闪念') ?></h3>
          <form class="n-comment-form" method="post" action="<?php $this->commentUrl(); ?>" data-comment-form data-ajax="0">
            <input type="hidden" name="_" value="<?= View::e($this->security->getToken($this->request->getRequestUrl())) ?>">
            <div class="n-comment-editor"><textarea name="text" rows="5" required data-comment-text placeholder="<?= I18n::e('记录生活的碎片') ?>"><?php $this->remember('text'); ?></textarea></div>
            <div class="n-comment-tools"><span class="n-comment-tip"><?= I18n::e('Ctrl + Enter 快速提交') ?></span><button type="submit" class="n-btn n-btn-primary" data-comment-submit><?= I18n::e('发布闪念') ?></button></div>
          </form>
        </div>
      </section>
      <?php endif; ?>
      <?php endif; ?>
    </section>
    <?php View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
