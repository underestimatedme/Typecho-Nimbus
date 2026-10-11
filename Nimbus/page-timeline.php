<?php
/**
 * 时间线
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
use Nimbus\Post;
use Nimbus\Options;

$o = \Helper::options();
// 全部文章逐条取分类、字段与摘要开销大，结果按文章指纹缓存（文章或相关设置变化时自动重建）
$cacheKey = \Nimbus\Cache::postsKey([(string)$o->index, (string)$o->siteUrl, json_encode(is_array($o->routingTable) ? ($o->routingTable['post'] ?? '') : ''), (string)Options::get('ThumbPool', ''), (string)Options::get('ExcerptLength', ''), 'v2']);
$items = \Nimbus\Cache::remember('timeline', $cacheKey, function () {
    $posts = \Typecho_Widget::widget('Widget_Contents_Post_Recent@nimbus-timeline', 'pageSize=10000');
    $items = [];
    while ($posts->next()) {
        $cat = isset($posts->categories[0]) ? $posts->categories[0]['name'] : '';
        $items[] = ['type' => 'post', 'cid' => (int)$posts->cid, 'title' => View::t($posts->title), 'url' => $posts->permalink, 'created' => (int)$posts->created, 'cat' => $cat, 'excerpt' => Post::excerpt($posts, 70), 'thumb' => Post::hasOwnImage($posts) ? Post::thumb($posts) : '', 'comments' => (int)$posts->commentsNum];
    }
    return $items;
});
if (!is_array($items)) $items = [];
$nPosts = count($items);
// 教程：在第一章发布的时间点放一条「开始连载」；教程里的文章加上「第 N 章」标记
$chapterOf = [];
$tuts = \Nimbus\Tutorial::all($this);
foreach ($tuts as $t) {
    foreach ($t['chapters'] as $i => $c) $chapterOf[$c['cid']] = ['name' => $t['name'], 'no' => $i + 1];
    $last = end($t['chapters']);
    $items[] = ['type' => 'tutorial', 'title' => $t['name'], 'url' => $t['url'], 'created' => (int)$t['chapters'][0]['created'], 'excerpt' => $t['description'], 'thumb' => $t['cover'],
        'chapters' => count($t['chapters']), 'latest' => $last['title'], 'minutes' => (int)$t['minutes']];
}
foreach ($items as &$it) if (($it['type'] ?? 'post') === 'post' && isset($chapterOf[$it['cid'] ?? 0])) $it['chapter'] = $chapterOf[$it['cid']];
unset($it);
// 项目（需 NimbusKit）：按添加时间
$projects = \Nimbus\Kit::projects() ?: [];
foreach ($projects as $p) {
    if (empty($p['created'])) continue;
    $items[] = ['type' => 'project', 'title' => $p['name'], 'url' => $p['url'] !== '' ? $p['url'] : $p['repo'], 'repo' => $p['repo'], 'demo' => $p['url'], 'created' => (int)$p['created'], 'excerpt' => $p['description'], 'thumb' => $p['cover'], 'tags' => array_slice($p['tags'], 0, 4)];
}
usort($items, function ($a, $b) { return $b['created'] <=> $a['created']; });
$counts = ['post' => $nPosts, 'tutorial' => count($tuts), 'project' => count(array_filter($items, function ($i) { return $i['type'] === 'project'; }))];
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <section class="n-timeline-page">
    <header class="n-list-head n-reveal"><div class="n-kicker"><?= View::icon('clock') ?> <?= I18n::e('时间线') ?></div><h1><?= View::e(View::t($this->title)) ?></h1><p><?= I18n::e('时光河流 · 共 {n} 篇文章', ['n' => $nPosts]) ?><?php if ($counts['tutorial']): ?> · <?= I18n::e('{n} 个教程', ['n' => $counts['tutorial']]) ?><?php endif; ?><?php if ($counts['project']): ?> · <?= I18n::e('{n} 个项目', ['n' => $counts['project']]) ?><?php endif; ?></p></header>
    <?php if ($counts['tutorial'] || $counts['project']): ?>
    <div class="n-tl-filter n-reveal" role="group" aria-label="<?= I18n::e('筛选') ?>">
      <button type="button" class="is-on" data-tl-filter="all"><?= I18n::e('全部') ?><small><?= count($items) ?></small></button>
      <button type="button" data-tl-filter="post"><?= View::icon('edit') ?><?= I18n::e('文章') ?><small><?= $nPosts ?></small></button>
      <?php if ($counts['tutorial']): ?><button type="button" data-tl-filter="tutorial"><?= View::icon('list') ?><?= I18n::e('教程') ?><small><?= $counts['tutorial'] ?></small></button><?php endif; ?>
      <?php if ($counts['project']): ?><button type="button" data-tl-filter="project"><?= View::icon('github') ?><?= I18n::e('项目') ?><small><?= $counts['project'] ?></small></button><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (empty($items)): ?>
      <div class="n-empty"><?= View::icon('info') ?><p><?= I18n::e('还没有文章。') ?></p></div>
    <?php else:
      $years = [];
      foreach ($items as $it) $years[View::date('Y', $it['created'])][] = $it;
    ?>
    <div class="n-tl" data-river>
      <span class="n-tl-spine" aria-hidden="true"><i data-tl-fill></i></span>
      <?php $k = 0; foreach ($years as $y => $list): ?>
      <section class="n-tl-year">
        <h2 class="n-tl-year-head"><span><?= View::e((string)$y) ?></span><small><?= I18n::t('{n} 项', ['n' => '<b>' . count($list) . '</b>']) ?></small></h2>
        <ol class="n-tl-list">
          <?php foreach ($list as $it): $side = $k++ % 2 ? 'is-right' : 'is-left'; ?>
          <li class="n-tl-item <?= $side ?> n-reveal is-<?= $it['type'] ?>" data-type="<?= $it['type'] ?>">
            <span class="n-tl-dot" aria-hidden="true"></span>
            <time class="n-tl-date" datetime="<?= View::date('c', $it['created']) ?>"><b><?= View::date('m.d', $it['created']) ?></b><span><?= $it['type'] === 'tutorial' ? I18n::e('开始连载') : ($it['type'] === 'project' ? I18n::e('新项目') : View::date('H:i', $it['created'])) ?></span></time>
            <a class="n-tl-card" href="<?= View::e($it['url']) ?>"<?= $it['type'] === 'project' ? ' target="_blank" rel="noopener" data-no-router' : '' ?>>
              <?php if ($it['thumb'] !== ''): ?><span class="n-tl-thumb" style="background-image:url('<?= View::e($it['thumb']) ?>')"></span><?php endif; ?>
              <span class="n-tl-body">
                <?php if ($it['type'] === 'tutorial'): ?><span class="n-tl-kind"><?= View::icon('list') ?><?= I18n::e('系列教程') ?> · <?= I18n::e('{n} 章', ['n' => $it['chapters']]) ?></span>
                <?php elseif ($it['type'] === 'project'): ?><span class="n-tl-kind"><?= View::icon('github') ?><?= I18n::e('项目') ?></span>
                <?php elseif (!empty($it['chapter'])): ?><span class="n-tl-kind"><?= View::icon('list') ?><?= View::e($it['chapter']['name']) ?> · <?= I18n::e('第 {n} 章', ['n' => $it['chapter']['no']]) ?></span><?php endif; ?>
                <b><?= View::e($it['title']) ?></b>
                <?php if ($it['excerpt'] !== ''): ?><em><?= View::e($it['excerpt']) ?></em><?php endif; ?>
                <span class="n-tl-meta">
                  <?php if ($it['type'] === 'tutorial'): ?><span><?= I18n::e('最新：{t}', ['t' => $it['latest']]) ?></span>
                  <?php elseif ($it['type'] === 'project'): ?><?php foreach ($it['tags'] as $tg): ?><span class="n-chip"><?= View::e($tg) ?></span><?php endforeach; ?>
                  <?php else: ?><?php if ($it['cat'] !== ''): ?><span class="n-chip"><?= View::e($it['cat']) ?></span><?php endif; ?><span><?= View::icon('comment') ?><?= (int)$it['comments'] ?></span><?php endif; ?>
                  <span class="n-tl-go"><?= View::icon($it['type'] === 'project' ? 'external' : 'arrow-right') ?></span>
                </span>
              </span>
            </a>
          </li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endforeach; ?>
      <div class="n-tl-end" aria-hidden="true"><span><?= View::icon('star') ?></span></div>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
