<?php
/**
 * 统计
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Chart;
use Nimbus\Stats;

$s = Stats::collect($this);
$c = $s['counts'];
$kit = $s['kit'];
$short = function (array $labels) { return array_map(function ($l) { return (int)substr($l, 5) . '月'; }, $labels); };
$cards = [
    ['文章', $c['posts'], 'edit', '篇'],
    ['评论', $c['comments'], 'comment', '条'],
    ['总字数', $c['words'], 'list', '字'],
    ['运行', $c['days'], 'clock', '天'],
];
if (is_array($kit)) {
    $cards[] = ['总浏览', (int)($kit['totals']['views'] ?? 0), 'eye', '次'];
    $cards[] = ['获赞', (int)($kit['totals']['likes'] ?? 0), 'heart', '个'];
} else {
    $cards[] = ['分类', $c['categories'], 'folder', '个'];
    $cards[] = ['标签', $c['tags'], 'tag', '个'];
}
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-stats-page">
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('star') ?> 统计</div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= $this->fields->subtitle ? View::e((string)$this->fields->subtitle) : '数字背后，是每一次写作与相遇' ?></p></header>

    <div class="n-st-cards">
      <?php foreach ($cards as $i => [$label, $num, $icon, $unit]): ?>
      <div class="n-st-card n-reveal" style="--i:<?= $i ?>">
        <span class="n-st-ico"><?= View::icon($icon) ?></span>
        <b><span data-count="<?= (int)$num ?>"><?= number_format((int)$num) ?></span><small><?= $unit ?></small></b>
        <em><?= $label ?></em>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="n-st-grid">
      <?php if (is_array($kit) && !empty($kit['daily'])): $days = array_map(function ($d) { return substr((string)$d['day'], 4, 2) . '/' . substr((string)$d['day'], 6, 2); }, $kit['daily']); ?>
      <section class="n-st-panel n-st-wide n-reveal">
        <header><h2>近 30 天浏览量</h2><span><?= number_format(array_sum(array_column($kit['daily'], 'views'))) ?> 次</span></header>
        <?= Chart::line('st-views', $days, array_map('intval', array_column($kit['daily'], 'views')), ' 次') ?>
      </section>
      <?php endif; ?>

      <section class="n-st-panel n-st-wide n-reveal">
        <header><h2>近 12 个月</h2><div class="n-st-keys"><span class="a">文章</span><span class="b">评论</span></div></header>
        <?= Chart::bars($short($s['posts']['labels']), [
            ['name' => '文章', 'values' => $s['posts']['values'], 'cls' => 'a'],
            ['name' => '评论', 'values' => $s['comments']['values'], 'cls' => 'b'],
        ]) ?>
      </section>

      <section class="n-st-panel n-st-wide n-reveal">
        <header><h2>写作热力图</h2><span>近一年 <?= (int)$s['heatmap']['total'] ?> 篇</span></header>
        <div class="n-st-heat"><?= Chart::heatmap($s['heatmap']) ?></div>
        <div class="n-st-heat-key">少<i class="l0"></i><i class="l1"></i><i class="l2"></i><i class="l3"></i><i class="l4"></i>多</div>
      </section>

      <section class="n-st-panel n-reveal">
        <header><h2>分类分布</h2></header>
        <?= Chart::donut($s['categories'], '篇文章') ?: '<p class="n-st-none">暂无数据</p>' ?>
      </section>

      <?php if (is_array($kit) && !empty($kit['top'])): $maxV = max(1, max(array_column($kit['top'], 'views'))); ?>
      <section class="n-st-panel n-reveal">
        <header><h2>热门文章</h2><span>按浏览量</span></header>
        <ol class="n-st-top">
          <?php foreach ($kit['top'] as $i => $p): ?>
          <li style="--w:<?= round($p['views'] / $maxV * 100, 1) ?>%;--i:<?= $i ?>"><a href="<?= View::e($p['url']) ?>"><span class="n-st-rank"><?= $i + 1 ?></span><span class="n-st-name"><?= View::e($p['title']) ?></span><b><?= View::num((int)$p['views']) ?></b></a></li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endif; ?>

      <?php if (!empty($s['tags'])): $maxT = max(array_column($s['tags'], 'count')); ?>
      <section class="n-st-panel n-reveal<?= is_array($kit) && !empty($kit['top']) ? ' n-st-wide' : '' ?>">
        <header><h2>常用标签</h2></header>
        <div class="n-st-tags">
          <?php foreach ($s['tags'] as $t): ?><a href="<?= View::e($t['url']) ?>" style="--s:<?= round(0.8 + 0.6 * $t['count'] / max(1, $maxT), 2) ?>"><?= View::e($t['name']) ?><small><?= (int)$t['count'] ?></small></a><?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </div>
    <?php if (!is_array($kit)): ?><p class="n-st-hint"><?= View::icon('info') ?> 启用配套插件 NimbusKit 后可显示浏览量趋势与热门文章。</p><?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
