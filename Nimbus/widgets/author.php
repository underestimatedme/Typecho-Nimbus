<?php
/** 作者卡 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
use Nimbus\Widgets;
use Nimbus\Comment;
$owner = Widgets::owner();
$name = (string)Options::get('AuthorName', $owner['screenName'] ?: (string)\Helper::options()->title);
$bio = (string)Options::get('AuthorBio', '');
$avatar = (string)Options::get('AuthorAvatar', '');
if ($avatar === '') $avatar = Comment::avatarUrl((string)$owner['mail'], 200, (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/'));
$counts = Post::counts();
$social = [];
foreach (Options::list('SocialLinks') as $line) {
    $p = array_map('trim', explode('|', $line));
    if (count($p) < 2 || $p[1] === '') continue;
    $icon = isset($p[2]) && View::hasIcon($p[2]) ? $p[2] : 'link';
    $social[] = ['name' => $p[0], 'url' => $p[1], 'icon' => $icon];
}
?>
<section class="n-widget n-widget-author n-reveal">
  <div class="n-author-band" aria-hidden="true"></div>
  <div class="n-author-av"><i style="background-image:url('<?= View::e($avatar) ?>')"></i></div>
  <div class="n-author-name"><?= View::e($name) ?></div>
  <?php if ($bio !== ''): ?><div class="n-author-bio"><?= View::e($bio) ?></div><?php endif; ?>
  <div class="n-author-stats">
    <a href="<?= View::e((string)\Helper::options()->siteUrl) ?>"><b data-count="<?= $counts['posts'] ?>"><?= $counts['posts'] ?></b><span><?= I18n::e('文章') ?></span></a>
    <div><b data-count="<?= $counts['comments'] ?>"><?= $counts['comments'] ?></b><span><?= I18n::e('评论') ?></span></div>
    <div><b data-count="<?= $counts['tags'] ?>"><?= $counts['tags'] ?></b><span><?= I18n::e('标签') ?></span></div>
  </div>
  <?php if (!empty($social)): ?>
  <div class="n-author-social">
    <?php foreach ($social as $s): ?><a href="<?= View::e($s['url']) ?>" target="_blank" rel="noopener" title="<?= View::e($s['name']) ?>" aria-label="<?= View::e($s['name']) ?>" data-no-router><?= View::icon($s['icon']) ?></a><?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
