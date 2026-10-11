<?php
/**
 * 分页：同时输出页码与「加载更多」，按浏览方式显示其一。
 * 浏览方式默认取后台「分页方式」（传统分页 → 分页；加载更多 / 无限滚动 → 滚动），访客可用切换按钮改，选择记在浏览器里。
 * 变量：$archive
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\I18n;
$mode = (string)Options::get('Pagination', 'normal');
if ($archive->is('single') || $archive->is('archive', '404') || !$archive->have()) return;
ob_start();
$archive->pageLink('next', 'next');
$html = (string)ob_get_clean();
$next = '';
if (preg_match('/href="([^"]+)"/', $html, $m)) $next = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
ob_start();
$archive->pageNav('‹', '›', 2, '…', ['wrapTag' => 'nav', 'wrapClass' => 'n-pagination', 'itemTag' => 'span', 'textTag' => 'span', 'currentClass' => 'is-current', 'prevClass' => 'n-page-prev', 'nextClass' => 'n-page-next']);
$nav = (string)ob_get_clean();
$default = $mode === 'normal' ? 'page' : 'scroll';
// 只有一页时不需要切换
$multi = $nav !== '' || $next !== '';
?>
<div class="n-pager" data-pager data-mode="<?= $default ?>" data-default="<?= $default ?>" data-auto="<?= $mode === 'more' ? '0' : '1' ?>">
  <?php if ($multi && Options::bool('PaginationSwitch')) View::partial('list-mode', ['archive' => $archive]); ?>
  <div class="n-pager-pages"><?= $nav ?></div>
  <div class="n-load-wrap" data-load-wrap>
    <?php if ($next !== ''): ?>
    <button type="button" class="n-btn n-load-more" data-load-more data-next="<?= View::e($next) ?>"<?= $default === 'scroll' && $mode !== 'more' ? ' data-infinite' : '' ?>><?= I18n::e('加载更多') ?></button>
    <?php elseif ($multi || $archive->getTotal() > 0): ?>
    <span class="n-load-end"><?= I18n::e('没有更多了') ?></span>
    <?php endif; ?>
  </div>
</div>
