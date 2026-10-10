<?php
namespace Nimbus;

/**
 * 模板输出工具：转义、片段、资源 URL、内联 SVG 图标。
 */
final class View
{
    /** @var array<string,string> 图标内容（24×24 viewBox，stroke 线性风格） */
    private static $icons = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'arrow-up' => '<path d="M12 19V5M5 12l7-7 7 7"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'comment' => '<path d="M21 12a8 8 0 0 1-11.6 7.2L4 21l1.8-5.4A8 8 0 1 1 21 12z"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l8.8 8.8 8.8-8.8a5.5 5.5 0 0 0 0-7.8z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
        'star' => '<path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7.1L12 17.3 5.8 21l1.6-7.1L2 9.2l7.1-.6z"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
        'check' => '<path d="m5 12 5 5L20 7"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'home' => '<path d="m3 11 9-8 9 8v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'coffee' => '<path d="M17 8h1a4 4 0 0 1 0 8h-1M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z"/><path d="M6 2v2M10 2v2M14 2v2"/>',
        'smile' => '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>',
        'alert' => '<path d="M12 3 2 21h20z"/><path d="M12 10v4M12 18h.01"/>',
        'refresh' => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-8 8"/>',
        'zoom' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>',
        'github' => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.9a3.4 3.4 0 0 0-.9-2.6c3.1-.3 6.4-1.5 6.4-7a5.4 5.4 0 0 0-1.5-3.8 5 5 0 0 0-.1-3.7s-1.2-.4-3.9 1.5a13.4 13.4 0 0 0-7 0C6.3.6 5.1 1 5.1 1a5 5 0 0 0-.1 3.7A5.4 5.4 0 0 0 3.5 8.5c0 5.5 3.3 6.7 6.4 7a3.4 3.4 0 0 0-.9 2.6V22"/>',
        'weibo' => '<path d="M10 20c-4.4 0-8-2.2-8-5.4 0-1.7 1-3.5 2.8-5.3 2.4-2.4 5.1-3.5 6.2-2.4.5.5.5 1.3.2 2.3 2.5-.6 4.6.3 4.1 2.1 3.5.9 3.7 3.2 3.7 3.3 0 3.2-4.6 5.4-9 5.4z"/><path d="M15.5 3.5a5 5 0 0 1 5 5.5M15.5 6.5a2.5 2.5 0 0 1 2.5 2.5"/><circle cx="9" cy="14" r="1"/>',
        'bilibili' => '<rect x="3" y="7" width="18" height="13" rx="3"/><path d="m8 3 2 4M16 3l-2 4M9 13v1M15 13v1"/>',
        'twitter' => '<path d="M4 4l16 16M20 4 4 20"/>',
        'email' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'rss' => '<path d="M4 11a9 9 0 0 1 9 9M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/>',
        'qq' => '<path d="M12 2c-3.9 0-6 3-6 7v2c-1 1.5-2 3.5-1.5 5 .3.8 1 .5 1.5 0 .3 2 1.3 3 2.5 3.5-1 .5-1 1.5.5 1.5h6c1.5 0 1.5-1 .5-1.5 1.2-.5 2.2-1.5 2.5-3.5.5.5 1.2.8 1.5 0 .5-1.5-.5-3.5-1.5-5V9c0-4-2.1-7-6-7z"/>',
        'wechat' => '<path d="M9 4C5.1 4 2 6.7 2 10c0 1.8.9 3.4 2.4 4.5L4 17l2.7-1.4c.7.2 1.5.4 2.3.4"/><path d="M15 8c3.9 0 7 2.5 7 5.5 0 1.6-.9 3-2.3 4L20 20l-2.5-1.2c-.8.2-1.6.3-2.5.3-3.9 0-7-2.5-7-5.6S11.1 8 15 8z"/>',
        'telegram' => '<path d="m21 4-4 16-6-4-3 3v-5l9-8-11 7-4-2z"/>',
        'zhihu' => '<path d="M4 5h9M8 5v6c0 3-1 5-3 7M5 11h8M10 11l3 7M15 5h5v12h-5zM17 17l-2 3"/>',
        'alipay' => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M7 12h10M12 7v3M8 15c3 2 6 2 9-1"/>',
    ];

    public static function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }

    /** 评论区布局：thread（默认）或 wall（留言板卡片墙）。由模板在 need('comments.php') 前设置。 */
    public static $commentLayout = 'thread';

    /** 由字符串稳定生成的色相（首字母头像配色）。 */
    public static function hue(string $s): int
    {
        return (int)(hexdec(substr(md5($s), 0, 4)) % 360);
    }

    public static function initial(string $s): string
    {
        $s = trim($s);
        return $s === '' ? '?' : mb_strtoupper(mb_substr($s, 0, 1));
    }

    /** Typecho 存储标题时已做 htmlspecialchars；先解码得到原文（再用 e() 输出）。 */
    public static function t(?string $title): string
    {
        return htmlspecialchars_decode((string)$title, ENT_QUOTES);
    }

    /** 渲染 partials/{$name}.php，$vars 可用；$archive 一般传模板里的 $this。 */
    public static function partial(string $name, array $vars = []): void
    {
        $file = NIMBUS_DIR . '/partials/' . $name . '.php';
        if (!is_file($file)) return;
        extract($vars, EXTR_SKIP);
        include $file;
    }

    public static function themeUrl(): string
    {
        return rtrim((string)\Helper::options()->themeUrl, '/');
    }

    /** 静态资源 URL，支持 CDN 前缀与版本号。 */
    public static function asset(string $path): string
    {
        $prefix = Options::get('CdnPrefix');
        $base = is_string($prefix) && $prefix !== '' ? rtrim($prefix, '/') : self::themeUrl() . '/assets';
        return $base . '/' . ltrim($path, '/') . '?v=' . NIMBUS_VERSION;
    }

    /** 内联 SVG 图标。未知名称返回空串。 */
    public static function icon(string $name, string $class = ''): string
    {
        if (!isset(self::$icons[$name])) return '';
        $cls = trim('n-ico ' . $class);
        return '<svg class="' . self::e($cls) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . self::$icons[$name] . '</svg>';
    }

    public static function hasIcon(string $name): bool
    {
        return isset(self::$icons[$name]);
    }

    /** 相对时间（用于评论）。 */
    public static function timeAgo(int $ts): string
    {
        $d = time() - $ts;
        if ($d < 60) return '刚刚';
        if ($d < 3600) return floor($d / 60) . ' 分钟前';
        if ($d < 86400) return floor($d / 3600) . ' 小时前';
        if ($d < 86400 * 30) return floor($d / 86400) . ' 天前';
        return date('Y-m-d', $ts);
    }

    /** 数字缩写：1234 → 1.2k */
    public static function num(int $n): string
    {
        if ($n >= 10000) return round($n / 10000, 1) . 'w';
        if ($n >= 1000) return round($n / 1000, 1) . 'k';
        return (string)$n;
    }
}
