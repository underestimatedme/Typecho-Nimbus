<?php
/**
 * 归档
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
$posts = \Typecho_Widget::widget('Widget_Contents_Post_Recent', 'pageSize=10000');
$groups = [];
$count = 0;
while ($posts->next()) {
    $y = date('Y', (int)$posts->created); $m = (int)date('n', (int)$posts->created);
    $groups[$y][$m][] = ['title' => $posts->title, 'permalink' => $posts->permalink, 'day' => date('d', (int)$posts->created), 'created' => (int)$posts->created, 'comments' => (int)$posts->commentsNum];
    $count++;
}
View::partial('header', ['archive' => $this]);
?>
<div class="n-container">
  <div class="n-wrap" data-side="<?= View::e((string)Options::get('SidebarPosition', 'right')) ?>">
    <section class="n-main n-archives">
      <header class="n-list-head n-reveal"><div class="n-kicker">归档</div><h1><?= View::e(View::t($this->title)) ?></h1><p>共 <?= $count ?> 篇文章</p></header>
      <?php if ($this->content && trim(strip_tags((string)$this->content)) !== ''): ?><div class="n-prose n-reveal" style="margin-bottom:var(--space-6)"><?= $this->content ?></div><?php endif; ?>
      <div class="n-timeline">
        <?php foreach ($groups as $y => $months): ?>
        <section class="n-year n-reveal">
          <h2 class="n-year-title"><span><?= $y ?></span><small><?= array_sum(array_map('count', $months)) ?> 篇</small></h2>
          <?php foreach ($months as $m => $items): ?>
          <div class="n-month">
            <h3 class="n-month-title"><?= $m ?> 月</h3>
            <ul>
              <?php foreach ($items as $it): ?>
              <li><time><?= $it['day'] ?></time><a href="<?= View::e($it['permalink']) ?>"><?= View::e(View::t($it['title'])) ?></a><?php if ($it['comments'] > 0): ?><span class="n-month-c"><?= View::icon('comment') ?><?= $it['comments'] ?></span><?php endif; ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
        <?php if ($count === 0): ?><div class="n-empty"><?= View::icon('info') ?><p>还没有文章。</p></div><?php endif; ?>
      </div>
      <?php $this->need('comments.php'); ?>
    </section>
    <?php View::partial('sidebar', ['archive' => $this]); ?>
  </div>
</div>
<?php View::partial('footer', ['archive' => $this]); ?>
