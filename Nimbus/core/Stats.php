<?php
namespace Nimbus;

/**
 * 统计看板数据：纯函数聚合（可单测）+ 从 Typecho 数据库采集。
 * 时间均按站点时区偏移（秒）计算，避免服务器时区影响。
 */
final class Stats
{
    /** 近 N 个月每月数量。@return array{labels:string[],values:int[]} */
    public static function monthly(array $timestamps, int $now, int $months = 12, int $tz = 0): array
    {
        $y = (int)gmdate('Y', $now + $tz);
        $m = (int)gmdate('n', $now + $tz);
        $keys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $mm = $m - $i; $yy = $y;
            while ($mm <= 0) { $mm += 12; $yy--; }
            $keys[sprintf('%04d-%02d', $yy, $mm)] = 0;
        }
        foreach ($timestamps as $t) {
            $k = gmdate('Y-m', (int)$t + $tz);
            if (isset($keys[$k])) $keys[$k]++;
        }
        return ['labels' => array_keys($keys), 'values' => array_values($keys)];
    }

    /**
     * 发文热力图：最近 $weeks 周，每周一列（周一开始），今天之后的格子标记 future。
     * @return array{weeks:array,max:int,total:int,months:array}
     */
    public static function heatmap(array $timestamps, int $now, int $weeks = 53, int $tz = 0): array
    {
        $today = intdiv($now + $tz, 86400);              // 本地日序号
        $dow = ((int)gmdate('N', $now + $tz)) - 1;       // 0 = 周一
        $start = $today - $dow - ($weeks - 1) * 7;
        $count = [];
        foreach ($timestamps as $t) {
            $d = intdiv((int)$t + $tz, 86400);
            if ($d >= $start && $d <= $today) $count[$d] = ($count[$d] ?? 0) + 1;
        }
        $grid = []; $months = []; $lastMonth = null; $max = 0; $total = 0;
        for ($w = 0; $w < $weeks; $w++) {
            $col = [];
            for ($i = 0; $i < 7; $i++) {
                $d = $start + $w * 7 + $i;
                $n = $count[$d] ?? 0;
                $max = max($max, $n); $total += $n;
                $col[] = ['date' => gmdate('Y-m-d', $d * 86400), 'n' => $n, 'future' => $d > $today];
            }
            $mon = gmdate('n', ($start + $w * 7) * 86400);
            if ($mon !== $lastMonth) { $months[] = [$w, (int)$mon]; $lastMonth = $mon; }
            $grid[] = $col;
        }
        return ['weeks' => $grid, 'max' => $max, 'total' => $total, 'months' => $months];
    }

    /** 热力等级 0–4。 */
    public static function level(int $n, int $max): int
    {
        if ($n <= 0 || $max <= 0) return 0;
        return min(4, (int)ceil($n / $max * 4));
    }

    /**
     * 前 $top 项保留，其余合并为「其他」。
     * @param array<int,array{name:string,count:int}> $items
     */
    public static function shares(array $items, int $top = 7): array
    {
        $items = array_values(array_filter($items, function ($i) { return (int)$i['count'] > 0; }));
        usort($items, function ($a, $b) { return (int)$b['count'] <=> (int)$a['count']; });
        if (count($items) <= $top + 1) return $items;
        $rest = array_slice($items, $top);
        $sum = array_sum(array_map(function ($i) { return (int)$i['count']; }, $rest));
        return array_merge(array_slice($items, 0, $top), [['name' => I18n::t('其他'), 'count' => $sum]]);
    }

    /** 字数：中日韩字符逐字计数，其余按单词计数。 */
    public static function words(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $cjk = preg_match_all('/[\x{2E80}-\x{9FFF}\x{F900}-\x{FAFF}\x{FF00}-\x{FFEF}]/u', $text);
        $rest = preg_replace('/[\x{2E80}-\x{9FFF}\x{F900}-\x{FAFF}\x{FF00}-\x{FFEF}]/u', ' ', $text);
        $latin = preg_match_all('/[A-Za-z0-9]+(?:[\'’-][A-Za-z0-9]+)*/u', (string)$rest);
        return (int)$cjk + (int)$latin;
    }

    /** 从数据库采集看板全部数据。 */
    public static function collect($archive): array
    {
        $db = \Typecho_Db::get();
        $opt = \Helper::options();
        $tz = (int)$opt->timezone;
        $now = (int)$opt->time ?: time();
        $posts = $db->fetchAll($db->select('cid', 'created', 'text')->from('table.contents')
            ->where('type = ? AND status = ?', 'post', 'publish')->where('created < ?', $now)
            ->where("password IS NULL OR password = ''"));
        $postTs = array_map(function ($r) { return (int)$r['created']; }, $posts);
        $words = 0;
        foreach ($posts as $p) $words += self::words(Shortcode::strip((string)$p['text']));
        $comments = $db->fetchAll($db->select('table.comments.created')->from('table.comments')
            ->join('table.contents', 'table.contents.cid = table.comments.cid')
            ->where('table.comments.status = ?', 'approved')->where('table.contents.status = ?', 'publish')
            ->where('table.comments.created > ?', $now - 400 * 86400));
        $commentTs = array_map(function ($r) { return (int)$r['created']; }, $comments);
        $metas = function (string $type, int $limit) use ($db) {
            return $db->fetchAll($db->select('mid', 'name', 'slug', 'count', 'type')->from('table.metas')->where('type = ?', $type)
                ->where('count > 0')->order('count', \Typecho_Db::SORT_DESC)->limit($limit));
        };
        $cats = array_map(function ($r) { return ['name' => (string)$r['name'], 'count' => (int)$r['count']]; }, $metas('category', 100));
        $tagRows = $metas('tag', 24);
        $tags = [];
        foreach ($tagRows as $r) {
            $f = \Typecho_Widget::widget('Widget_Abstract_Metas')->filter($r);
            $tags[] = ['name' => (string)$r['name'], 'count' => (int)$r['count'], 'url' => (string)$f['permalink']];
        }
        $since = (string)Options::get('SiteSince', '');
        $first = $postTs ? min($postTs) : 0;
        $start = ($since !== '' && ($t = strtotime($since)) !== false) ? $t : $first;
        $counts = Post::counts();

        $kit = Kit::stats();
        if (is_array($kit)) {
            $top = isset($kit['top']) && is_array($kit['top']) ? $kit['top'] : [];
            $views = [];
            foreach ($top as $r) $views[(int)$r['cid']] = (int)$r['views'];
            $kit['top'] = array_map(function ($p) use ($views) { return ['title' => View::t((string)$p['title']), 'url' => (string)$p['permalink'], 'views' => $views[(int)$p['cid']] ?? 0]; }, Post::byCids($archive, array_keys($views)));
        }
        return [
            'counts' => $counts + ['words' => $words, 'days' => $start ? max(0, (int)floor(($now - $start) / 86400)) : 0],
            'posts' => self::monthly($postTs, $now, 12, $tz),
            'comments' => self::monthly($commentTs, $now, 12, $tz),
            'categories' => self::shares($cats, 7),
            'tags' => $tags,
            'heatmap' => self::heatmap($postTs, $now, 53, $tz),
            'kit' => $kit,
        ];
    }
}
