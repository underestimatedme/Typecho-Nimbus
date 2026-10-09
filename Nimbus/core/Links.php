<?php
namespace Nimbus;

/**
 * 友链数据：Kit 优先，否则页面自定义字段 JSON。
 */
final class Links
{
    public static function parse(?string $json): array
    {
        if (!is_string($json) || trim($json) === '') return [];
        $d = json_decode($json, true);
        if (!is_array($d)) return [];
        $out = [];
        foreach ($d as $it) {
            if (!is_array($it) || empty($it['name']) || empty($it['url']) || !is_string($it['url'])) continue;
            $out[] = [
                'name' => (string)$it['name'],
                'url' => $it['url'],
                'avatar' => isset($it['avatar']) && is_string($it['avatar']) ? $it['avatar'] : '',
                'desc' => isset($it['desc']) && is_string($it['desc']) ? $it['desc'] : '',
            ];
        }
        return $out;
    }

    /** @param mixed $page */
    public static function all($page): array
    {
        $kit = Kit::links();
        if (is_array($kit)) return self::parse(json_encode($kit));
        return self::parse(Post::field($page, 'links'));
    }

    public static function initial(string $name): string
    {
        $name = trim($name);
        if ($name === '') return '?';
        return mb_strtoupper(mb_substr($name, 0, 1));
    }
}
