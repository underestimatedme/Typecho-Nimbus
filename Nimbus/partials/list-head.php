<?php
/** 分类 / 标签 / 搜索 / 作者 / 日期页标题栏。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\View;
use Nimbus\I18n;
if ($archive->is('archive', '404')) return;
$kicker = '';
$title = View::t((string)$archive->getArchiveTitle());
if ($archive->is('category')) $kicker = I18n::t('分类');
elseif ($archive->is('tag')) $kicker = I18n::t('标签');
elseif ($archive->is('search')) { $kicker = I18n::t('搜索'); $title = (string)$archive->getKeywords(); }
elseif ($archive->is('author')) $kicker = I18n::t('作者');
elseif ($archive->is('date')) $kicker = I18n::t('归档');
else return;
$total = (int)$archive->getTotal();
?>
<header class="n-list-head n-reveal">
  <?php if ($kicker !== ''): ?><div class="n-kicker"><?= View::e($kicker) ?></div><?php endif; ?>
  <h1><?= View::e($title !== '' ? $title : I18n::t('文章')) ?></h1>
  <p><?= I18n::e('共 {n} 篇文章', ['n' => $total]) ?></p>
</header>
