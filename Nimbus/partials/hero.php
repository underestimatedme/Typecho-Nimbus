<?php
/** 首页 Hero。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
$o = \Helper::options();
[$bg] = \Nimbus\Bing::heroImage();
if ($bg === '') $bg = preg_replace('/\?v=.*$/', '', View::asset('img/hero-default.svg'));
$title = (string)Options::get('HeroTitle', (string)$o->title);
$parts = array_map('trim', explode('|', $title, 2));
$subs = Options::lines('HeroSubtitles');
if (empty($subs)) $subs = [(string)$o->description];
$kicker = (string)Options::get('HeroKicker', '');
?>
<section class="n-hero" data-hero style="--hero-h: <?= View::e((string)Options::get('HeroHeight', '600px')) ?>">
  <div class="n-hero-bg" style="background-image:url('<?= View::e($bg) ?>')"></div>
  <i class="n-orb n-orb-a" aria-hidden="true"></i><i class="n-orb n-orb-b" aria-hidden="true"></i>
  <div class="n-hero-in">
    <?php if ($kicker !== ''): ?><div class="n-hero-kicker"><i aria-hidden="true"></i><span><?= View::e($kicker) ?></span></div><?php endif; ?>
    <h1 class="n-hero-title"><span><?= View::e($parts[0]) ?></span><?php if (isset($parts[1]) && $parts[1] !== ''): ?><em><?= View::e($parts[1]) ?></em><?php endif; ?></h1>
    <p class="n-hero-sub" data-typewriter="<?= View::e(json_encode(array_values($subs), JSON_UNESCAPED_UNICODE)) ?>"><span><?= View::e($subs[0]) ?></span><b class="n-caret" aria-hidden="true"></b></p>
  </div>
  <a class="n-hero-down" href="#n-main" aria-label="<?= I18n::e('向下滚动') ?>"></a>
  <svg class="n-hero-wave" viewBox="0 0 1440 70" preserveAspectRatio="none" aria-hidden="true"><path d="M0,40 C240,80 480,0 720,30 C960,60 1200,10 1440,40 L1440,70 L0,70 Z"/></svg>
</section>
