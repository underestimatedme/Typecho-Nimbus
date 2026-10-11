<?php
namespace TypechoPlugin\NimbusKit;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 对主题公开的静态门面。主题通过 Nimbus\Kit 调用（类名 NimbusKit_Api）。
 * 所有方法在对应功能关闭时返回 null，主题据此降级。
 */
final class Api
{
    const VERSION = '1.1.0';

    public static function features(): array
    {
        return array_values(array_intersect(Config::FEATURES, (array)Config::get('features')));
    }

    public static function views(int $cid): ?int
    {
        return Config::feature('views') ? Store::stat('post', $cid)['views'] : null;
    }

    public static function likes(int $cid): ?int
    {
        return Config::feature('likes') ? Store::stat('post', $cid)['likes'] : null;
    }

    /** 列表页预取统计，减少查询次数。 */
    public static function prefetch(array $cids): void
    {
        if (Config::feature('views') || Config::feature('likes')) Store::prefetch('post', $cids);
    }

    /** @return int[]|null 按浏览量排序的 cid */
    public static function hotPosts(int $limit): ?array
    {
        if (!Config::feature('views')) return null;
        $rows = Store::topPosts('views', $limit);
        if (empty($rows)) return null; // 还没有浏览数据：让主题回落到评论数排序
        return array_map(function ($r) { return (int)$r['cid']; }, $rows);
    }

    public static function links(): ?array
    {
        $links = Store::links();
        return empty($links) ? null : array_map(function ($l) {
            return ['name' => $l['name'], 'url' => $l['url'], 'avatar' => $l['avatar'], 'desc' => $l['desc']];
        }, $links);
    }

    /** @return array{items:array,total:int} */
    public static function moments(int $page, int $size): ?array
    {
        return Store::moments($page, $size);
    }

    public static function albums(): ?array
    {
        return Store::albums();
    }

    /** @return array<int,array{name:string,items:array}> */
    public static function bookmarks(): array
    {
        $groups = \TypechoPlugin\NimbusKit\Lib\Text::groupBookmarks(Store::bookmarks());
        foreach ($groups as &$g) if ($g['name'] === '未分组') $g['name'] = Lang::t('未分组');
        unset($g);
        return $groups;
    }

    public static function projects(): array
    {
        return Store::projects();
    }

    /** 统计看板数据。 */
    public static function stats(): array
    {
        return ['daily' => Store::daily(30), 'totals' => Store::totals(), 'top' => Store::topPosts('views', 10)];
    }

    /** @var \TypechoPlugin\NimbusKit\Lib\Ip2Region|false|null */
    private static $ipdb = null;

    public static function regionFile(): string
    {
        return __DIR__ . '/data/ip2region.xdb';
    }

    /** IP 属地展示名（内网为「本地」）；功能关闭或数据文件缺失返回 null。 */
    public static function region(string $ip): ?string
    {
        if (Config::get('ipRegion') !== '1') return null;
        if (self::$ipdb === null) {
            try { self::$ipdb = new \TypechoPlugin\NimbusKit\Lib\Ip2Region(self::regionFile()); } catch (\Throwable $e) { self::$ipdb = false; }
        }
        if (self::$ipdb === false) return null;
        if (\TypechoPlugin\NimbusKit\Lib\Ip2Region::isLocal($ip)) return Lang::t('本地');
        $label = \TypechoPlugin\NimbusKit\Lib\Ip2Region::label(self::$ipdb->search($ip));
        return $label === '' ? '' : Lang::t($label);
    }

    const COMMENTED = 'nimbus_cm';

    /** 本浏览器提交过的评论 coid（签名 cookie，防伪造）；用于「回复可见」。 */
    public static function commentedIds(): array
    {
        $v = isset($_COOKIE[self::COMMENTED]) ? (string)$_COOKIE[self::COMMENTED] : '';
        if ($v === '') return [];
        $d = \TypechoPlugin\NimbusKit\Lib\Token::verify((string)\Helper::options()->secret, 'cm', $v, time());
        return $d && isset($d['c']) && is_array($d['c']) ? array_values(array_filter(array_map('intval', $d['c']))) : [];
    }

    public static function rememberComment(int $coid): void
    {
        if ($coid <= 0) return;
        $ids = \TypechoPlugin\NimbusKit\Lib\Token::addComment(self::commentedIds(), $coid);
        $val = \TypechoPlugin\NimbusKit\Lib\Token::sign((string)\Helper::options()->secret, 'cm', ['c' => $ids, 'exp' => time() + 31536000]);
        $_COOKIE[self::COMMENTED] = $val;
        setcookie(self::COMMENTED, $val, ['expires' => time() + 31536000, 'path' => \Typecho\Cookie::getPath() ?: '/', 'secure' => stripos((string)\Helper::options()->siteUrl, 'https://') === 0, 'httponly' => true, 'samesite' => 'Lax']);
    }

    /**
     * Typecho 自带搜索：排除「只在回复可见内容里命中」的文章。
     * 挂在 Widget_Archive::handleInit 上（searchHandle 钩子会被 Typecho 误判为「插件已接管搜索」，导致页面出错）。
     * @param mixed $archive
     * @param \Typecho\Db\Query $select
     */
    public static function searchHandle($archive, $select): void
    {
        if (!in_array((string)\Typecho\Router::$current, ['search', 'search_page'], true)) return;
        $kw = self::searchKeywords();
        if ($kw === '') return;
        $db = \Typecho\Db::get();
        $op = $db->getAdapter()->getDriver() == 'pgsql' ? 'ILIKE' : 'LIKE';
        $q = '%' . str_replace(' ', '%', $kw) . '%';
        $rows = $db->fetchAll($db->select('cid', 'title', 'text')->from('table.contents')
            ->where('type = ?', 'post')->where("text {$op} ?", '%[hide%')->where("title {$op} ? OR text {$op} ?", $q, $q));
        $drop = [];
        foreach ($rows as $r) {
            if (\TypechoPlugin\NimbusKit\Lib\Text::likeMatch((string)$r['title'], $kw)) continue;
            if (\TypechoPlugin\NimbusKit\Lib\Text::likeMatch(\TypechoPlugin\NimbusKit\Lib\Text::dropHidden((string)$r['text']), $kw)) continue;
            $drop[] = (int)$r['cid'];
        }
        // 查询构造器会把 NOT 当列名加引号，改为逐条排除
        foreach ($drop as $cid) $select->where('table.contents.cid <> ?', $cid);
    }

    private static function searchKeywords(): string
    {
        $req = \Typecho\Request::getInstance();
        if (preg_match('#/search/([^/]+)#', (string)$req->getPathInfo(), $m)) return trim(urldecode($m[1]));
        return trim((string)$req->get('s', ''));
    }

    /** GitHub 登录状态：未配置返回 null。 */
    public static function github(string $back = ''): ?array
    {
        if (!GitHubAuth::enabled()) return null;
        return [
            'user' => ($u = GitHubAuth::current()) ? $u + ['formAuthor' => GitHubAuth::formAuthor($u)] : null,
            'only' => Config::get('githubOnly') === '1',
            'login' => GitHubAuth::endpoint('github-login', $back),
            'logout' => GitHubAuth::endpoint('github-logout', $back),
        ];
    }

    /** 浏览量排行（最多 200 篇）。 */
    public static function topViewed(int $limit): array
    {
        return Store::topPosts('views', max(1, min(200, $limit)));
    }

    public static function likesFeature(): bool
    {
        return Config::feature('likes');
    }
}
