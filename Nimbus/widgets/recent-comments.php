<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Options;
use Nimbus\Comment;
$w = \Typecho_Widget::widget('Widget_Comments_Recent', 'pageSize=5&ignoreAuthor=true');
if (!$w->have()) return;
$mirror = (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/');
?>
<section class="n-widget n-widget-comments n-reveal">
  <h4 class="n-widget-title"><?= View::icon('comment') ?><?= I18n::e('最新评论') ?></h4>
  <ul class="n-recent-comments">
    <?php while ($w->next()): ?>
    <li>
      <a href="<?= View::e($w->permalink) ?>">
        <i style="background-image:url('<?= View::e(Comment::avatarUrl((string)$w->mail, 60, $mirror)) ?>')"></i>
        <span class="n-rc-body"><b><?= View::e($w->author) ?></b><em><?= View::e(mb_substr(trim(strip_tags((string)$w->text)), 0, 40)) ?></em></span>
      </a>
    </li>
    <?php endwhile; ?>
  </ul>
</section>
