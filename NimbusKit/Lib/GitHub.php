<?php
namespace TypechoPlugin\NimbusKit\Lib;

/**
 * GitHub 登录的纯逻辑（无 Typecho 依赖，可单测）：
 * OAuth state 与身份 cookie 都是「base64url(JSON).HMAC-SHA256」，密钥为站点 secret。
 */
final class GitHub
{
    const STATE_TTL = 600;
    const COOKIE_TTL = 2592000; // 30 天
    const COOKIE = 'nimbus_gh';

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function unb64(string $s): string
    {
        $d = base64_decode(strtr($s, '-_', '+/'), true);
        return $d === false ? '' : $d;
    }

    public static function sign(string $secret, array $payload): string
    {
        $body = self::b64((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $body . '.' . self::b64(hash_hmac('sha256', $body, 'nimbus-gh|' . $secret, true));
    }

    /** 校验签名与过期时间（payload 需含 exp）。 */
    public static function verify(string $secret, string $token, int $now): ?array
    {
        if ($secret === '' || strlen($token) > 4096 || substr_count($token, '.') !== 1) return null;
        [$body, $mac] = explode('.', $token);
        $want = self::b64(hash_hmac('sha256', $body, 'nimbus-gh|' . $secret, true));
        if (!hash_equals($want, $mac)) return null;
        $data = json_decode(self::unb64($body), true);
        if (!is_array($data) || !isset($data['exp']) || (int)$data['exp'] < $now) return null;
        return $data;
    }

    public static function state(string $secret, string $back, int $now): string
    {
        return self::sign($secret, ['b' => $back, 'exp' => $now + self::STATE_TTL, 'n' => bin2hex(random_bytes(6))]);
    }

    /** 返回 state 中的回跳地址；无效或过期返回 null。 */
    public static function verifyState(string $secret, string $state, int $now): ?string
    {
        $d = self::verify($secret, $state, $now);
        return $d && isset($d['b']) && is_string($d['b']) ? $d['b'] : null;
    }

    /**
     * 回跳地址只允许本站：同源绝对地址，或以单个 / 开头的站内路径；其他一律回首页（防开放重定向）。
     */
    public static function safeBack(string $back, string $siteUrl): string
    {
        $home = rtrim($siteUrl, '/') . '/';
        $back = trim($back);
        if ($back === '' || preg_match('/[\x00-\x1f\\\\]/', $back)) return $home;
        $site = parse_url($siteUrl);
        if ($back[0] === '/') {
            if (isset($back[1]) && ($back[1] === '/')) return $home;
            return ($site['scheme'] ?? 'http') . '://' . ($site['host'] ?? '') . (isset($site['port']) ? ':' . $site['port'] : '') . $back;
        }
        $u = parse_url($back);
        if (!$u || !isset($u['scheme'], $u['host'])) return $home;
        $same = strtolower($u['scheme']) === strtolower($site['scheme'] ?? '')
            && strtolower($u['host']) === strtolower($site['host'] ?? '')
            && (int)($u['port'] ?? 0) === (int)($site['port'] ?? 0);
        return $same ? $back : $home;
    }

    /** 把 GitHub /user 返回映射为评论身份。 */
    public static function identity(array $u): ?array
    {
        $id = (int)($u['id'] ?? 0);
        $login = (string)($u['login'] ?? '');
        if ($id <= 0 || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/', $login)) return null;
        $name = trim((string)($u['name'] ?? ''));
        $name = $name !== '' ? mb_substr(preg_replace('/[<>"\'&\x00-\x1f]/u', '', $name), 0, 60) : $login;
        if ($name === '') $name = $login;
        $mail = (string)($u['email'] ?? '');
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $mail = $id . '+' . $login . '@users.noreply.github.com';
        return [
            'id' => $id,
            'login' => $login,
            'name' => $name,
            'mail' => $mail,
            'url' => 'https://github.com/' . $login,
            'avatar' => 'https://avatars.githubusercontent.com/u/' . $id . '?v=4',
        ];
    }

    public static function cookieValue(string $secret, array $identity, int $now): string
    {
        return self::sign($secret, $identity + ['exp' => $now + self::COOKIE_TTL]);
    }

    public static function readCookie(string $secret, string $value, int $now): ?array
    {
        $d = self::verify($secret, $value, $now);
        if (!$d || empty($d['id']) || empty($d['login'])) return null;
        unset($d['exp']);
        return $d;
    }

    public static function authorizeUrl(string $base, string $clientId, string $redirect, string $state): string
    {
        return rtrim($base, '/') . '/login/oauth/authorize?' . http_build_query([
            'client_id' => $clientId, 'redirect_uri' => $redirect, 'state' => $state, 'allow_signup' => 'true',
        ]);
    }
}
