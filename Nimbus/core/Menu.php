<?php
namespace Nimbus;

/**
 * 导航菜单树。
 */
final class Menu
{
    /**
     * @param array $pages 每项含 title、permalink
     * @param array|null $custom 自定义菜单 JSON 解析结果
     * @return array 每项 ['title','url','icon','children'=>[]]
     */
    public static function build(array $pages, ?array $custom, array $categories = [], bool $pagesFirst = false, string $pageGroup = ''): array
    {
        if (is_array($custom)) {
            $items = self::normalize($custom);
            if (!empty($items)) return $items;
        }
        $out = [];
        foreach ($pages as $p) {
            if (empty($p['title']) || empty($p['permalink'])) continue;
            $out[] = ['title' => (string)$p['title'], 'url' => (string)$p['permalink'], 'icon' => '', 'children' => []];
        }
        if ($pageGroup !== '' && $out) {
            $out = [['title' => $pageGroup, 'url' => '#', 'icon' => '', 'children' => $out]];
        }
        $byParent = [];
        foreach ($categories as $c) $byParent[(int)($c['parent'] ?? 0)][] = $c;
        $descendants = function (int $mid, array $seen = []) use (&$descendants, $byParent): array {
            if (isset($seen[$mid])) return [];
            $seen[$mid] = true;
            $children = [];
            foreach ($byParent[$mid] ?? [] as $child) {
                $children[] = ['title' => $child['name'], 'url' => $child['permalink']];
                $children = array_merge($children, $descendants((int)$child['mid'], $seen));
            }
            return $children;
        };
        $cats = [];
        foreach ($categories as $c) {
            if ((int)($c['parent'] ?? 0) !== 0) continue;
            $cats[] = ['title' => $c['name'], 'url' => $c['permalink'], 'children' => $descendants((int)$c['mid'])];
        }
        $cats = self::normalize($cats);
        return $pagesFirst ? array_merge($out, $cats) : array_merge($cats, $out);
    }

    /** Nimbus JSON 数组及 Cuteen 不带外层方括号的 JSON 菜单。 */
    public static function parse(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        $data = json_decode($raw, true);
        if (is_array($data) && (isset($data['name']) || isset($data['title']))) $data = [$data];
        if (!is_array($data)) $data = json_decode('[' . $raw . ']', true);
        return is_array($data) ? $data : null;
    }

    private static function normalize(array $list, int $depth = 0): array
    {
        $out = [];
        foreach ($list as $it) {
            if (!is_array($it)) continue;
            $title = $it['title'] ?? $it['name'] ?? '';
            if (!is_string($title) || trim($title) === '') continue;
            $children = [];
            if ($depth === 0 && !empty($it['children']) && is_array($it['children'])) {
                $children = self::normalize($it['children'], 1);
            }
            $url = $it['url'] ?? ($children ? '#' : null);
            if (!is_string($url)) continue;
            $out[] = [
                'title' => $title,
                'url' => $url,
                'icon' => isset($it['icon']) && is_string($it['icon']) ? $it['icon'] : '',
                'children' => $children,
            ];
        }
        return $out;
    }

    /** 从 Typecho 独立页面与后台配置组装菜单（首项固定首页）。 */
    public static function items(): array
    {
        $pages = [];
        try {
            $w = \Typecho_Widget::widget('Widget_Contents_Page_List');
            while ($w->next()) {
                $pages[] = ['title' => $w->title, 'permalink' => $w->permalink];
            }
        } catch (\Throwable $e) {
            $pages = [];
        }
        $source = (string)Options::get('MenuSource', 'pages');
        $categories = [];
        if ($source !== 'pages') {
            try {
                $w = \Typecho_Widget::widget('Widget_Metas_Category_List@nimbus-menu');
                while ($w->next()) {
                    $categories[] = ['mid' => $w->mid, 'parent' => $w->parent, 'name' => $w->name, 'permalink' => $w->permalink];
                }
            } catch (\Throwable $e) {}
        }
        $items = self::build($pages, self::parse((string)Options::get('CustomMenu', '')), $categories, $source === 'pages-first', (string)Options::get('MenuPageGroup', ''));
        array_unshift($items, ['title' => I18n::t('首页'), 'url' => (string)\Helper::options()->siteUrl, 'icon' => 'home', 'children' => []]);
        return $items;
    }

    /** 判断菜单项是否为当前页面。 */
    public static function isCurrent(string $url, string $current): bool
    {
        if ($url === '' || $url[0] === '#') return false;
        $a = rtrim(parse_url($url, PHP_URL_PATH) ?: '/', '/');
        $b = rtrim(parse_url($current, PHP_URL_PATH) ?: '/', '/');
        return $a === $b;
    }
}
