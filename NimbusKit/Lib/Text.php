<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 文本处理：说说清洗、照片列表、URL 过滤、搜索摘要。
 */
final class Text
{
    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /** 只允许 http(s) 绝对地址或以 / 开头的站内路径。 */
    public static function safeUrl(string $u): string
    {
        $u = trim($u);
        if ($u === '') return '';
        if (preg_match('#^https?://[^\s<>"\']+$#i', $u)) return $u;
        if (preg_match('#^/(?!/)[^\s<>"\']*$#', $u)) return $u;
        return '';
    }

    /** 说说内容：转义 + 换行 + 自动链接（仅 http/https）。 */
    public static function moment(string $raw): string
    {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        $html = self::e($raw);
        $html = (string)preg_replace_callback('#https?://[^\s<>"\'（）()，。！？]+#iu', function ($m) {
            $url = html_entity_decode($m[0], ENT_QUOTES, 'UTF-8');
            if (self::safeUrl($url) === '') return $m[0];
            return '<a href="' . self::e($url) . '" target="_blank" rel="noopener nofollow">' . $m[0] . '</a>';
        }, $html);
        return nl2br($html, false);
    }

    /**
     * 照片列表：每行 `URL` 或 `URL|说明`，非法 URL 丢弃。
     * @return array<int,array{url:string,caption:string}>
     */
    public static function photos(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = explode('|', $line, 2);
            $url = self::safeUrl($parts[0]);
            if ($url === '') continue;
            $out[] = ['url' => $url, 'caption' => isset($parts[1]) ? trim($parts[1]) : ''];
        }
        return $out;
    }

    /** 图片 URL 列表（说说）：每行一个。 */
    public static function urls(string $raw): array
    {
        return array_values(array_map(function ($p) { return $p['url']; }, self::photos($raw)));
    }

    /**
     * 收藏夹按分组聚合：分组顺序取组内最小排序值，组内按 sort、id。
     * @param array<int,array> $rows 每项含 group、sort、id
     * @return array<int,array{name:string,items:array}>
     */
    public static function groupBookmarks(array $rows): array
    {
        $groups = [];
        foreach ($rows as $r) {
            $g = trim((string)($r['group'] ?? '')) ?: '未分组';
            if (!isset($groups[$g])) $groups[$g] = ['name' => $g, 'min' => PHP_INT_MAX, 'items' => []];
            $groups[$g]['items'][] = $r;
            $groups[$g]['min'] = min($groups[$g]['min'], (int)($r['sort'] ?? 0));
        }
        foreach ($groups as &$g) {
            usort($g['items'], function ($a, $b) { return [(int)$a['sort'], (int)$a['id']] <=> [(int)$b['sort'], (int)$b['id']]; });
        }
        unset($g);
        $list = array_values($groups);
        usort($list, function ($a, $b) { return $a['min'] <=> $b['min']; });
        return array_map(function ($g) { return ['name' => $g['name'], 'items' => $g['items']]; }, $list);
    }

    /** 技术栈标签：逗号 / 顿号 / 分号分隔（保留「Vue 3」这类带空格的名字），去重。 */
    public static function tags(string $raw): array
    {
        $out = [];
        foreach (preg_split('/[,，、;；\n]+/u', $raw) ?: [] as $t) {
            $t = trim($t);
            if ($t !== '' && !in_array($t, $out, true)) $out[] = mb_substr($t, 0, 30);
        }
        return array_slice($out, 0, 12);
    }

    /** 日期键：20261011 */
    public static function dayKey(int $ts): int
    {
        return (int)date('Ymd', $ts);
    }

    /** 去掉 HTML / Markdown 标记的纯文本。 */
    public static function plain(string $text): string
    {
        $s = preg_replace('/\[hide[^\]]*\].*?\[\/hide\]/su', '', $text); // 回复可见内容不进入摘要与搜索
        $s = preg_replace('/<!--.*?-->/s', '', (string)$s);
        $s = preg_replace('/```.*?```/s', ' ', (string)$s);
        $s = strip_tags((string)$s);
        $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        $s = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $s);
        $s = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', (string)$s);
        $s = preg_replace('/[#>*`_~|]+/', '', (string)$s);
        return trim((string)preg_replace('/\s+/u', ' ', (string)$s));
    }

    /** 搜索摘要：以关键词为中心截取。 */
    public static function excerpt(string $text, string $kw, int $len = 80): string
    {
        $plain = self::plain($text);
        if ($plain === '') return '';
        $pos = $kw !== '' ? mb_stripos($plain, $kw) : false;
        if ($pos === false || $pos < $len / 2) {
            $s = mb_substr($plain, 0, $len);
            return $s . (mb_strlen($plain) > $len ? '…' : '');
        }
        $start = max(0, (int)($pos - $len / 3));
        $s = mb_substr($plain, $start, $len);
        return '…' . $s . ($start + $len < mb_strlen($plain) ? '…' : '');
    }

    /**
     * 宽松 LIKE 模式：用户输入里的 % 和 _ 换成单字符通配 _。
     * 不依赖 ESCAPE 子句（各数据库默认转义符不同），精确匹配交给 contains() 在 PHP 里复核。
     */
    public static function likePattern(string $s): string
    {
        return '%' . str_replace(['%', '_'], '_', $s) . '%';
    }

    /** 不区分大小写的子串判断。 */
    public static function contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && mb_stripos($haystack, $needle) !== false;
    }
}
