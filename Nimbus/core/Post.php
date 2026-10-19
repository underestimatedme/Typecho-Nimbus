<?php
namespace Nimbus;

/**
 * 文章派生数据：缩略图、摘要、字数、阅读时长、上下篇、相关、置顶、热门。
 */
final class Post
{
    const BUILTIN_THUMBS = 6;
    /** @var array<string,mixed> 单次请求缓存 */
    private static $cache = [];

    /** 纯函数：缩略图选择。 */
    public static function thumbFrom(?string $field, string $html, array $pool, int $cid, string $builtinBase): string
    {
        $field = trim((string)$field);
        if ($field !== '') return $field;
        if ($html !== '') {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) return $m[1];
            if (preg_match('/!\[[^\]]*\]\(([^)\s]+)/', $html, $m)) return $m[1];
        }
        $pool = array_values(array_filter($pool, function ($u) { return is_string($u) && trim($u) !== ''; }));
        if (!empty($pool)) return trim($pool[$cid % count($pool)]);
        $n = ($cid % self::BUILTIN_THUMBS) + 1;
        return rtrim($builtinBase, '/') . '/thumb-' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '.svg';
    }

    /** @param mixed $post Widget_Abstract_Contents */
    public static function thumb($post): string
    {
        $cid = (int)$post->cid;
        $key = 'thumb:' . $cid;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $field = self::field($post, 'thumb') ?? self::field($post, 'imgst');
        if ($field !== null) return self::$cache[$key] = $field;
        $builtin = View::asset('img/thumbs');
        $builtin = preg_replace('/\?v=.*$/', '', $builtin);
        return self::$cache[$key] = self::thumbFrom(is_string($field) ? $field : null, (string)$post->text, Options::list('ThumbPool'), $cid, $builtin);
    }

    /** 文章是否有真实配图（自定义字段或正文图片），用于决定文章页头图。 */
    public static function hasOwnImage($post): bool
    {
        if (self::field($post, 'thumb') !== null || self::field($post, 'imgst') !== null) return true;
        $html = (string)$post->text;
        return (bool)preg_match('/<img[^>]+src=|!\[[^\]]*\]\(/i', $html);
    }

    /** 纯函数：摘要。 */
    public static function excerptFrom(?string $field, string $html, int $len): string
    {
        $field = trim((string)$field);
        if ($field !== '') return $field;
        $s = Shortcode::strip($html);
        $s = preg_replace('/<!--.*?-->/s', '', $s);
        $s = preg_replace('/<(pre|code|script|style)[^>]*>.*?<\/\1>/is', '', (string)$s);
        $s = strip_tags((string)$s);
        $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        // 去掉 Markdown 标记残留
        $s = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $s);
        $s = preg_replace('/[#>*`_~\[\]|]+/', '', (string)$s);
        $s = preg_replace('/\s+/u', ' ', (string)$s);
        // 中文字符之间的空白去除
        $s = preg_replace('/(?<=\p{Han})\s+(?=\p{Han})/u', '', (string)$s);
        $s = trim((string)$s);
        if (mb_strlen($s) > $len) return mb_substr($s, 0, $len) . '…';
        return $s;
    }

    public static function excerpt($post, ?int $len = null): string
    {
        $cid = (int)$post->cid;
        $len = $len ?? Options::int('ExcerptLength');
        $key = 'excerpt:' . $cid . ':' . $len;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $field = null;
        try { $field = $post->fields->summary; } catch (\Throwable $e) {}
        $html = (string)$post->content;
        return self::$cache[$key] = self::excerptFrom(is_string($field) ? $field : null, $html, $len);
    }

    public static function wordCount(string $html): int
    {
        $t = trim(strip_tags($html));
        $cjk = preg_match_all('/\p{Han}/u', $t);
        $latin = preg_match_all('/[A-Za-z0-9_]+/u', $t);
        return (int)$cjk + (int)$latin;
    }

    public static function readingTime(int $words): int
    {
        return max(1, (int)ceil($words / 400));
    }

    /** 纯函数：按 cid 列表顺序过滤行。 */
    public static function filterCids(array $rows, array $cids): array
    {
        $map = [];
        foreach ($rows as $r) $map[(int)$r['cid']] = $r;
        $out = [];
        foreach ($cids as $c) {
            $c = (int)$c;
            if (isset($map[$c])) $out[] = $map[$c];
        }
        return $out;
    }

    /**
     * 按 cid 列表取已发布文章，保持顺序。
     * @param mixed $archive Widget_Archive（用于 filter 生成 permalink）
     * @return array<int,array>
     */
    public static function byCids($archive, array $cids): array
    {
        $cids = array_values(array_unique(array_filter(array_map('intval', $cids))));
        if (empty($cids)) return [];
        $db = \Typecho_Db::get();
        $rows = $db->fetchAll($db->select()->from('table.contents')
            ->where('table.contents.cid IN ?', $cids)
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where("table.contents.password IS NULL OR table.contents.password = ''"));
        $rows = array_map(function ($r) use ($archive) { return $archive->filter($r); }, $rows);
        return self::filterCids($rows, $cids);
    }

    /** 上一篇 / 下一篇（同类型、已发布、非密码）。 */
    public static function adjacent($archive): array
    {
        $key = 'adjacent:' . (int)$archive->cid;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $db = \Typecho_Db::get();
        $base = function () use ($db, $archive) {
            return $db->select()->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', $archive->type)
                ->where("table.contents.password IS NULL OR table.contents.password = ''");
        };
        $prev = $db->fetchRow($base()->where('table.contents.created < ?', $archive->created)->order('table.contents.created', \Typecho_Db::SORT_DESC)->limit(1));
        $next = $db->fetchRow($base()->where('table.contents.created > ? AND table.contents.created < ?', $archive->created, \Helper::options()->time)->order('table.contents.created', \Typecho_Db::SORT_ASC)->limit(1));
        $wrap = function ($row) use ($archive) {
            if (!$row) return null;
            $row = $archive->filter($row);
            return ['cid' => (int)$row['cid'], 'title' => $row['title'], 'permalink' => $row['permalink'], 'thumb' => self::rowThumb($row)];
        };
        return self::$cache[$key] = ['prev' => $wrap($prev), 'next' => $wrap($next)];
    }

    /** 相关文章：同标签优先，不足补同分类。 */
    public static function related($archive, int $limit): array
    {
        if ($limit <= 0) return [];
        $cid = (int)$archive->cid;
        $key = 'related:' . $cid . ':' . $limit;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $db = \Typecho_Db::get();
        $out = [];
        $seen = [$cid => true];
        $collect = function (array $mids) use ($db, $archive, &$out, &$seen, $limit) {
            if (empty($mids) || count($out) >= $limit) return;
            $rows = $db->fetchAll($db->select('table.contents.*')->from('table.contents')
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid IN ?', $mids)
                ->where('table.contents.type = ?', 'post')
                ->where('table.contents.status = ?', 'publish')
                ->where("table.contents.password IS NULL OR table.contents.password = ''")
                ->order('table.contents.created', \Typecho_Db::SORT_DESC)
                ->limit($limit * 3));
            foreach ($rows as $r) {
                $id = (int)$r['cid'];
                if (isset($seen[$id])) continue;
                $seen[$id] = true;
                $out[] = $archive->filter($r);
                if (count($out) >= $limit) break;
            }
        };
        $tagMids = [];
        foreach ((array)$archive->tags as $t) if (isset($t['mid'])) $tagMids[] = (int)$t['mid'];
        $collect($tagMids);
        $catMids = [];
        foreach ((array)$archive->categories as $c) if (isset($c['mid'])) $catMids[] = (int)$c['mid'];
        $collect($catMids);
        return self::$cache[$key] = $out;
    }

    /** 热门文章：Kit 浏览量优先，否则按评论数。返回 filter 后的行。 */
    public static function hot($archive, int $limit): array
    {
        $key = 'hot:' . $limit;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $kit = Kit::hotPosts($limit);
        if (is_array($kit)) {
            $cids = array_map(function ($r) { return is_array($r) ? (int)$r['cid'] : (int)$r; }, $kit);
            return self::$cache[$key] = self::byCids($archive, $cids);
        }
        $db = \Typecho_Db::get();
        $rows = $db->fetchAll($db->select()->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->order('table.contents.commentsNum', \Typecho_Db::SORT_DESC)
            ->order('table.contents.created', \Typecho_Db::SORT_DESC)
            ->limit($limit));
        return self::$cache[$key] = array_map(function ($r) use ($archive) { return $archive->filter($r); }, $rows);
    }

    /** 最新若干篇（404 页随机用）。 */
    public static function recent($archive, int $limit): array
    {
        $db = \Typecho_Db::get();
        $now = (int)\Helper::options()->time ?: time();
        $rows = $db->fetchAll($db->select()->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where("table.contents.password IS NULL OR table.contents.password = ''")
            ->where('table.contents.created < ?', $now)
            ->order('table.contents.created', \Typecho_Db::SORT_DESC)
            ->limit($limit));
        return array_map(function ($r) use ($archive) { return $archive->filter($r); }, $rows);
    }

    /** 站点统计计数（文章 / 评论 / 分类 / 标签）。 */
    public static function counts(): array
    {
        if (isset(self::$cache['counts'])) return self::$cache['counts'];
        $db = \Typecho_Db::get();
        $c = function ($q) use ($db) { $r = $db->fetchRow($q); return (int)($r['n'] ?? 0); };
        return self::$cache['counts'] = [
            'posts' => $c($db->select(['COUNT(cid)' => 'n'])->from('table.contents')->where('type = ? AND status = ?', 'post', 'publish')),
            'comments' => $c($db->select(['COUNT(coid)' => 'n'])->from('table.comments')->where('status = ?', 'approved')),
            'categories' => $c($db->select(['COUNT(mid)' => 'n'])->from('table.metas')->where('type = ?', 'category')),
            'tags' => $c($db->select(['COUNT(mid)' => 'n'])->from('table.metas')->where('type = ?', 'tag')),
        ];
    }

    /** 是否使用杂志风文章页：杂志布局 + 文章（加密文章也显示封面，只是不出摘要）。 */
    public static function magazine($archive): bool
    {
        return (string)Options::get('PostLayout', 'magazine') === 'magazine' && $archive->is('post');
    }

    /** 是否使用沉浸式通栏头图：沉浸布局 + 文章或无自定义模板的页面 + 有真实配图 + 未加密。 */
    public static function immersive($archive): bool
    {
        if ((string)Options::get('PostLayout', 'magazine') !== 'immersive') return false;
        if (!$archive->is('single') || $archive->hidden) return false;
        if (!$archive->is('post') && !($archive->is('page') && empty($archive->template))) return false;
        return self::hasOwnImage($archive);
    }

    /** 读取 nimbusHide 多选字段。 */
    public static function hidden($post, string $what): bool
    {
        try {
            $v = $post->fields->nimbusHide;
        } catch (\Throwable $e) {
            return false;
        }
        if (is_array($v)) return in_array($what, $v, true);
        if (is_string($v)) return in_array($what, preg_split('/[,\s]+/', $v) ?: [], true);
        return false;
    }

    /** 数据库行对应的缩略图：thumb → Cuteen imgst → 正文首图 → 缩略图池 → 内置图。 */
    public static function rowThumb(array $row): string
    {
        $field = null;
        try {
            $db = \Typecho_Db::get();
            $fields = $db->fetchAll($db->select('name', 'str_value')->from('table.fields')->where('cid = ?', (int)$row['cid'])->where('name IN ?', ['thumb', 'imgst']));
            $values = [];
            foreach ($fields as $f) $values[$f['name']] = trim((string)$f['str_value']);
            $field = !empty($values['thumb']) ? $values['thumb'] : ($values['imgst'] ?? null);
        } catch (\Throwable $e) {
        }
        return self::thumbFrom($field, (string)($row['text'] ?? ''), Options::list('ThumbPool'), (int)$row['cid'], preg_replace('/\?v=.*$/', '', View::asset('img/thumbs')));
    }

    /** 自定义字段字符串，不存在返回 null。 */
    public static function field($post, string $name): ?string
    {
        try {
            $v = $post->fields->{$name};
        } catch (\Throwable $e) {
            return null;
        }
        return is_string($v) && trim($v) !== '' ? trim($v) : null;
    }
}
