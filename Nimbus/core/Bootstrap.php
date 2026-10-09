<?php
/**
 * Nimbus 启动文件：常量、自动加载、版本检查。
 * 不使用命名空间，便于 functions.php 直接调用。
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

if (!defined('NIMBUS_VERSION')) define('NIMBUS_VERSION', '1.0.0');
if (!defined('NIMBUS_DIR')) define('NIMBUS_DIR', dirname(__DIR__));

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    exit('<h2 style="font-family:sans-serif">Nimbus 需要 PHP 7.4 或更高版本，当前为 ' . PHP_VERSION . '</h2>');
}

spl_autoload_register(function (string $class): void {
    if (strpos($class, 'Nimbus\\') !== 0) return;
    $file = NIMBUS_DIR . '/core/' . str_replace('\\', '/', substr($class, 7)) . '.php';
    if (is_file($file)) require $file;
});

final class NimbusBootstrap
{
    /**
     * themeInit 入口：统一评论相关默认项。
     * @param mixed $archive Widget_Archive
     */
    public static function init($archive): void
    {
        $o = \Helper::options();
        $o->commentsThreaded = true;
        $o->commentsMaxNestingLevels = max(3, (int)$o->commentsMaxNestingLevels);
        $o->commentsPageDisplay = 'last';
        $o->commentsOrder = 'ASC';
    }
}
