<?php
namespace TypechoPlugin\NimbusKit;

use TypechoPlugin\NimbusKit\Lib\Visitor;
use TypechoPlugin\NimbusKit\Lib\Sitemap;
use TypechoPlugin\NimbusKit\Lib\Growth;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 前台接口：/nimbus-api/[endpoint] 与 /sitemap.xml
 */
class Action extends \Widget\Base
{
    public function dispatch()
    {
        $endpoint = (string)$this->request->get('endpoint');
        try {
            switch ($endpoint) {
                case 'growth':
                    $this->growth();
                    break;
                case 'view':
                    $this->view();
                    break;
                case 'like':
                    $this->like();
                    break;
                case 'search':
                    $this->search();
                    break;
                case 'github-login':
                    GitHubAuth::login($this->request, $this->response);
                    break;
                case 'github-callback':
                    GitHubAuth::callback($this->request, $this->response);
                    break;
                case 'github-logout':
                    GitHubAuth::logout($this->request, $this->response);
                    break;
                default:
                    $this->fail('not found', 404);
            }
        } catch (\Typecho\Widget\Terminal $t) {
            throw $t;
        } catch (\Throwable $e) {
            error_log('[NimbusKit] ' . $e->getMessage());
            $this->fail('server error', 500);
        }
    }

    private function send(array $body, int $code = 200): void
    {
        $this->response->setStatus($code);
        $this->response->setHeader('Cache-Control', 'no-store');
        $this->response->throwJson($body);
    }

    private function ok($data): void
    {
        $this->send(['ok' => true, 'data' => $data]);
    }

    private function fail(string $error, int $code = 400): void
    {
        $this->send(['ok' => false, 'error' => $error], $code);
    }

    private function requirePost(): void
    {
        if (!$this->request->isPost()) $this->fail('method not allowed', 405);
        $siteUrl = (string)$this->options->siteUrl;
        if (!Visitor::sameOrigin($this->request->getHeader('Origin'), $this->request->getReferer(), $siteUrl)) {
            $this->fail('forbidden', 403);
        }
    }

    private function visitor(): string
    {
        return Visitor::fingerprint((string)$this->options->secret, (string)$this->request->getIp(), (string)$this->request->getAgent());
    }

