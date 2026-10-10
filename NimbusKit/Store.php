<?php
namespace TypechoPlugin\NimbusKit;

use TypechoPlugin\NimbusKit\Lib\Schema;
use TypechoPlugin\NimbusKit\Lib\Text;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 数据访问：统计、点赞记录、说说、友链、相册。
 */
final class Store
{
    /** @var array<string,array{views:int,likes:int}> */
    private static $stats = [];

    private static function db(): \Typecho\Db
    {
        return \Typecho\Db::get();
    }

    /** 当前时间：定时发布的文章（created 在未来）对外不可见。 */
    private static function now(): int
    {
        return (int)\Helper::options()->time;
    }

    private static function likeOp(): string
    {
        return Schema::normalizeAdapter(self::db()->getAdapterName()) === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    public static function install(): void
    {
        $db = self::db();
        foreach (Schema::forAdapter($db->getAdapterName(), $db->getPrefix()) as $sql) {
            $db->query($sql, \Typecho\Db::WRITE);
        }
    }

    public static function uninstall(): void
    {
        $db = self::db();
        foreach (Schema::dropStatements($db->getPrefix()) as $sql) {
            $db->query($sql, \Typecho\Db::WRITE);
        }
    }

    // ---------- 统计 ----------

    public static function stat(string $type, int $cid): array
    {
        $key = $type . ':' . $cid;
        if (isset(self::$stats[$key])) return self::$stats[$key];
        $db = self::db();
        $row = $db->fetchRow($db->select('views', 'likes')->from('table.nimbus_stats')->where('type = ? AND cid = ?', $type, $cid)->limit(1));
        return self::$stats[$key] = ['views' => (int)($row['views'] ?? 0), 'likes' => (int)($row['likes'] ?? 0)];
    }

    /** 批量预取，减少列表页查询。 */
    public static function prefetch(string $type, array $cids): void
    {
        $cids = array_values(array_unique(array_filter(array_map('intval', $cids))));
        $missing = array_filter($cids, function ($c) use ($type) { return !isset(self::$stats[$type . ':' . $c]); });
        if (empty($missing)) return;
        $db = self::db();
        foreach ($missing as $c) self::$stats[$type . ':' . $c] = ['views' => 0, 'likes' => 0];
        $rows = $db->fetchAll($db->select('cid', 'views', 'likes')->from('table.nimbus_stats')->where('type = ?', $type)->where('cid IN ?', array_values($missing)));
        foreach ($rows as $r) self::$stats[$type . ':' . (int)$r['cid']] = ['views' => (int)$r['views'], 'likes' => (int)$r['likes']];
    }

    private static function ensureStat(string $type, int $cid): void
    {
        $db = self::db();
        $exists = $db->fetchRow($db->select('cid')->from('table.nimbus_stats')->where('type = ? AND cid = ?', $type, $cid)->limit(1));
        if (!$exists) {
            try {
                $db->query($db->insert('table.nimbus_stats')->rows(['type' => $type, 'cid' => $cid, 'views' => 0, 'likes' => 0]));
            } catch (\Typecho\Db\Exception $e) {
                // 并发插入：忽略
            }
        }
    }

    public static function incrementView(string $type, int $cid): int
    {
        self::ensureStat($type, $cid);
        $db = self::db();
        $db->query($db->update('table.nimbus_stats')->expression('views', 'views + 1')->where('type = ? AND cid = ?', $type, $cid));
        unset(self::$stats[$type . ':' . $cid]);
        self::incrementDaily();
        return self::stat($type, $cid)['views'];
    }

    public static function hasLiked(string $type, int $cid, string $visitor): bool
    {
        $db = self::db();
        return (bool)$db->fetchRow($db->select('id')->from('table.nimbus_like_log')->where('type = ? AND cid = ? AND visitor = ?', $type, $cid, $visitor)->limit(1));
    }

    /** @return array{likes:int,liked:bool} */
    public static function like(string $type, int $cid, string $visitor): array
    {
        if (self::hasLiked($type, $cid, $visitor)) return ['likes' => self::stat($type, $cid)['likes'], 'liked' => true];
        $db = self::db();
        try {
            $db->query($db->insert('table.nimbus_like_log')->rows(['type' => $type, 'cid' => $cid, 'visitor' => $visitor, 'created' => time()]));
        } catch (\Typecho\Db\Exception $e) {
            return ['likes' => self::stat($type, $cid)['likes'], 'liked' => true];
        }
        self::ensureStat($type, $cid);
        $db->query($db->update('table.nimbus_stats')->expression('likes', 'likes + 1')->where('type = ? AND cid = ?', $type, $cid));
        unset(self::$stats[$type . ':' . $cid]);
        return ['likes' => self::stat($type, $cid)['likes'], 'liked' => true];
    }

    /** 按 views / likes 排序的文章 cid（仅已发布、无密码）。 */
    public static function topPosts(string $field, int $limit): array
    {
        $field = $field === 'likes' ? 'likes' : 'views';
        $db = self::db();
        $rows = $db->fetchAll($db->select('table.nimbus_stats.cid', 'table.nimbus_stats.views', 'table.nimbus_stats.likes', 'table.contents.title')
            ->from('table.nimbus_stats')
            ->join('table.contents', 'table.contents.cid = table.nimbus_stats.cid')
            ->where('table.nimbus_stats.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->where('table.contents.created < ?', self::now())
            ->where('table.nimbus_stats.' . $field . ' > 0')
            ->order('table.nimbus_stats.' . $field, \Typecho\Db::SORT_DESC)
            ->limit($limit));
        return $rows;
    }

    /** 文章是否可公开访问（已发布、文章类型、无密码）。 */
    public static function publicPost(int $cid): bool
    {
        $db = self::db();
        $row = $db->fetchRow($db->select('cid', 'password')->from('table.contents')->where('cid = ? AND status = ? AND (type = ? OR type = ?)', $cid, 'publish', 'post', 'page')->where('created < ?', self::now())->limit(1));
        return $row && ($row['password'] === null || $row['password'] === '');
    }

    /** 每日总浏览量（统计看板用）。 */
    private static function incrementDaily(): void
    {
        $db = self::db();
        $day = Text::dayKey(self::now() ?: time());
        try {
            $n = $db->query($db->update('table.nimbus_daily')->expression('views', 'views + 1')->where('day = ?', $day));
            if (!$n) $db->query($db->insert('table.nimbus_daily')->rows(['day' => $day, 'views' => 1]));
        } catch (\Typecho\Db\Exception $e) {
            // 旧版本升级后还没重新启用插件建表：忽略
        }
    }

    /** 近 $days 天每日浏览量（含 0）。 */
    public static function daily(int $days): array
    {
        $db = self::db();
        $end = self::now() ?: time();
        $keys = [];
        for ($i = $days - 1; $i >= 0; $i--) $keys[Text::dayKey($end - $i * 86400)] = 0;
        try {
            $rows = $db->fetchAll($db->select('day', 'views')->from('table.nimbus_daily')->where('day >= ?', (int)array_key_first($keys)));
            foreach ($rows as $r) if (isset($keys[(int)$r['day']])) $keys[(int)$r['day']] = (int)$r['views'];
        } catch (\Typecho\Db\Exception $e) {
        }
        $out = [];
        foreach ($keys as $k => $v) $out[] = ['day' => $k, 'views' => $v];
        return $out;
    }

    public static function totals(): array
    {
        $db = self::db();
        $r = $db->fetchRow($db->select(['SUM(views)' => 'v', 'SUM(likes)' => 'l'])->from('table.nimbus_stats')->where('type = ?', 'post'));
        return ['views' => (int)($r['v'] ?? 0), 'likes' => (int)($r['l'] ?? 0)];
    }

    // ---------- 收藏夹 ----------

    public static function bookmarks(bool $all = false): array
    {
        $db = self::db();
        $q = $db->select()->from('table.nimbus_bookmarks');
        if (!$all) $q->where('status = ?', 'publish');
        $rows = $db->fetchAll($q->order('sort', \Typecho\Db::SORT_ASC)->order('id', \Typecho\Db::SORT_ASC));
        return array_map(function ($r) {
            return ['id' => (int)$r['id'], 'group' => (string)$r['group_name'], 'name' => (string)$r['name'], 'url' => (string)$r['url'], 'icon' => (string)$r['icon'], 'desc' => (string)$r['description'], 'sort' => (int)$r['sort'], 'status' => (string)$r['status']];
        }, $rows);
    }

    public static function bookmark(int $id): ?array
    {
        foreach (self::bookmarks(true) as $b) if ($b['id'] === $id) return $b;
        return null;
    }

    public static function saveBookmark(?int $id, array $d): int
    {
        $db = self::db();
        $row = ['group_name' => (string)$d['group'], 'name' => (string)$d['name'], 'url' => (string)$d['url'], 'icon' => (string)$d['icon'], 'description' => (string)$d['desc'], 'sort' => (int)$d['sort'], 'status' => ($d['status'] ?? '') === 'hidden' ? 'hidden' : 'publish'];
        if ($id) { $db->query($db->update('table.nimbus_bookmarks')->rows($row)->where('id = ?', $id)); return $id; }
        $row['created'] = time();
        return (int)$db->query($db->insert('table.nimbus_bookmarks')->rows($row));
    }

    public static function deleteBookmark(int $id): void
    {
        $db = self::db();
        $db->query($db->delete('table.nimbus_bookmarks')->where('id = ?', $id));
    }

    // ---------- 项目 ----------

    public static function projects(bool $all = false): array
    {
        $db = self::db();
        $q = $db->select()->from('table.nimbus_projects');
        if (!$all) $q->where('status = ?', 'publish');
        $rows = $db->fetchAll($q->order('sort', \Typecho\Db::SORT_ASC)->order('id', \Typecho\Db::SORT_DESC));
        return array_map(function ($r) {
            return ['id' => (int)$r['id'], 'name' => (string)$r['name'], 'description' => (string)$r['description'], 'cover' => (string)$r['cover'], 'url' => (string)$r['url'], 'repo' => (string)$r['repo'], 'tags' => Text::tags((string)$r['tags']), 'sort' => (int)$r['sort'], 'status' => (string)$r['status']];
        }, $rows);
    }

    public static function project(int $id): ?array
    {
        foreach (self::projects(true) as $p) if ($p['id'] === $id) return $p;
        return null;
    }

    public static function saveProject(?int $id, array $d): int
    {
        $db = self::db();
        $row = ['name' => (string)$d['name'], 'description' => (string)$d['description'], 'cover' => (string)$d['cover'], 'url' => (string)$d['url'], 'repo' => (string)$d['repo'], 'tags' => implode(',', Text::tags((string)$d['tags'])), 'sort' => (int)$d['sort'], 'status' => ($d['status'] ?? '') === 'hidden' ? 'hidden' : 'publish'];
        if ($id) { $db->query($db->update('table.nimbus_projects')->rows($row)->where('id = ?', $id)); return $id; }
        $row['created'] = time();
        return (int)$db->query($db->insert('table.nimbus_projects')->rows($row));
    }

    public static function deleteProject(int $id): void
    {
        $db = self::db();
        $db->query($db->delete('table.nimbus_projects')->where('id = ?', $id));
    }

    // ---------- 搜索 ----------

    public static function search(string $q, int $limit = 8): array
    {
        $db = self::db();
        $like = Text::likePattern($q);
        $base = function () use ($db) {
            return $db->select()->from('table.contents')
                ->where('status = ? AND type = ?', 'publish', 'post')
                ->where("password IS NULL OR password = ''")
                ->where('created < ?', self::now())
                ->order('created', \Typecho\Db::SORT_DESC);
        };
        $op = self::likeOp();
        $rows = [];
        $seen = [];
        // 标题命中优先，其次正文；SQL 宽松匹配后在 PHP 里精确复核
        foreach ([['title', 'title'], ['text', 'text']] as $pair) {
            list($col, $field) = $pair;
            foreach ($db->fetchAll($base()->where($col . ' ' . $op . ' ?', $like)->limit($limit * 5)) as $r) {
                $cid = (int)$r['cid'];
                if (isset($seen[$cid])) continue;
                $hay = $field === 'title' ? htmlspecialchars_decode((string)$r['title'], ENT_QUOTES) : Text::plain((string)$r['text']);
                if (!Text::contains($hay, $q)) continue;
                $seen[$cid] = true;
                $rows[] = $r;
                if (count($rows) >= $limit) break 2;
            }
        }
        $w = \Widget\Base\Contents::alloc();
        $out = [];
        foreach ($rows as $r) {
            $f = $w->filter($r);
            $out[] = [
                'title' => htmlspecialchars_decode((string)$f['title'], ENT_QUOTES),
                'url' => $f['permalink'],
                'excerpt' => Text::excerpt((string)$r['text'], $q, 70),
                'date' => date('Y-m-d', (int)$r['created']),
            ];
        }
        return $out;
    }

    // ---------- 说说 ----------

    public static function moments(int $page, int $size, bool $all = false): array
    {
        $db = self::db();
        $q = $db->select()->from('table.nimbus_moments');
        if (!$all) $q->where('status = ?', 'publish');
        $q->order('created', \Typecho\Db::SORT_DESC)->page(max(1, $page), max(1, $size));
        $rows = $db->fetchAll($q);
        $count = $db->select(['COUNT(id)' => 'n'])->from('table.nimbus_moments');
        if (!$all) $count->where('status = ?', 'publish');
        $total = (int)($db->fetchRow($count)['n'] ?? 0);
        self::prefetch('moment', array_column($rows, 'id'));
        $items = [];
        foreach ($rows as $r) $items[] = self::momentRow($r);
        return ['items' => $items, 'total' => $total];
    }

    public static function moment(int $id): ?array
    {
        $db = self::db();
        $r = $db->fetchRow($db->select()->from('table.nimbus_moments')->where('id = ?', $id)->limit(1));
        return $r ? self::momentRow($r) : null;
    }

    private static function momentRow(array $r): array
    {
        $images = json_decode((string)$r['images'], true);
        return [
            'id' => (int)$r['id'],
            'raw' => (string)$r['content'],
            'content' => Text::moment((string)$r['content']),
            'images' => is_array($images) ? array_values(array_filter($images, 'is_string')) : [],
            'created' => (int)$r['created'],
            'status' => (string)$r['status'],
            'likes' => self::stat('moment', (int)$r['id'])['likes'],
        ];
    }

    public static function publicMoment(int $id): bool
    {
        $db = self::db();
        return (bool)$db->fetchRow($db->select('id')->from('table.nimbus_moments')->where('id = ? AND status = ?', $id, 'publish')->limit(1));
    }

    public static function saveMoment(?int $id, string $content, array $images, string $status, ?int $created = null): int
    {
        $db = self::db();
        $row = ['content' => $content, 'images' => json_encode(array_values($images), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'status' => $status === 'hidden' ? 'hidden' : 'publish'];
        if ($id) {
            $db->query($db->update('table.nimbus_moments')->rows($row)->where('id = ?', $id));
            return $id;
        }
        $row['created'] = $created ?: time();
        return (int)$db->query($db->insert('table.nimbus_moments')->rows($row));
    }

    public static function deleteMoment(int $id): void
    {
        $db = self::db();
        $db->query($db->delete('table.nimbus_moments')->where('id = ?', $id));
        $db->query($db->delete('table.nimbus_stats')->where('type = ? AND cid = ?', 'moment', $id));
        $db->query($db->delete('table.nimbus_like_log')->where('type = ? AND cid = ?', 'moment', $id));
    }

    // ---------- 友链 ----------

    public static function links(bool $all = false): array
    {
        $db = self::db();
        $q = $db->select()->from('table.nimbus_links');
        if (!$all) $q->where('status = ?', 'publish');
        $rows = $db->fetchAll($q->order('sort', \Typecho\Db::SORT_ASC)->order('id', \Typecho\Db::SORT_ASC));
        return array_map(function ($r) {
            return ['id' => (int)$r['id'], 'name' => (string)$r['name'], 'url' => (string)$r['url'], 'avatar' => (string)$r['avatar'], 'desc' => (string)$r['description'], 'sort' => (int)$r['sort'], 'status' => (string)$r['status']];
        }, $rows);
    }

    public static function link(int $id): ?array
    {
        foreach (self::links(true) as $l) if ($l['id'] === $id) return $l;
        return null;
    }

    public static function saveLink(?int $id, array $data): int
    {
        $db = self::db();
        $row = [
            'name' => (string)$data['name'], 'url' => (string)$data['url'], 'avatar' => (string)$data['avatar'],
            'description' => (string)$data['desc'], 'sort' => (int)$data['sort'], 'status' => ($data['status'] ?? '') === 'hidden' ? 'hidden' : 'publish',
        ];
        if ($id) {
            $db->query($db->update('table.nimbus_links')->rows($row)->where('id = ?', $id));
            return $id;
        }
        $row['created'] = time();
        return (int)$db->query($db->insert('table.nimbus_links')->rows($row));
    }

    public static function deleteLink(int $id): void
    {
        $db = self::db();
        $db->query($db->delete('table.nimbus_links')->where('id = ?', $id));
    }

    // ---------- 相册 ----------

    public static function albums(): array
    {
        $db = self::db();
        $rows = $db->fetchAll($db->select()->from('table.nimbus_albums')->order('sort', \Typecho\Db::SORT_ASC)->order('id', \Typecho\Db::SORT_DESC));
        return array_map([__CLASS__, 'albumRow'], $rows);
    }

    public static function album(int $id): ?array
    {
        $db = self::db();
        $r = $db->fetchRow($db->select()->from('table.nimbus_albums')->where('id = ?', $id)->limit(1));
        return $r ? self::albumRow($r) : null;
    }

    private static function albumRow(array $r): array
    {
        $photos = json_decode((string)$r['photos'], true);
        $photos = is_array($photos) ? array_values(array_filter($photos, function ($p) { return is_array($p) && !empty($p['url']); })) : [];
        $cover = (string)$r['cover'];
        if ($cover === '' && !empty($photos)) $cover = $photos[0]['url'];
        return ['id' => (int)$r['id'], 'title' => (string)$r['title'], 'cover' => $cover, 'description' => (string)$r['description'], 'photos' => $photos, 'sort' => (int)$r['sort'], 'created' => (int)$r['created']];
    }

    public static function saveAlbum(?int $id, array $data): int
    {
        $db = self::db();
        $row = [
            'title' => (string)$data['title'], 'cover' => (string)$data['cover'], 'description' => (string)$data['description'],
            'photos' => json_encode(array_values($data['photos']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'sort' => (int)$data['sort'],
        ];
        if ($id) {
            $db->query($db->update('table.nimbus_albums')->rows($row)->where('id = ?', $id));
            return $id;
        }
        $row['created'] = time();
        return (int)$db->query($db->insert('table.nimbus_albums')->rows($row));
    }

    public static function deleteAlbum(int $id): void
    {
        $db = self::db();
        $db->query($db->delete('table.nimbus_albums')->where('id = ?', $id));
    }
}
