<?php
/** 分页：normal / more / infinite。变量：$archive */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
$mode = (string)Options::get('Pagination', 'normal');
if ($archive->is('single') || $archive->is('archive', '404') || !$archive->have()) return;
if ($mode === 'normal') {
    $archive->pageNav('‹', '›', 2, '…', ['wrapTag' => 'nav', 'wrapClass' => 'n-pagination', 'itemTag' => 'span', 'textTag' => 'span', 'currentClass' => 'is-current', 'prevClass' => 'n-page-prev', 'nextClass' => 'n-page-next']);
    return;
}
ob_start();
$archive->pageLink('next', 'next');
$html = (string)ob_get_clean();
$next = '';
if (preg_match('/href="([^"]+)"/', $html, $m)) $next = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
?>
<div class="n-load-wrap" data-load-wrap>
  <?php if ($next !== ''): ?>
  <button type="button" class="n-btn n-load-more" data-load-more data-next="<?= View::e($next) ?>"<?= $mode === 'infinite' ? ' data-infinite' : '' ?>>加载更多</button>
  <?php else: ?>
  <span class="n-load-end">没有更多了</span>
  <?php endif; ?>
</div>
