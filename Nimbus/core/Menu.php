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
    public static function build(array $pages, ?array $custom): array
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
        return $out;
    }

    private static function normalize(array $list, int $depth = 0): array
    {
        $out = [];
        foreach ($list as $it) {
            if (!is_array($it) || empty($it['title']) || !isset($it['url']) || !is_string($it['url'])) continue;
            $children = [];
            if ($depth === 0 && !empty($it['children']) && is_array($it['children'])) {
                $children = self::normalize($it['children'], 1);
            }
            $out[] = [
                'title' => (string)$it['title'],
                'url' => $it['url'],
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
        $items = self::build($pages, Options::json('CustomMenu'));
        array_unshift($items, ['title' => I18n::t('首页'), 'url' => (string)\Helper::options()->siteUrl, 'icon' => 'home', 'children' => []]);
        return $items;
    }

    /** 判断菜单项是否为当前页面。 */
    public static function isCurrent(string $url, string $current): bool
    {
        $a = rtrim(parse_url($url, PHP_URL_PATH) ?: '/', '/');
        $b = rtrim(parse_url($current, PHP_URL_PATH) ?: '/', '/');
        return $a === $b;
    }
}
