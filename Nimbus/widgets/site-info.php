<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\Options;
use Nimbus\Post;
$counts = Post::counts();
$since = (string)Options::get('SiteSince', '');
$daysRun = null;
if ($since !== '' && ($ts = strtotime($since)) !== false) $daysRun = max(0, (int)floor((time() - $ts) / 86400));
$db = \Typecho_Db::get();
$row = $db->fetchRow($db->select('modified')->from('table.contents')->where('type = ? AND status = ?', 'post', 'publish')->order('modified', \Typecho_Db::SORT_DESC)->limit(1));
$last = $row ? (int)$row['modified'] : 0;
?>
<section class="n-widget n-widget-site n-reveal">
  <h4 class="n-widget-title"><?= View::icon('info') ?>站点信息</h4>
  <ul class="n-site-info">
    <?php if ($daysRun !== null): ?><li><span>运行天数</span><b><?= $daysRun ?> 天</b></li><?php endif; ?>
    <li><span>文章数</span><b><?= $counts['posts'] ?></b></li>
    <li><span>评论数</span><b><?= $counts['comments'] ?></b></li>
    <li><span>分类数</span><b><?= $counts['categories'] ?></b></li>
    <li><span>标签数</span><b><?= $counts['tags'] ?></b></li>
    <?php if ($last): ?><li><span>最后更新</span><b><?= View::timeAgo($last) ?></b></li><?php endif; ?>
  </ul>
</section>
