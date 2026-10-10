<?php
/**
 * php -S 开发服务器路由（仅本地测试用，不随主题发布）。
 * Typecho 给 cookie 加了 Domain=localhost / IP，浏览器会拒收导致后台无法登录；
 * 这里在响应发出前去掉 Set-Cookie 的 Domain 属性，变成 host-only cookie。
 */
$root = $_SERVER['DOCUMENT_ROOT'];
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 静态资源直接交给内置服务器
if ($path !== '/' && is_file($root . $path) && !preg_match('/\.php$/i', $path)) return false;

header_register_callback(function () {
    $cookies = [];
    foreach (headers_list() as $h) if (stripos($h, 'Set-Cookie:') === 0) $cookies[] = $h;
    if (!$cookies) return;
    header_remove('Set-Cookie');
    foreach ($cookies as $c) header(preg_replace('/;\s*domain=[^;]*/i', '', $c), false);
});

// 找到要执行的 PHP 文件：/admin/login.php、/install.php、/index.php/xxx 或伪静态路径
$script = 'index.php';
if (preg_match('#^(/.*?\.php)(/|$)#i', $path, $m) && is_file($root . $m[1])) $script = ltrim($m[1], '/');
elseif (is_dir($root . $path) && is_file(rtrim($root . $path, '/') . '/index.php')) $script = trim($path, '/') . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/' . $script;
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/' . $script;
chdir(dirname($root . '/' . $script));
require $root . '/' . $script;
