<?php
/**
 * 页面头部：<!DOCTYPE> 到 <main> 开始。
 * 变量：$archive（模板里的 $this）
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
use Nimbus\Options;
use Nimbus\View;
use Nimbus\Seo;
use Nimbus\Kit;

$o = \Helper::options();
$page = 'index';
if ($archive->is('post')) $page = 'post';
elseif ($archive->is('page')) $page = 'page';
elseif ($archive->is('archive', '404')) $page = 'error';
elseif ($archive->is('single')) $page = 'page';

$themeLight = (string)Options::get('ThemeLight', 'sky');
$themeDark = (string)Options::get('ThemeDark', 'dusk');
$follow = Options::bool('ThemeFollowSystem');
$switchable = Options::bool('ThemeSwitchable');
$fontScheme = (string)Options::get('FontScheme', 'serif');
$cid = $archive->is('single') ? (int)$archive->cid : 0;

$title = (string)$o->title;
$sub = (string)Options::get('SiteSubtitle', '');
if ($archive->is('index')) {
    $docTitle = $title . ($sub !== '' ? ' — ' . $sub : '');
} else {
    $archiveTitle = (string)$archive->getArchiveTitle(['category' => '分类 %s', 'search' => '搜索 %s', 'tag' => '标签 %s', 'author' => '作者 %s'], '', '');
    if ($archive->is('single')) $archiveTitle = (string)$archive->title;
    if ($page === 'error') $archiveTitle = '页面不存在';
    $docTitle = ($archiveTitle !== '' ? $archiveTitle . ' — ' : '') . $title;
}

$config = [
    'version' => NIMBUS_VERSION,
    'assets' => preg_replace('/\?v=.*$/', '', View::asset('')),
    'index' => rtrim((string)$o->index, '/'),
    'site' => rtrim((string)$o->siteUrl, '/'),
    'admin' => (string)$o->adminUrl,
    'api' => Kit::apiUrl(),
    'page' => $page,
    'cid' => $cid,
    'themes' => ['light' => $themeLight, 'dark' => $themeDark, 'follow' => $follow, 'switchable' => $switchable],
    'router' => Options::bool('RouterEnable'),
    'lightbox' => Options::bool('LightboxEnable'),
    'codeFold' => Options::int('CodeFoldLines'),
    'codeLines' => Options::bool('CodeLineNumbers'),
    'math' => (string)Options::get('MathEnable', 'auto'),
    'mermaid' => (string)Options::get('MermaidEnable', 'auto'),
    'pagination' => (string)Options::get('Pagination', 'normal'),
    'debug' => Options::bool('Debug'),
    'i18n' => ['copy' => '复制', 'copied' => '已复制', 'loadMore' => '加载更多', 'loading' => '加载中…', 'noMore' => '没有更多了', 'search' => '搜索', 'expand' => '展开全部', 'collapse' => '收起', 'linkCopied' => '链接已复制', 'toc' => '目录'],
];
if ($archive->is('single')) {
    $config['hasMath'] = isset($nimbusContent['hasMath']) ? $nimbusContent['hasMath'] : false;
    $config['hasMermaid'] = isset($nimbusContent['hasMermaid']) ? $nimbusContent['hasMermaid'] : false;
}
$favicon = (string)Options::get('Favicon', '');
?><!DOCTYPE html>
<html lang="zh-CN" data-theme="<?= View::e($themeLight) ?>" data-radius="<?= View::e((string)Options::get('Radius', 'md')) ?>" data-motion="<?= View::e((string)Options::get('Motion', 'full')) ?>" data-font="<?= View::e($fontScheme) ?>"<?= Options::bool('Mourning') ? ' data-mourning' : '' ?>>
<head>
<meta charset="<?php $o->charset(); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e($docTitle) ?></title>
<script>(function(){var l=<?= json_encode($themeLight) ?>,d=<?= json_encode($themeDark) ?>,f=<?= $follow ? 'true' : 'false' ?>,t=null;try{t=localStorage.getItem('nimbus-theme')}catch(e){}if(t!=='sky'&&t!=='dusk'&&t!=='cyber'){t=(f&&matchMedia('(prefers-color-scheme: dark)').matches)?d:l}document.documentElement.dataset.theme=t;})();</script>
<meta name="theme-color" content="#f4f7fb">
<?php if ($favicon !== ''): ?><link rel="icon" href="<?= View::e($favicon) ?>">
<?php else: ?><link rel="icon" href="<?= View::e(preg_replace('/\?v=.*$/', '', View::asset('img/favicon.svg'))) ?>" type="image/svg+xml">
<?php endif; ?>
<?php $archive->header('description=&keywords=&generator=&template=&pingback=&xmlrpc=&wlw=&commentReply=&antiSpam='); ?>
<?= Seo::head($archive) ?>
<?php if ($fontScheme === 'custom' && Options::get('FontCustomUrl')): ?>
<link rel="stylesheet" href="<?= View::e((string)Options::get('FontCustomUrl')) ?>">
<style>:root{--font-custom:<?= View::e((string)Options::get('FontCustomFamily', 'serif')) ?>,serif}</style>
<?php elseif ($fontScheme === 'serif'): ?>
<link rel="preload" href="<?= View::e(View::asset('fonts/NotoSerifSC-subset.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= View::e(View::asset('fonts/fonts.css')) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= View::e(View::asset('css/nimbus.css')) ?>">
<script id="n-config" type="application/json"><?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php if (Options::get('CustomCss')): ?><style><?= (string)Options::get('CustomCss') ?></style>
<?php endif; ?>
<?= (string)Options::get('HeadCode', '') ?>
</head>
<body class="n-page-<?= $page ?>" data-page="<?= $page ?>" data-cid="<?= $cid ?>">
<div class="n-progress" id="n-progress" aria-hidden="true"></div>
<?php View::partial('nav', ['archive' => $archive, 'solid' => !($archive->is('index') && Options::bool('HeroEnable') && (int)$archive->request->get('page', 1) <= 1)]); ?>
<main id="n-main" class="n-main-wrap" data-page="<?= $page ?>" data-cid="<?= $cid ?>">
