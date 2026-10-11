<?php
/** 杂志风首页封面：极光背景 + 超大标题 + 行动按钮 + 站点数据 + 3D 最新文章卡片堆 + 分类标签滚动条。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;

$o = \Helper::options();
[$bg, $bing] = \Nimbus\Bing::heroImage();
$title = (string)Options::get('HeroTitle', (string)$o->title);
$parts = array_map('trim', explode('|', $title, 2));
$subs = Options::list('HeroSubtitles');
if (empty($subs)) $subs = [(string)$o->description];
$kicker = (string)Options::get('HeroKicker', '');
if ($kicker === '') $kicker = I18n::t('欢迎来到 {site}', ['site' => (string)$o->title]);
$recent = Post::recent($archive, 12);
$stack = array_slice($recent, 0, 3);
$random = $recent ? $recent[array_rand($recent)] : null;
$counts = Post::counts();
$since = (string)Options::get('SiteSince', '');
$days = ($since !== '' && ($ts = strtotime($since)) !== false) ? max(0, (int)floor((time() - $ts) / 86400)) : null;
// 滚动条：分类 + 热门标签
$chips = [];
$cats = \Typecho_Widget::widget('Widget_Metas_Category_List@nimbus-cover', 'ignoreZeroCount=1');
while ($cats->next()) $chips[] = ['name' => (string)$cats->name, 'url' => (string)$cats->permalink, 'n' => (int)$cats->count, 'icon' => 'folder'];
$tags = \Typecho_Widget::widget('Widget_Metas_Tag_Cloud@nimbus-cover', 'sort=count&ignoreZeroCount=1&desc=1&limit=16');
while ($tags->next()) $chips[] = ['name' => (string)$tags->name, 'url' => (string)$tags->permalink, 'n' => (int)$tags->count, 'icon' => 'tag'];
?>
<section class="n-cover<?= $bg !== '' ? ' has-image' : '' ?>" data-hero data-cover>
  <div class="n-cover-bg" aria-hidden="true">
    <?php if ($bg !== ''): ?><img class="n-cover-img" src="<?= View::e($bg) ?>" alt="" fetchpriority="high" decoding="async"><?php endif; ?>
    <i class="n-aurora n-aurora-a"></i><i class="n-aurora n-aurora-b"></i><i class="n-aurora n-aurora-c"></i>
    <i class="n-cover-grid"></i><i class="n-cover-grain"></i>
  </div>
  <div class="n-container n-cover-in">
    <div class="n-cover-copy">
      <div class="n-cover-kicker"><i aria-hidden="true"></i><span><?= View::e($kicker) ?></span></div>
      <h1 class="n-cover-title"><span><?= View::e($parts[0]) ?></span><?php if (isset($parts[1]) && $parts[1] !== ''): ?><em><?= View::e($parts[1]) ?></em><?php endif; ?></h1>
      <p class="n-cover-sub" data-typewriter="<?= View::e(json_encode(array_values($subs), JSON_UNESCAPED_UNICODE)) ?>"><span><?= View::e($subs[0]) ?></span><b class="n-caret" aria-hidden="true"></b></p>
      <div class="n-cover-cta">
        <a class="n-btn n-btn-primary n-cover-btn" href="#n-posts"><?= I18n::e('开始阅读') ?><?= View::icon('arrow-right') ?></a>
        <?php if ($random): ?><a class="n-btn n-cover-btn n-cover-btn-ghost" href="<?= View::e((string)$random['permalink']) ?>"><?= View::icon('shuffle') ?><?= I18n::e('随便看看') ?></a><?php endif; ?>
      </div>
      <ul class="n-cover-stats">
        <li><b data-count="<?= (int)$counts['posts'] ?>"><?= (int)$counts['posts'] ?></b><span><?= I18n::e('篇文章') ?></span></li>
        <li><b data-count="<?= (int)$counts['comments'] ?>"><?= (int)$counts['comments'] ?></b><span><?= I18n::e('条评论') ?></span></li>
        <?php if ($days !== null): ?><li><b data-count="<?= $days ?>"><?= $days ?></b><span><?= I18n::e('天在写') ?></span></li>
        <?php else: ?><li><b data-count="<?= (int)$counts['tags'] ?>"><?= (int)$counts['tags'] ?></b><span><?= I18n::e('个标签') ?></span></li><?php endif; ?>
      </ul>
    </div>
    <?php if ($stack): ?>
    <div class="n-cover-stack" data-cover-stack aria-label="<?= I18n::e('最新文章') ?>">
      <?php foreach (array_reverse($stack, true) as $i => $p):
        $thumb = Post::rowThumb($p);
        $cat = isset($p['categories'][0]) ? (string)$p['categories'][0]['name'] : ''; ?>
      <a class="n-stack-card" style="--i:<?= (int)$i ?>" href="<?= View::e((string)$p['permalink']) ?>">
        <span class="n-stack-img" style="background-image:url('<?= View::e($thumb) ?>')"></span>
        <span class="n-stack-body">
          <span class="n-stack-meta"><?php if ($cat !== ''): ?><i><?= View::e($cat) ?></i><?php endif; ?><time><?= View::date('Y.m.d', (int)$p['created']) ?></time></span>
          <b><?= View::e(View::t((string)$p['title'])) ?></b>
        </span>
        <?php if ($i === 0): ?><span class="n-stack-badge"><?= I18n::e('最新') ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($bing): ?>
  <a class="n-cover-credit"<?= $bing['link'] !== '' ? ' href="' . View::e($bing['link']) . '" target="_blank" rel="noopener nofollow" data-no-router' : '' ?> title="<?= View::e($bing['copyright']) ?>">
    <?= View::icon('image') ?><span><b><?= View::e($bing['title'] !== '' ? $bing['title'] : I18n::t('每日必应')) ?></b><em><?= View::e($bing['copyright']) ?></em></span>
  </a>
  <?php endif; ?>
  <?php if ($chips): ?>
  <div class="n-marquee" aria-hidden="true">
    <div class="n-marquee-track">
      <?php for ($k = 0; $k < 2; $k++): foreach ($chips as $c): ?><a href="<?= View::e($c['url']) ?>" tabindex="-1"><?= View::icon($c['icon']) ?><?= View::e($c['name']) ?><small><?= $c['n'] ?></small></a><?php endforeach; endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
