<?php
/**
 * 统计
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Chart;
use Nimbus\Stats;
use Nimbus\Kit;

$s = Stats::collect($this);
$c = $s['counts'];
$kit = $s['kit'];
$short = function (array $labels) { return array_map(function ($l) { return I18n::t('{m}月', ['m' => (int)substr($l, 5)]); }, $labels); };
$cards = [
    [I18n::t('文章'), $c['posts'], 'edit', I18n::t('篇')],
    [I18n::t('评论'), $c['comments'], 'comment', I18n::t('条')],
    [I18n::t('总字数'), $c['words'], 'list', I18n::t('字')],
    [I18n::t('运行'), $c['days'], 'clock', I18n::t('天')],
];
if (is_array($kit)) {
    $cards[] = [I18n::t('总浏览'), (int)($kit['totals']['views'] ?? 0), 'eye', I18n::t('次')];
    $cards[] = [I18n::t('获赞'), (int)($kit['totals']['likes'] ?? 0), 'heart', I18n::t('个')];
} else {
    $cards[] = [I18n::t('分类'), $c['categories'], 'folder', I18n::t('个')];
    $cards[] = [I18n::t('标签'), $c['tags'], 'tag', I18n::t('个')];
}
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-stats-page">
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('star') ?> <?= I18n::e('统计') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $this->fields->subtitle ? View::e((string)$this->fields->subtitle) : I18n::e('数字背后，是每一次写作与相遇') ?></p></header>

    <div class="n-st-cards">
      <?php foreach ($cards as $i => [$label, $num, $icon, $unit]): ?>
      <div class="n-st-card n-reveal" style="--i:<?= $i ?>">
        <span class="n-st-ico"><?= View::icon($icon) ?></span>
        <b><span data-count="<?= (int)$num ?>"><?= number_format((int)$num) ?></span><small><?= View::e($unit) ?></small></b>
        <em><?= View::e($label) ?></em>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="n-st-grid">
      <?php if (is_array($kit) && !empty($kit['daily'])): $days = array_map(function ($d) { return substr((string)$d['day'], 4, 2) . '/' . substr((string)$d['day'], 6, 2); }, $kit['daily']); ?>
      <section class="n-st-panel n-st-wide n-reveal">
        <header><h2><?= I18n::e('近 30 天浏览量') ?></h2><span><?= I18n::e('{n} 次', ['n' => number_format(array_sum(array_column($kit['daily'], 'views')))]) ?></span></header>
        <?= Chart::line('st-views', $days, array_map('intval', array_column($kit['daily'], 'views')), I18n::t(' 次')) ?>
      </section>
      <?php endif; ?>

      <section class="n-st-panel n-st-wide n-reveal">
        <header><h2><?= I18n::e('近 12 个月') ?></h2><div class="n-st-keys"><span class="a"><?= I18n::e('文章') ?></span><span class="b"><?= I18n::e('评论') ?></span></div></header>
        <?= Chart::bars($short($s['posts']['labels']), [
            ['name' => I18n::t('文章'), 'values' => $s['posts']['values'], 'cls' => 'a'],
            ['name' => I18n::t('评论'), 'values' => $s['comments']['values'], 'cls' => 'b'],
        ]) ?>
      </section>

      <section class="n-st-panel n-st-wide n-reveal">
        <header><h2><?= I18n::e('写作热力图') ?></h2><span><?= I18n::e('近一年 {n} 篇', ['n' => (int)$s['heatmap']['total']]) ?></span></header>
        <div class="n-st-heat" data-heat><?= Chart::heatmap($s['heatmap']) ?></div>
        <script type="application/json" id="n-heat-data"><?= json_encode(['posts' => (object)$s['heatPosts'], 'day' => \Typecho\Router::url('archive_day', ['year' => 'YYYY', 'month' => 'MM', 'day' => 'DD'], (string)$this->options->index)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
        <div class="n-st-heat-key"><?= I18n::e('少') ?><i class="l0"></i><i class="l1"></i><i class="l2"></i><i class="l3"></i><i class="l4"></i><?= I18n::e('多') ?></div>
      </section>

      <section class="n-st-panel n-reveal">
        <header><h2><?= I18n::e('分类分布') ?></h2></header>
        <?= Chart::donut($s['categories'], I18n::t('篇文章'), $c['posts']) ?: '<p class="n-st-none">' . I18n::e('暂无数据') . '</p>' ?>
        <?php if ($s['categories']): ?><p class="n-st-hint"><?= I18n::e('文章总数已去重；一篇文章可属于多个分类，占比按分类归属次数计算。') ?></p><?php endif; ?>
      </section>

      <?php if (is_array($kit) && !empty($kit['top'])):
        // 「查看全部」：?hot=all 展开为前 100 名的完整排行（普通链接，可分享）
        $hotAll = (string)$this->request->get('hot') === 'all';
        $hot = $kit['top'];
        $peek = Kit::topViewed($hotAll ? 100 : count($hot) + 1);
        $hasMore = is_array($peek) && count($peek) > count($kit['top']);
        if ($hotAll && is_array($peek)) $hot = Stats::ranked($this, $peek);
        $maxV = max(1, max(array_column($hot, 'views')));
        $self = (string)$this->permalink;
        $allUrl = $self . (strpos($self, '?') === false ? '?' : '&') . 'hot=all#hot';
      ?>
      <section class="n-st-panel n-reveal<?= $hotAll ? ' n-st-wide' : '' ?>" id="hot">
        <header><h2><?= I18n::e('热门文章') ?></h2><span><?= $hotAll ? I18n::e('前 {n} 篇 · 按浏览量', ['n' => count($hot)]) : I18n::e('按浏览量') ?></span>
          <?php if ($hotAll): ?><a class="n-st-more" href="<?= View::e($self) ?>#hot"><?= I18n::e('收起') ?></a>
          <?php elseif ($hasMore): ?><a class="n-st-more" href="<?= View::e($allUrl) ?>"><?= I18n::e('查看全部') ?><?= View::icon('arrow-right') ?></a><?php endif; ?>
        </header>
        <ol class="n-st-top<?= $hotAll ? ' is-all' : '' ?>">
          <?php foreach ($hot as $i => $p): ?>
          <li style="--w:<?= round($p['views'] / $maxV * 100, 1) ?>%;--i:<?= min($i, 20) ?>"><a href="<?= View::e($p['url']) ?>"><span class="n-st-rank"><?= $i + 1 ?></span><span class="n-st-name"><?= View::e($p['title']) ?></span><?php if ($hotAll): ?><span class="n-st-sub"><span><?= View::icon('heart') ?><?= View::num((int)($p['likes'] ?? 0)) ?></span><span><?= View::icon('comment') ?><?= (int)($p['comments'] ?? 0) ?></span></span><?php endif; ?><b><?= View::icon('eye') ?><?= View::num((int)$p['views']) ?></b></a></li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endif; ?>

      <?php if (!empty($s['tags'])): $maxT = max(array_column($s['tags'], 'count')); ?>
      <section class="n-st-panel n-reveal<?= is_array($kit) && !empty($kit['top']) ? ' n-st-wide' : '' ?>">
        <header><h2><?= I18n::e('常用标签') ?></h2></header>
        <div class="n-st-tags">
          <?php foreach ($s['tags'] as $t): ?><a href="<?= View::e($t['url']) ?>" style="--s:<?= round(0.8 + 0.6 * $t['count'] / max(1, $maxT), 2) ?>"><?= View::e($t['name']) ?><small><?= (int)$t['count'] ?></small></a><?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </div>
    <?php if (!is_array($kit)): ?><p class="n-st-hint"><?= View::icon('info') ?> <?= I18n::e('启用配套插件 NimbusKit 后可显示浏览量趋势与热门文章。') ?></p><?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
