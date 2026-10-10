<?php
namespace TypechoPlugin\NimbusKit;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 前台提示的语言：使用 Nimbus 主题时跟随主题的「前台语言」设置，其他主题保持中文。
 */
final class Lang
{
    public static function t(string $zh, array $args = []): string
    {
        if (class_exists('Nimbus\\I18n')) {
            try { return \Nimbus\I18n::t($zh, $args); } catch (\Throwable $e) {}
        }
        if (!$args) return $zh;
        $map = [];
        foreach ($args as $k => $v) $map['{' . $k . '}'] = (string)$v;
        return strtr($zh, $map);
    }
}
