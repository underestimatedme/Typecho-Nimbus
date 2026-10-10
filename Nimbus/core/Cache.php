<?php
namespace Nimbus;

/**
 * 轻量结果缓存：存在 options 表的 nimbus:cache:{name} 行（JSON），以「文章指纹」作键，
 * 文章增删改、评论数变化、跨天或相关设置变化时自动失效。用于统计字数、时间线等大站点开销较大的计算。
 */
final class Cache
{
    /** @var array<string,string> */
    private static $keys = [];

    /** 已发布文章的指纹：数量、最后修改、评论总数、当天日期，加上调用方给的额外因子。 */
    public static function postsKey(array $extra = []): string
    {
        if (!isset(self::$keys['posts'])) {
            $db = \Typecho_Db::get();
            $now = (int)\Helper::options()->time ?: time();
            $r = $db->fetchRow($db->select(['COUNT(cid)' => 'n', 'MAX(modified)' => 'm', 'SUM(commentsNum)' => 'c'])->from('table.contents')
                ->where('type = ? AND status = ?', 'post', 'publish')->where('created < ?', $now));
            self::$keys['posts'] = (int)($r['n'] ?? 0) . '|' . (int)($r['m'] ?? 0) . '|' . (int)($r['c'] ?? 0) . '|' . View::date('Ymd', $now);
        }
        return md5(self::$keys['posts'] . '|' . json_encode($extra));
    }

    const CHUNK = 60000; // MySQL 的 options.value 为 TEXT（64 KB），按块存储

    /** @return mixed */
    public static function remember(string $name, string $key, callable $build)
    {
        $base = 'nimbus:cache:' . $name;
        try {
            $db = \Typecho_Db::get();
            $meta = $db->fetchRow($db->select('value')->from('table.options')->where('name = ? AND user = 0', $base)->limit(1));
            $m = $meta ? json_decode((string)$meta['value'], true) : null;
            if (is_array($m) && ($m['k'] ?? null) === $key && (int)($m['n'] ?? 0) > 0) {
                $names = [];
                for ($i = 0; $i < (int)$m['n']; $i++) $names[] = $base . ':' . $i;
                $rows = $db->fetchAll($db->select('name', 'value')->from('table.options')->where('name IN ?', $names)->where('user = 0'));
                $parts = [];
                foreach ($rows as $r) $parts[(string)$r['name']] = (string)$r['value'];
                if (count($parts) === count($names)) {
                    $b64 = '';
                    foreach ($names as $n) $b64 .= $parts[$n];
                    $raw = base64_decode($b64, true);
                    if ($raw !== false && md5($raw) === ($m['h'] ?? '')) {
                        $v = json_decode($raw, true);
                        if (is_array($v) && array_key_exists('d', $v)) return $v['d'];
                    }
                }
            }
        } catch (\Throwable $e) {
            return $build();
        }
        $data = $build();
        try {
            $raw = (string)json_encode(['d' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $chunks = str_split(base64_encode($raw), self::CHUNK);
            if (count($chunks) <= 200) {
                $db->query($db->delete('table.options')->where('name LIKE ? AND user = 0', $base . ':%'));
                foreach ($chunks as $i => $c) $db->query($db->insert('table.options')->rows(['name' => $base . ':' . $i, 'user' => 0, 'value' => $c]));
                $mv = (string)json_encode(['k' => $key, 'n' => count($chunks), 'h' => md5($raw)]);
                if ($meta) $db->query($db->update('table.options')->rows(['value' => $mv])->where('name = ? AND user = 0', $base));
                else $db->query($db->insert('table.options')->rows(['name' => $base, 'user' => 0, 'value' => $mv]));
            }
        } catch (\Throwable $e) {
            // 写缓存失败不影响页面
        }
        return $data;
    }
}