    private function view(): void
    {
        if (!Config::feature('views')) $this->fail('disabled', 404);
        $this->requirePost();
        $cid = (int)$this->request->get('cid');
        if ($cid <= 0 || !Store::publicPost($cid)) $this->fail('invalid cid', 404);
        $cookie = Visitor::viewCookie($cid);
        if (!empty($_COOKIE[$cookie])) {
            $this->ok(['views' => Store::stat('post', $cid)['views'], 'counted' => false]);
        }
        $views = Store::incrementView('post', $cid);
        setcookie($cookie, '1', ['expires' => time() + Visitor::VIEW_TTL, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        $this->ok(['views' => $views, 'counted' => true]);
    }

    private function growth(): void
    {
        if (Config::get('analyticsEnable') !== '1') $this->fail('disabled', 404);
        $this->requirePost();
        if ($this->request->getHeader('DNT') === '1' || $this->request->getHeader('Sec-GPC') === '1') {
            $this->ok(['counted' => false, 'reason' => 'privacy']);
        }
        if (\Widget\User::alloc()->hasLogin()) $this->ok(['counted' => false, 'reason' => 'signed-in']);
        $kind = $_POST['kind'] ?? null;
        $utm = $_POST['utm'] ?? '';
        $referrer = $_POST['referrer'] ?? '';
        if (!is_string($kind) || !in_array($kind, ['entry', 'goal'], true) || !is_string($utm) || strlen($utm) > 40 || !is_string($referrer) || strlen($referrer) > 253) $this->fail('invalid input');
        $goal = null;
        if ($kind === 'goal') {
            $key = $_POST['goal'] ?? '';
            if (!is_string($key)) $this->fail('invalid input');
            foreach (Growth::goals((string)Config::get('analyticsGoals')) as $g) if ($g['key'] === $key) { $goal = $g; break; }
            if (!$goal) $this->fail('invalid goal', 404);
        }
        $site = (string)$this->options->siteUrl;
        $scope = 'nimbus_g_source_' . substr(hash('sha256', $site), 0, 12);
        $saved = $_COOKIE[$scope] ?? '';
        $hasSource = is_string($saved) && isset(Growth::SOURCES[$saved]);
        $source = $hasSource ? $saved : Growth::source($utm, $referrer, (string)parse_url($site, PHP_URL_HOST));
        $cookie = $goal ? Growth::goalCookie($goal['key'], $goal['url']) : $scope . '_entry';
        if (!empty($_COOKIE[$cookie])) $this->ok(['counted' => false, 'source' => $source]);
        $options = ['expires' => time() + Growth::TTL, 'path' => rtrim((string)parse_url($site, PHP_URL_PATH), '/') . '/', 'secure' => parse_url($site, PHP_URL_SCHEME) === 'https', 'httponly' => true, 'samesite' => 'Lax'];
        if (!$hasSource) {
            setcookie($scope, $source, $options);
        }
        Store::countGrowth($kind, $source, $goal ? $goal['key'] : '');
        setcookie($cookie, '1', $options);
        $this->ok(['counted' => true, 'source' => $source]);
    }

    private function like(): void
    {
        if (!Config::feature('likes')) $this->fail('disabled', 404);
        $this->requirePost();
        $type = (string)$this->request->get('type', 'post') === 'moment' ? 'moment' : 'post';
        $cid = (int)$this->request->get('cid');
        $valid = $type === 'post' ? Store::publicPost($cid) : Store::publicMoment($cid);
        if ($cid <= 0 || !$valid) $this->fail('invalid id', 404);
        $this->ok(Store::like($type, $cid, $this->visitor()));
    }

    private function search(): void
    {
        if (!Config::feature('search')) $this->fail('disabled', 404);
        $q = trim((string)$this->request->get('q'));
        $len = mb_strlen($q);
        if ($len < 2 || $len > 40) $this->fail(Lang::t('关键词需为 2 到 40 个字'));
        $this->ok(Store::search($q, 8));
    }

    /** /sitemap.xml */
    public function sitemap()
    {
        if (!Config::feature('sitemap')) throw new \Typecho\Widget\Exception('Not Found', 404);
        $db = $this->db;
        $w = \Widget\Base\Contents::alloc();
        $urls = [['loc' => (string)$this->options->siteUrl, 'lastmod' => time(), 'priority' => '1.0']];
        $rows = $db->fetchAll($db->select()->from('table.contents')->where('status = ?', 'publish')->where('type = ? OR type = ?', 'post', 'page')->where("password IS NULL OR password = ''")->where('created < ?', (int)$this->options->time)->order('modified', \Typecho\Db::SORT_DESC)->limit(5000));
        foreach ($rows as $r) {
            $f = $w->filter($r);
            $urls[] = ['loc' => $f['permalink'], 'lastmod' => (int)$r['modified'], 'priority' => $r['type'] === 'post' ? '0.8' : '0.6'];
        }
        $metas = $db->fetchAll($db->select()->from('table.metas')->where('type = ? OR type = ?', 'category', 'tag')->where('count > 0'));
        $cats = null;
        foreach ($metas as $m) {
            $slug = urlencode((string)$m['slug']);
            $params = ['slug' => $slug, 'mid' => (int)$m['mid']];
            if ($m['type'] === 'category') {
                // 子分类的 directory 需包含父级路径
                $cats = $cats ?? \Widget\Metas\Category\Rows::alloc();
                $dir = $cats->getAllParentsSlug((int)$m['mid']);
                $dir[] = (string)$m['slug'];
                $params['directory'] = implode('/', array_map('urlencode', $dir));
            }
            $urls[] = ['loc' => \Typecho\Router::url($m['type'] === 'category' ? 'category' : 'tag', $params, (string)$this->options->index), 'lastmod' => null, 'priority' => '0.4'];
        }
        $this->response->setContentType('application/xml');
        echo Sitemap::xml($urls);
    }
}
