<?php
namespace Nimbus;

/**
 * 侧栏小部件注册与渲染。
 */
final class Widgets
{
    const KNOWN = ['author', 'notice', 'hot', 'recent-comments', 'tags', 'categories', 'calendar', 'site-info', 'custom-html'];
    const DEFAULT_ORDER = ['author', 'notice', 'hot', 'recent-comments', 'tags', 'categories'];

    /** 解析后台「小部件顺序」文本。 */
    public static function order(?string $raw): array
    {
        $raw = (string)$raw;
        $parts = preg_split('/[\n\r,，;；]+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = strtolower(trim($p));
            if ($p === '' || $p[0] === '#') continue;
            if (!in_array($p, self::KNOWN, true) || in_array($p, $out, true)) continue;
            $out[] = $p;
        }
        return empty($out) ? self::DEFAULT_ORDER : $out;
    }

    /** @param mixed $archive */
    public static function render(string $name, $archive): void
    {
        $file = NIMBUS_DIR . '/widgets/' . $name . '.php';
        if (!is_file($file)) return;
        include $file;
    }

    /** 站长用户信息（作者卡回落）。 */
    public static function owner(): array
    {
        static $owner = null;
        if ($owner !== null) return $owner;
        $db = \Typecho_Db::get();
        $row = $db->fetchRow($db->select()->from('table.users')->where('group = ?', 'administrator')->order('uid', \Typecho_Db::SORT_ASC)->limit(1));
        $owner = $row ?: ['screenName' => (string)\Helper::options()->title, 'mail' => '', 'url' => ''];
        return $owner;
    }
}
