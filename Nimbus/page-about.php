<?php
/**
 * 关于
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Widgets;
use Nimbus\Comment;
use Nimbus\Content;
$owner = Widgets::owner();
$name = (string)Options::get('AuthorName', $owner['screenName'] ?: (string)$this->options->title);
$avatar = (string)Options::get('AuthorAvatar', '');
if ($avatar === '') $avatar = Comment::avatarUrl((string)$owner['mail'], 240, (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/'));
$social = [];
foreach (Options::lines('SocialLinks') as $line) {
    $p = array_map('trim', explode('|', $line));
    if (count($p) < 2 || $p[1] === '') continue;
    $social[] = ['name' => $p[0], 'url' => $p[1], 'icon' => isset($p[2]) && View::hasIcon($p[2]) ? $p[2] : 'link'];
}
$nimbusContent = Content::render($this);
View::partial('header', ['archive' => $this, 'nimbusContent' => $nimbusContent]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="none">
    <section class="n-main n-about">
      <header class="n-about-head n-reveal">
        <div class="n-about-av"><i style="background-image:url('<?= View::e($avatar) ?>')"></i></div>
        <h1 class="n-about-name"><?= View::e($name) ?></h1>
        <p class="n-about-bio"><?= View::e((string)Options::get('AuthorBio', '')) ?></p>
        <?php if (!empty($social)): ?><div class="n-about-social"><?php foreach ($social as $s): ?><a class="n-btn n-btn-sm" href="<?= View::e($s['url']) ?>" target="_blank" rel="noopener" data-no-router><?= View::icon($s['icon']) ?><?= View::e($s['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
      </header>
      <div class="n-prose n-reveal" id="n-prose"><?= $nimbusContent['html'] ?></div>
      <?php $this->need('comments.php'); ?>
    </section>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
