<?php
/** 分类 / 标签 / 搜索 / 作者 / 日期页标题栏。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
if ($archive->is('archive', '404')) return;
$kicker = '';
$title = (string)$archive->getArchiveTitle(['category' => '%s', 'tag' => '%s', 'search' => '%s', 'author' => '%s', 'date' => '%s'], '', '');
if ($archive->is('category')) $kicker = '分类';
elseif ($archive->is('tag')) $kicker = '标签';
elseif ($archive->is('search')) { $kicker = '搜索'; $title = (string)$archive->keywords; }
elseif ($archive->is('author')) $kicker = '作者';
elseif ($archive->is('date')) $kicker = '归档';
else return;
$total = (int)$archive->getTotal();
?>
<header class="n-list-head n-reveal">
  <?php if ($kicker !== ''): ?><div class="n-kicker"><?= View::e($kicker) ?></div><?php endif; ?>
  <h1><?= View::e($title !== '' ? $title : '文章') ?></h1>
  <p>共 <?= $total ?> 篇文章</p>
</header>
