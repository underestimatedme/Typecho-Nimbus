<?php
/** 杂志风首页的 Bento 网格：精选文章、作者、写作节奏、最新说说、音乐 / 签名。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
use Nimbus\Widgets;
use Nimbus\Comment;
use Nimbus\Kit;
use Nimbus\Stats;

$o = \Helper::options();
// 精选：置顶文章第一篇，否则最新一篇
$feature = null;
$top = Options::list('TopPosts');
if ($top) { $f = Post::byCids($archive, array_slice($top, 0, 1)); $feature = $f[0] ?? null; }
$isPinned = $feature !== null;
if (!$feature) { $r = Post::recent($archive, 1); $feature = $r[0] ?? null; }
if (!$feature) return;
$fText = \Nimbus\Shortcode::strip((string)$feature['text']);
$fWords = Post::wordCount($fText);
$fExcerpt = Post::excerptFrom(null, $fText, 90);
$fCat = isset($feature['categories'][0]) ? (string)$feature['categories'][0]['name'] : '';

$owner = Widgets::owner();
$name = (string)Options::get('AuthorName', $owner['screenName'] ?: (string)$o->title);
$bio = (string)Options::get('AuthorBio', '');
$avatar = (string)Options::get('AuthorAvatar', '');
if ($avatar === '') $avatar = Comment::avatarUrl((string)$owner['mail'], 200, (string)Options::get('GravatarMirror', 'https://cravatar.cn/avatar/'));
$social = [];
foreach (Options::list('SocialLinks') as $line) {
    $p = array_map('trim', explode('|', $line));
    if (count($p) < 2 || $p[1] === '') continue;
    $social[] = ['name' => $p[0], 'url' => $p[1], 'icon' => isset($p[2]) && View::hasIcon($p[2]) ? $p[2] : 'link'];
}
$about = Widgets::pageUrl($archive, 'page-about.php');

// 写作节奏：近 12 个月发文
$db = \Typecho_Db::get();
$now = (int)$o->time ?: time();
$ts = array_map(function ($r) { return (int)$r['created']; }, $db->fetchAll($db->select('created')->from('table.contents')->where('type = ? AND status = ?', 'post', 'publish')->where('created < ?', $now)->where('created > ?', $now - 400 * 86400)));
$monthly = Stats::monthly($ts, $now, 12, (int)$o->timezone);
$max = max(1, max($monthly['values']));
$yearTotal = array_sum($monthly['values']);
$statsUrl = Widgets::pageUrl($archive, 'page-stats.php');
$counts = Post::counts();

// 最新说说（需 NimbusKit），否则公告
$moments = Kit::moments(1, 1);
$moment = $moments && !empty($moments['items']) ? $moments['items'][0] : null;
$momentsUrl = Widgets::pageUrl($archive, 'page-moments.php');
$notice = trim(strip_tags((string)Options::get('NoticeHtml', '')));

$music = \Nimbus\Music::config();
$musicUrl = Widgets::pageUrl($archive, 'page-music.php');
?>
<section class="n-bento" aria-label="<?= I18n::e('精选') ?>">
  <a class="n-bento-tile n-bento-feature n-reveal" href="<?= View::e((string)$feature['permalink']) ?>">
    <span class="n-bento-feature-img" style="background-image:url('<?= View::e(Post::rowThumb($feature)) ?>')"></span>
    <span class="n-bento-feature-body">
      <span class="n-bento-eyebrow"><?= View::icon($isPinned ? 'star' : 'clock') ?><?= $isPinned ? I18n::e('精选') : I18n::e('最新') ?><?php if ($fCat !== ''): ?><i>·</i><?= View::e($fCat) ?><?php endif; ?></span>
      <b class="n-bento-feature-title"><?= View::e(View::t((string)$feature['title'])) ?></b>
      <?php if ($fExcerpt !== ''): ?><span class="n-bento-feature-excerpt"><?= View::e($fExcerpt) ?></span><?php endif; ?>
      <span class="n-bento-feature-meta"><time><?= View::date('Y-m-d', (int)$feature['created']) ?></time><span><?= I18n::e('{n} 分钟', ['n' => Post::readingTime($fWords)]) ?></span><span class="n-bento-go"><?= I18n::e('阅读全文') ?><?= View::icon('arrow-right') ?></span></span>
    </span>
  </a>

  <div class="n-bento-tile n-bento-author n-reveal">
    <i class="n-bento-av" style="background-image:url('<?= View::e($avatar) ?>')"></i>
    <div class="n-bento-author-txt">
      <b><?= View::e($name) ?></b>
      <?php if ($bio !== ''): ?><span><?= View::e($bio) ?></span><?php endif; ?>
    </div>
    <div class="n-bento-social">
      <?php foreach (array_slice($social, 0, 4) as $s): ?><a href="<?= View::e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= View::e($s['name']) ?>" data-no-router><?= View::icon($s['icon']) ?></a><?php endforeach; ?>
      <?php if ($about !== ''): ?><a class="n-bento-more" href="<?= View::e($about) ?>"><?= I18n::e('关于我') ?><?= View::icon('arrow-right') ?></a><?php endif; ?>
    </div>
  </div>

  <<?= $statsUrl !== '' ? 'a href="' . View::e($statsUrl) . '"' : 'div' ?> class="n-bento-tile n-bento-pulse n-reveal">
    <span class="n-bento-label"><?= View::icon('edit') ?><?= I18n::e('写作节奏') ?></span>
    <span class="n-bento-big"><b data-count="<?= (int)$yearTotal ?>"><?= (int)$yearTotal ?></b><small><?= I18n::e('篇 / 近一年') ?></small></span>
    <span class="n-bento-bars" aria-hidden="true"><?php foreach ($monthly['values'] as $k => $v): ?><i style="--h:<?= round($v / $max * 100) ?>%;--i:<?= $k ?>"<?= $v ? '' : ' class="is-zero"' ?>></i><?php endforeach; ?></span>
    <span class="n-bento-foot"><?= I18n::e('共 {n} 篇 · {c} 条评论', ['n' => (int)$counts['posts'], 'c' => (int)$counts['comments']]) ?></span>
  </<?= $statsUrl !== '' ? 'a' : 'div' ?>>

  <<?= $momentsUrl !== '' && $moment ? 'a href="' . View::e($momentsUrl) . '"' : 'div' ?> class="n-bento-tile n-bento-note n-reveal">
    <span class="n-bento-label"><?= View::icon($moment ? 'comment' : 'info') ?><?= $moment ? I18n::e('最新说说') : I18n::e('公告') ?></span>
    <?php if ($moment): ?>
      <span class="n-bento-quote"><?= View::e(mb_substr(trim(html_entity_decode(strip_tags((string)$moment['content']), ENT_QUOTES, 'UTF-8')), 0, 80)) ?></span>
      <span class="n-bento-foot"><?= View::e(View::timeAgo((int)$moment['created'])) ?><?php if (!empty($moment['images'])): ?> · <?= I18n::e('{n} 张照片', ['n' => count($moment['images'])]) ?><?php endif; ?></span>
    <?php else: ?>
      <span class="n-bento-quote"><?= View::e($notice !== '' ? mb_substr($notice, 0, 80) : (string)$o->description) ?></span>
    <?php endif; ?>
  </<?= $momentsUrl !== '' && $moment ? 'a' : 'div' ?>>

  <?php if ($music && !empty($music['list'])): $t0 = $music['list'][0]; ?>
  <div class="n-bento-tile n-bento-music n-reveal" data-bento-music>
    <span class="n-bento-disc" aria-hidden="true"><i style="<?= $t0['cover'] !== '' ? 'background-image:url(\'' . View::e($t0['cover']) . '\')' : '' ?>" data-bento-cover></i></span>
    <span class="n-bento-label"><?= View::icon('music') ?><span data-bento-state data-on="<?= I18n::e('正在播放') ?>"><?= I18n::e('听一首歌') ?></span></span>
    <b class="n-bento-song" data-bento-title><?= View::e($t0['title']) ?></b>
    <span class="n-bento-foot" data-bento-artist><?= View::e($t0['artist']) ?></span>
    <button type="button" class="n-bento-play" data-bento-play aria-label="<?= I18n::e('播放 / 暂停') ?>"><?= View::icon('play', 'n-ico-play') ?><?= View::icon('pause', 'n-ico-pause') ?></button>
    <?php if ($musicUrl !== ''): ?><a class="n-bento-corner" href="<?= View::e($musicUrl) ?>" aria-label="<?= I18n::e('音乐') ?>"><?= View::icon('arrow-right') ?></a><?php endif; ?>
  </div>
  <?php else: ?>
  <div class="n-bento-tile n-bento-motto n-reveal">
    <span class="n-bento-label"><?= View::icon('star') ?><?= I18n::e('此刻') ?></span>
    <span class="n-bento-quote"><?= View::e((string)$o->description ?: I18n::t('记录、思考，与世界温柔相处')) ?></span>
  </div>
  <?php endif; ?>
</section>
<div class="n-section-head" id="n-posts">
  <span class="n-section-eyebrow"><?= I18n::e('最新文章') ?></span>
  <h2><?= I18n::e('慢慢写，慢慢读') ?></h2>
  <?php if (Options::bool('PaginationSwitch') && $archive->getTotal() > (int)\Helper::options()->pageSize) View::partial('list-mode', ['archive' => $archive]); ?>
  <?php $arch = Widgets::pageUrl($archive, 'page-archives.php'); if ($arch !== ''): ?><a class="n-section-more" href="<?= View::e($arch) ?>"><?= I18n::e('全部归档') ?><?= View::icon('arrow-right') ?></a><?php endif; ?>
</div>
