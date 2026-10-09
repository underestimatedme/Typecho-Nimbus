<?php
/**
 * 导航：透明顶栏 .n-nav（Hero 页）或实色 .n-nav-solid，悬浮胶囊 .n-nav-float，移动端抽屉 .n-drawer。
 * 变量：$archive, $solid(bool)
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\Menu;

$items = Menu::items();
$current = (string)$archive->request->getRequestUrl();
$logoImg = (string)Options::get('LogoImage', '');
$logoText = (string)Options::get('LogoText', (string)\Helper::options()->title);
$switchable = Options::bool('ThemeSwitchable');
$renderLogo = function (string $cls) use ($logoImg, $logoText) {
    $home = (string)\Helper::options()->siteUrl;
    if ($logoImg !== '') return '<a class="' . $cls . '" href="' . View::e($home) . '" aria-label="' . View::e($logoText) . '"><img src="' . View::e($logoImg) . '" alt="' . View::e($logoText) . '"></a>';
    return '<a class="' . $cls . '" href="' . View::e($home) . '"><i class="n-logo-mark" aria-hidden="true"></i><span>' . View::e($logoText) . '</span></a>';
};
$renderMenu = function (string $cls) use ($items, $current) {
    $h = '<ul class="' . $cls . '">';
    foreach ($items as $it) {
        $isCur = Menu::isCurrent($it['url'], $current);
        $has = !empty($it['children']);
        $h .= '<li class="n-menu-item' . ($isCur ? ' is-current' : '') . ($has ? ' has-children' : '') . '">';
        $h .= '<a href="' . View::e($it['url']) . '"' . ($isCur ? ' aria-current="page"' : '') . '>' . ($it['icon'] ? View::icon($it['icon']) : '') . View::e($it['title']) . ($has ? View::icon('chevron-down', 'n-menu-caret') : '') . '</a>';
        if ($has) {
            $h .= '<ul class="n-submenu">';
            foreach ($it['children'] as $c) $h .= '<li><a href="' . View::e($c['url']) . '">' . ($c['icon'] ? View::icon($c['icon']) : '') . View::e($c['title']) . '</a></li>';
            $h .= '</ul>';
        }
        $h .= '</li>';
    }
    return $h . '</ul>';
};
$renderActs = function () use ($switchable) {
    $h = '<div class="n-nav-acts">';
    $h .= '<button type="button" class="n-icon-btn" data-search-open aria-label="搜索" title="搜索 (Ctrl+K)">' . View::icon('search') . '</button>';
    if ($switchable) $h .= '<button type="button" class="n-icon-btn n-theme-toggle" data-theme-toggle aria-label="切换亮暗模式" title="切换亮暗模式">' . View::icon('moon', 'n-ico-moon') . View::icon('sun', 'n-ico-sun') . '</button>';
    $h .= '<button type="button" class="n-icon-btn n-drawer-open" data-drawer-open aria-label="打开菜单">' . View::icon('menu') . '</button>';
    return $h . '</div>';
};
?>
<header class="n-nav<?= $solid ? ' n-nav-solid' : '' ?>" id="n-nav" data-nav>
  <div class="n-container n-nav-in">
    <?= $renderLogo('n-logo') ?>
    <nav class="n-nav-menu" aria-label="主导航"><?= $renderMenu('n-menu') ?></nav>
    <?= $renderActs() ?>
  </div>
</header>
<div class="n-nav-float" data-nav-float aria-hidden="true">
  <?= $renderLogo('n-logo n-logo-sm') ?>
  <nav class="n-nav-menu"><?= $renderMenu('n-menu') ?></nav>
  <?= $renderActs() ?>
</div>
<dialog class="n-drawer" id="n-drawer" data-drawer aria-label="菜单">
  <div class="n-drawer-in">
    <div class="n-drawer-head"><?= $renderLogo('n-logo n-logo-sm') ?><button type="button" class="n-icon-btn" data-drawer-close aria-label="关闭"><?= View::icon('close') ?></button></div>
    <?= $renderMenu('n-drawer-menu') ?>
    <div class="n-drawer-foot">
      <button type="button" class="n-btn" data-search-open><?= View::icon('search') ?> 搜索</button>
      <?php if ($switchable): ?><button type="button" class="n-btn" data-theme-toggle><?= View::icon('moon', 'n-ico-moon') ?><?= View::icon('sun', 'n-ico-sun') ?> 亮暗切换</button><?php endif; ?>
    </div>
  </div>
</dialog>
