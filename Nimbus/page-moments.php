<?php
/**
 * 说说
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Kit;
use Nimbus\Comment;
use Nimbus\Widgets;

$size = 10;
$page = max(1, (int)$this->request->get('mp', 1));
$data = Kit::moments($page, $size);
$owner = Widgets::owner();
$name = (string)Options::get('AuthorName', $owner['screenName'] ?: (string)$this->options->title);
$avatar = (string)Options::get('AuthorAvatar', '');
if ($avatar === '') $avatar = Comment::avatarUrl((string)$owner['mail'], 96, (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/'));
$hasLikes = Kit::has('likes');
$next = ($data && $page * $size < $data['total']) ? (string)$this->permalink . (strpos((string)$this->permalink, '?') === false ? '?' : '&') . 'mp=' . ($page + 1) : '';
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="<?= View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main n-moments-page">
      <header class="n-list-head n-reveal"><div class="n-kicker"><?= I18n::e('说说') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $data ? I18n::e('共 {n} 条', ['n' => (int)$data['total']]) : I18n::e('记录生活的碎片') ?></p></header>
      <?php if ($data === null): ?>
        <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('说说功能需要启用配套插件 NimbusKit。') ?></p></div>
      <?php elseif (empty($data['items'])): ?>
        <div class="n-empty"><?= View::icon('edit') ?><p><?= I18n::e('还没有说说，去后台「管理 → Nimbus」发布第一条吧。') ?></p></div>
      <?php else: ?>
      <div class="n-moments" id="n-list" data-list>
        <?php foreach ($data['items'] as $m): $n = count($m['images']); ?>
        <article class="n-moment n-reveal" id="moment-<?= (int)$m['id'] ?>">
          <i class="n-moment-av" style="background-image:url('<?= View::e($avatar) ?>')"></i>
          <div class="n-moment-card">
            <div class="n-moment-head"><b><?= View::e($name) ?></b><time datetime="<?= date('c', $m['created']) ?>" title="<?= date('Y-m-d H:i', $m['created']) ?>"><?= View::e(View::timeAgo($m['created'])) ?></time></div>
            <?php if ($m['content'] !== ''): ?><div class="n-moment-text"><?= $m['content'] ?></div><?php endif; ?>
            <?php if ($n > 0): ?>
            <div class="n-moment-imgs n-imgs-<?= $n === 1 ? '1' : ($n === 2 || $n === 4 ? '2' : '3') ?>" data-gallery>
              <?php foreach ($m['images'] as $img): ?><a href="<?= View::e($img) ?>" data-lightbox data-no-router><img src="<?= View::e($img) ?>" alt="" loading="lazy" decoding="async"></a><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="n-moment-foot">
              <?php if ($hasLikes): ?><button type="button" class="n-moment-like" data-like="<?= (int)$m['id'] ?>" data-like-type="moment" aria-label="<?= I18n::e('点赞') ?>"><?= View::icon('heart') ?><span data-like-count><?= (int)$m['likes'] ?></span></button><?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <div class="n-load-wrap" data-load-wrap>
        <?php if ($next !== ''): ?><button type="button" class="n-btn n-load-more" data-load-more data-next="<?= View::e($next) ?>"><?= I18n::e('加载更多') ?></button><?php else: ?><span class="n-load-end"><?= I18n::e('没有更多了') ?></span><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal n-moments-intro" id="n-prose"><?= $this->content ?></div><?php endif; ?>
      <?php $this->need('comments.php'); ?>
    </section>
    <?php View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
