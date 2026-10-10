<?php
/** 当月日历，有文章的日期可点 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
$o = \Helper::options();
$offset = (int)$o->timezone;
$now = time() + $offset;
$year = (int)gmdate('Y', $now); $month = (int)gmdate('n', $now); $today = (int)gmdate('j', $now);
$first = gmmktime(0, 0, 0, $month, 1, $year) - $offset;
$days = (int)gmdate('t', $first + $offset);
$last = $first + $days * 86400;
$db = \Typecho_Db::get();
$rows = $db->fetchAll($db->select('created')->from('table.contents')->where('type = ? AND status = ?', 'post', 'publish')->where('created >= ? AND created < ?', $first, $last)->where("password IS NULL OR password = ''"));
$has = [];
foreach ($rows as $r) $has[(int)gmdate('j', (int)$r['created'] + $offset)] = true;
$startDow = (int)gmdate('w', $first + $offset); // 0=周日
$startDow = ($startDow + 6) % 7; // 周一开头
$dateUrl = function (int $d) use ($year, $month, $o) {
    return \Typecho_Router::url('archive_day', ['year' => $year, 'month' => sprintf('%02d', $month), 'day' => sprintf('%02d', $d)], $o->index);
};
?>
<section class="n-widget n-widget-calendar n-reveal">
  <h4 class="n-widget-title"><?= View::icon('calendar') ?><?= I18n::e('{y} 年 {m} 月', ['y' => $year, 'm' => $month]) ?></h4>
  <table class="n-calendar">
    <thead><tr><th><?= I18n::e('一') ?></th><th><?= I18n::e('二') ?></th><th><?= I18n::e('三') ?></th><th><?= I18n::e('四') ?></th><th><?= I18n::e('五') ?></th><th><?= I18n::e('六') ?></th><th><?= I18n::e('日') ?></th></tr></thead>
    <tbody>
      <tr>
      <?php for ($i = 0; $i < $startDow; $i++) echo '<td></td>'; $col = $startDow; ?>
      <?php for ($d = 1; $d <= $days; $d++): ?>
        <td class="<?= $d === $today ? 'is-today' : '' ?>"><?php if (isset($has[$d])): ?><a href="<?= View::e($dateUrl($d)) ?>"><?= $d ?></a><?php else: ?><span><?= $d ?></span><?php endif; ?></td>
        <?php $col++; if ($col % 7 === 0 && $d < $days) echo '</tr><tr>'; ?>
      <?php endfor; ?>
      <?php while ($col % 7 !== 0) { echo '<td></td>'; $col++; } ?>
      </tr>
    </tbody>
  </table>
</section>
