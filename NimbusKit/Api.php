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
        return \TypechoPlugin\NimbusKit\Lib\Text::groupBookmarks(Store::bookmarks());
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
        if (\TypechoPlugin\NimbusKit\Lib\Ip2Region::isLocal($ip)) return '本地';
        return \TypechoPlugin\NimbusKit\Lib\Ip2Region::label(self::$ipdb->search($ip));
    }

    /** GitHub 登录状态：未配置返回 null。 */
    public static function github(string $back = ''): ?array
    {
        if (!GitHubAuth::enabled()) return null;
        return [
            'user' => GitHubAuth::current(),
            'only' => Config::get('githubOnly') === '1',
            'login' => GitHubAuth::endpoint('github-login', $back),
            'logout' => GitHubAuth::endpoint('github-logout', $back),
        ];
    }

    public static function likesFeature(): bool
    {
        return Config::feature('likes');
    }
}
