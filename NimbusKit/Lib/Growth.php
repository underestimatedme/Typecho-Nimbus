<?php
namespace TypechoPlugin\NimbusKit\Lib;

/** Small, finite attribution vocabulary. Never stores arbitrary campaign/referrer strings. */
final class Growth
{
    const TTL = 1800;
    const SOURCES = [
        'direct' => '直接 / 来源不可知', 'juejin' => '掘金', 'wechat' => '微信',
        'xiaohongshu' => '小红书', 'github' => 'GitHub', 'v2ex' => 'V2EX',
        'google' => 'Google', 'bing' => 'Bing', 'baidu' => '百度', 'other' => '其他来源',
    ];

    public static function source(string $utm, string $referrerHost, string $siteHost): string
    {
        $utm = strtolower(trim($utm));
        if ($utm !== '') {
            $aliases = ['weixin' => 'wechat', 'xhs' => 'xiaohongshu'];
            $utm = $aliases[$utm] ?? $utm;
            return isset(self::SOURCES[$utm]) ? $utm : 'other';
        }
        $host = strtolower(rtrim($referrerHost, '.'));
        if ($host === '' || $host === strtolower($siteHost)) return 'direct';
        $domains = ['juejin.cn' => 'juejin', 'weixin.qq.com' => 'wechat', 'wechat.com' => 'wechat', 'xiaohongshu.com' => 'xiaohongshu', 'xhslink.com' => 'xiaohongshu', 'github.com' => 'github', 'v2ex.com' => 'v2ex', 'google.com' => 'google', 'bing.com' => 'bing', 'baidu.com' => 'baidu'];
        foreach ($domains as $domain => $source) {
            if ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain) return $source;
        }
        return 'other';
    }

    /** Admin-owned public destinations; at most 20, IDs are bounded and first wins. */
    public static function goals(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 3) continue;
            [$key, $label, $url] = $parts;
            if (!preg_match('/^[a-z][a-z0-9_-]{0,39}$/D', $key) || isset($out[$key]) || $label === '' || !self::safeUrl($url)) continue;
            $out[$key] = ['key' => $key, 'label' => mb_substr(strip_tags($label), 0, 80), 'url' => $url];
            if (count($out) >= 20) break;
        }
        return array_values($out);
    }

    private static function safeUrl(string $url): bool
    {
        if ($url === '' || strlen($url) > 500 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) return false;
        if ($url[0] === '/') return substr($url, 0, 2) !== '//';
        $u = parse_url($url);
        return is_array($u) && in_array(strtolower($u['scheme'] ?? ''), ['http', 'https'], true)
            && !empty($u['host']) && !isset($u['user']) && !isset($u['pass']);
    }

    public static function goalCookie(string $key, string $url): string
    {
        return 'nimbus_g_' . substr(hash('sha256', $key . '|' . $url), 0, 16);
    }
}
