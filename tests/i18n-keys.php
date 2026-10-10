<?php
/**
 * 收集前台源码中的翻译键：PHP 的 I18n::t / I18n::e / Lang::t，JS 的 tr()。
 * 用法：php tests/i18n-keys.php   输出缺少英文译文的键（被 tests/unit/I18nTest.php 复用）
 */
function nimbus_i18n_keys(string $root): array
{
    $php = []; $js = [];
    $skip = ['/core/Admin.php', '/core/Config/', '/assets/js/admin.js', '/lang/'];
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/Nimbus', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = str_replace('\\', '/', $f->getPathname());
        if (strpos($p, '/assets/vendor/') !== false) continue;
        foreach ($skip as $s) if (strpos($p, $s) !== false) continue 2;
        if (preg_match('/\.(php|js)$/', $p)) $files[] = $p;
    }
    foreach (['Action.php', 'Hooks.php', 'GitHubAuth.php', 'Api.php'] as $f) $files[] = $root . '/NimbusKit/' . $f;
    $str = "'((?:[^'\\\\]|\\\\.)*)'";
    foreach ($files as $p) {
        $src = (string)file_get_contents($p);
        if (substr($p, -3) === '.js') {
            preg_match_all('/\btr\(' . $str . '/u', $src, $m);
            foreach ($m[1] as $k) $js[stripcslashes($k)] = true;
        } else {
            preg_match_all('/(?:I18n::[te]|Lang::t)\(' . $str . '/u', $src, $m);
            foreach ($m[1] as $k) $php[str_replace(["\\'", '\\\\'], ["'", '\\'], $k)] = true;
        }
    }
    return ['ui' => array_keys($php), 'js' => array_keys($js)];
}

if (PHP_SAPI === 'cli' && realpath($argv[0]) === __FILE__) {
    $root = dirname(__DIR__);
    $keys = nimbus_i18n_keys($root);
    $pack = include $root . '/Nimbus/lang/en.php';
    $miss = 0;
    foreach (['ui', 'js'] as $sec) foreach ($keys[$sec] as $k) {
        $has = $sec === 'js' ? isset($pack['js'][$k]) : (isset($pack['ui'][$k]) || isset($pack['js'][$k]));
        if (!$has) { echo "[$sec] $k\n"; $miss++; }
    }
    echo "keys: ui=" . count($keys['ui']) . " js=" . count($keys['js']) . " missing=$miss\n";
}
