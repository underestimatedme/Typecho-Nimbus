<?php
namespace TypechoPlugin\NimbusKit;

use TypechoPlugin\NimbusKit\Lib\GitHub;
use TypechoPlugin\NimbusKit\Lib\Http;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * GitHub 登录评论：OAuth 跳转、回调、签名 cookie 读写。
 * 安全要点：state 有签名与 10 分钟有效期，并与浏览器上的随机数 cookie 绑定（防登录 CSRF）；
 * 回跳地址只允许本站；access token 只在服务端使用，不落库、不下发。
 */
final class GitHubAuth
{
    const NONCE = 'nimbus_gh_n';

    public static function enabled(): bool
    {
        return trim((string)Config::get('githubClientId')) !== '' && trim((string)Config::get('githubClientSecret')) !== '';
    }

    private static function secret(): string
    {
        return (string)\Helper::options()->secret;
    }

    public static function current(): ?array
    {
        if (!self::enabled()) return null;
        $v = isset($_COOKIE[GitHub::COOKIE]) ? (string)$_COOKIE[GitHub::COOKIE] : '';
        return $v === '' ? null : GitHub::readCookie(self::secret(), $v, time());
    }

    public static function endpoint(string $name, string $back = ''): string
    {
        $url = rtrim((string)\Helper::options()->index, '/') . '/nimbus-api/' . $name;
        return $back === '' ? $url : $url . '?back=' . rawurlencode($back);
    }

    private static function cookie(string $name, string $value, int $ttl): void
    {
        $path = \Typecho\Cookie::getPath() ?: '/';
        $secure = stripos((string)\Helper::options()->siteUrl, 'https://') === 0;
        setcookie($name, $value, ['expires' => $ttl > 0 ? time() + $ttl : time() - 3600, 'path' => $path, 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    }

    /** @var \Typecho\Widget\Response|null */
    private static $res = null;

    private static function redirect(string $url): void
    {
        \Typecho\Response::getInstance()->setHeader('Cache-Control', 'no-store');
        self::$res->redirect($url);
    }

    private static function back(string $raw): string
    {
        return GitHub::safeBack($raw, (string)\Helper::options()->siteUrl);
    }

    public static function login(\Typecho\Widget\Request $req, \Typecho\Widget\Response $res): void
    {
        self::$res = $res;
        $back = self::back((string)$req->get('back'));
        if (!self::enabled()) self::redirect($back);
        $state = GitHub::state(self::secret(), $back, time());
        $data = GitHub::verify(self::secret(), $state, time());
        self::cookie(self::NONCE, (string)$data['n'], GitHub::STATE_TTL);
        self::redirect(GitHub::authorizeUrl((string)Config::get('githubBase'), (string)Config::get('githubClientId'), self::endpoint('github-callback'), $state));
    }

    public static function callback(\Typecho\Widget\Request $req, \Typecho\Widget\Response $res): void
    {
        self::$res = $res;
        $state = (string)$req->get('state');
        $data = GitHub::verify(self::secret(), $state, time());
        $nonce = isset($_COOKIE[self::NONCE]) ? (string)$_COOKIE[self::NONCE] : '';
        self::cookie(self::NONCE, '', 0);
        if (!self::enabled() || !$data || !isset($data['b'], $data['n']) || $nonce === '' || !hash_equals((string)$data['n'], $nonce)) {
            self::page('登录请求已过期或无效，请返回重新点击「GitHub 登录」。', self::back(''));
        }
        $back = self::back((string)$data['b']);
        if ((string)$req->get('error') !== '') self::redirect($back);
        $code = (string)$req->get('code');
        if ($code === '' || strlen($code) > 200) self::page('GitHub 没有返回授权码。', $back);
        $tok = Http::request('POST', rtrim((string)Config::get('githubBase'), '/') . '/login/oauth/access_token', ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'client_id' => (string)Config::get('githubClientId'), 'client_secret' => (string)Config::get('githubClientSecret'),
            'code' => $code, 'redirect_uri' => self::endpoint('github-callback'),
        ]));
        $t = json_decode($tok['body'], true);
        $access = is_array($t) ? (string)($t['access_token'] ?? '') : '';
        if ($access === '') {
            error_log('[NimbusKit] GitHub token exchange failed: HTTP ' . $tok['status']);
            self::page('无法向 GitHub 换取登录凭据，请稍后再试。', $back);
        }
        $u = Http::request('GET', rtrim((string)Config::get('githubApi'), '/') . '/user', ['Accept: application/vnd.github+json', 'Authorization: Bearer ' . $access]);
        $id = $u['status'] === 200 ? GitHub::identity((array)json_decode($u['body'], true)) : null;
        if (!$id) {
            error_log('[NimbusKit] GitHub /user failed: HTTP ' . $u['status']);
            self::page('读取 GitHub 账号信息失败，请稍后再试。', $back);
        }
        self::cookie(GitHub::COOKIE, GitHub::cookieValue(self::secret(), $id, time()), GitHub::COOKIE_TTL);
        self::redirect($back);
    }

    public static function logout(\Typecho\Widget\Request $req, \Typecho\Widget\Response $res): void
    {
        self::$res = $res;
        self::cookie(GitHub::COOKIE, '', 0);
        self::redirect(self::back((string)$req->get('back')));
    }

    private static function page(string $msg, string $back): void
    {
        $e = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
        $html = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>GitHub 登录</title>'
            . '<div style="max-width:420px;margin:15vh auto;padding:28px;font:15px/1.7 system-ui,sans-serif;border:1px solid #e5e7eb;border-radius:14px;text-align:center">'
            . '<p>' . $e($msg) . '</p><p><a href="' . $e($back) . '">返回</a></p></div>';
        \Typecho\Response::getInstance()->setStatus(400)->setContentType('text/html')
            ->addResponder(function () use ($html) { echo $html; })->respond();
    }

    /** Widget_Feedback::comment 过滤器：用签名 cookie 中的身份覆盖昵称、邮箱、网址。 */
    public static function filterComment(array $comment, $content, $last = null): array
    {
        if (is_array($last)) $comment = $last;
        if (!empty($comment['authorId'])) return $comment; // 已登录的 Typecho 用户
        $gh = self::current();
        if ($gh) {
            $comment['author'] = (string)$gh['name'];
            $comment['mail'] = (string)$gh['mail'];
            $comment['url'] = (string)$gh['url'];
        } elseif (self::enabled() && Config::get('githubOnly') === '1') {
            throw new \Typecho\Widget\Exception(_t('请先使用 GitHub 登录后再发表评论'), 403);
        }
        return $comment;
    }
}
