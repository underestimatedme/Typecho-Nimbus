<?php
namespace Nimbus;

/**
 * 教程（系列文章）：把某些分类标记为教程，分类下的文章按「章节」顺序排列。
 * 设置「首页 → 教程分类」每行一个：分类缩略名，或「缩略名|封面图 URL」。
 * 章节顺序：文章自定义字段 chapter（数字）优先，其次按发布时间从早到晚。
 */
final class Tutorial
{
    /** 解析设置。@return array<string,string> slug => cover */
    public static function parseConfig(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $p = array_map('trim', explode('|', (string)$line, 2));
            $slug = $p[0] ?? '';
            if ($slug === '' || !preg_match('/^[\w\x{4e00}-\x{9fff}.-]{1,100}$/u', $slug)) continue;
            $cover = $p[1] ?? '';
            $out[$slug] = preg_match('#^(https?://|/)[^\s"\'<>]+$#i', $cover) ? $cover : '';
        }
        return $out;
    }

    /** 章节排序：chapter 数字小的在前；没有 chapter 的排在后面，按发布时间。 */
    public static function sortChapters(array $rows): array
    {
        usort($rows, function ($a, $b) {
            $ca = $a['chapter'] ?? null; $cb = $b['chapter'] ?? null;
            if ($ca !== null && $cb !== null && $ca !== $cb) return $ca <=> $cb;
            if ($ca !== null && $cb === null) return -1;
            if ($ca === null && $cb !== null) return 1;
            return [(int)$a['created'], (int)$a['cid']] <=> [(int)$b['created'], (int)$b['cid']];
        });
        return array_values($rows);
    }

    /** @var array|null */
    private static $all = null;

    /** 全部教程（含章节列表），按设置顺序。 */
    public static function all($archive): array
    {
        if (self::$all !== null) return self::$all;
        $cfg = self::parseConfig(Options::list('TutorialCategories'));
        if (!$cfg) return self::$all = [];
        $db = \Typecho_Db::get();
        $metas = $db->fetchAll($db->select('mid', 'name', 'slug', 'description', 'type', 'count')->from('table.metas')
            ->where('type = ?', 'category')->where('slug IN ?', array_keys($cfg)));
        if (!$metas) return self::$all = [];
        $o = \Helper::options();
        $key = Cache::postsKey(['tut', $cfg, $metas, (string)$o->index]);
        $data = Cache::remember('tutorials', $key, function () use ($db, $archive, $cfg, $metas, $o) {
            $now = (int)$o->time ?: time();
            $bySlug = [];
            foreach ($metas as $m) $bySlug[(string)$m['slug']] = $m;
            $list = [];
            foreach ($cfg as $slug => $cover) {
                if (!isset($bySlug[$slug])) continue;
                $m = $bySlug[$slug];
                $rows = $db->fetchAll($db->select('table.contents.*')->from('table.contents')
                    ->join('table.relationships', 'table.relationships.cid = table.contents.cid')
                    ->where('table.relationships.mid = ?', (int)$m['mid'])
                    ->where('table.contents.type = ? AND table.contents.status = ?', 'post', 'publish')
                    ->where('table.contents.created < ?', $now)
                    ->where("table.contents.password IS NULL OR table.contents.password = ''"));
                if (!$rows) continue;
                $cids = array_map(function ($r) { return (int)$r['cid']; }, $rows);
                $fields = $db->fetchAll($db->select('cid', 'str_value', 'int_value')->from('table.fields')->where('name = ?', 'chapter')->where('cid IN ?', $cids));
                $chap = [];
                foreach ($fields as $f) {
                    $v = $f['int_value'] !== null && (int)$f['int_value'] !== 0 ? (int)$f['int_value'] : (is_numeric($f['str_value']) ? (float)$f['str_value'] : null);
                    if ($v !== null) $chap[(int)$f['cid']] = $v;
                }
                foreach ($rows as &$r) $r['chapter'] = $chap[(int)$r['cid']] ?? null;
                unset($r);
                $rows = self::sortChapters($rows);
                $chapters = []; $minutes = 0;
                foreach ($rows as $r) {
                    $f = $archive->filter($r);
                    $words = Post::wordCount(Shortcode::strip((string)$r['text']));
                    $min = Post::readingTime($words);
                    $minutes += $min;
                    $chapters[] = ['cid' => (int)$r['cid'], 'title' => View::t((string)$r['title']), 'url' => (string)$f['permalink'], 'created' => (int)$r['created'], 'minutes' => $min, 'thumb' => Post::rowThumb($r), 'excerpt' => Post::excerptFrom(null, Shortcode::strip((string)$r['text']), 60)];
                }
                $meta = \Typecho_Widget::widget('Widget_Abstract_Metas')->filter($m);
                $list[] = [
                    'slug' => $slug,
                    'name' => (string)$m['name'],
                    'description' => trim(strip_tags((string)$m['description'])),
                    'url' => (string)$meta['permalink'],
                    'cover' => $cover !== '' ? $cover : Post::rowThumb($rows[0]),
                    'minutes' => $minutes,
                    'updated' => max(array_map(function ($r) { return (int)$r['modified']; }, $rows)),
                    'chapters' => $chapters,
                ];
            }
            return $list;
        });
        return self::$all = is_array($data) ? $data : [];
    }

    public static function get($archive, string $slug): ?array
    {
        foreach (self::all($archive) as $t) if ($t['slug'] === $slug) return $t;
        return null;
    }

    /** 文章所在的教程与章节序号。@return array{tutorial:array,index:int}|null */
    public static function forPost($archive, int $cid): ?array
    {
        foreach (self::all($archive) as $t) {
            foreach ($t['chapters'] as $i => $c) if ($c['cid'] === $cid) return ['tutorial' => $t, 'index' => $i];
        }
        return null;
    }

    /** 给前端进度脚本用的数据。 */
    public static function clientData(array $t): string
    {
        return (string)json_encode(['slug' => $t['slug'], 'ch' => array_map(function ($c) { return [$c['cid'], $c['url']]; }, $t['chapters'])], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}
