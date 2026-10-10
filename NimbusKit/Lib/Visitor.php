<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 访客指纹与浏览去重规则。不保存原始 IP。
 */
final class Visitor
{
    const VIEW_TTL = 21600; // 6 小时

    public static function fingerprint(string $salt, string $ip, string $ua): string
    {
        return sha1($salt . '|' . $ip . '|' . substr($ua, 0, 300));
    }

    public static function viewCookie(int $cid): string
    {
        return 'nimbus_v_' . $cid;
    }

    /** 同源判断：Origin 或 Referer 的主机必须等于站点主机。 */
    public static function sameOrigin(?string $origin, ?string $referer, string $siteUrl): bool
    {
        $site = strtolower((string)parse_url($siteUrl, PHP_URL_HOST));
        if ($site === '') return false;
        foreach ([$origin, $referer] as $u) {
            if (!is_string($u) || $u === '' || $u === 'null') continue;
            $h = strtolower((string)parse_url($u, PHP_URL_HOST));
            return $h === $site;
        }
        return false;
    }
}
