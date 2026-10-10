<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * 通用签名令牌：base64url(JSON).HMAC-SHA256，按用途区分密钥，payload 需含 exp。
 */
final class Token
{
    private static function b64(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

    public static function sign(string $secret, string $purpose, array $payload): string
    {
        $body = self::b64((string)json_encode($payload, JSON_UNESCAPED_SLASHES));
        return $body . '.' . self::b64(hash_hmac('sha256', $body, 'nimbus-' . $purpose . '|' . $secret, true));
    }

    public static function verify(string $secret, string $purpose, string $token, int $now): ?array
    {
        if ($secret === '' || strlen($token) > 4096 || substr_count($token, '.') !== 1) return null;
        [$body, $mac] = explode('.', $token);
        if (!hash_equals(self::b64(hash_hmac('sha256', $body, 'nimbus-' . $purpose . '|' . $secret, true)), $mac)) return null;
        $raw = base64_decode(strtr($body, '-_', '+/'), true);
        $d = $raw === false ? null : json_decode($raw, true);
        if (!is_array($d) || (int)($d['exp'] ?? 0) < $now) return null;
        return $d;
    }

    /** 「本浏览器提交过的评论」列表：追加新 coid，最多保留 $max 条。 */
    public static function addComment(array $ids, int $coid, int $max = 60): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($i) { return $i > 0; }));
        $ids = array_values(array_diff($ids, [$coid]));
        $ids[] = $coid;
        return array_slice($ids, -$max);
    }
}
