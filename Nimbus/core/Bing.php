<?php
namespace Nimbus;

/**
 * 每日必应壁纸：服务端每天向必应取一次当日图片，结果缓存在 options 表（nimbus:bing），访客请求不会等待必应。
 * 取不到时一小时内不再重试，页面回落到自定义背景图或极光背景。中文站用 cn.bing.com（国内可访问）。
 */
final class Bing
{
    const ROW = 'nimbus:bing';

    /** 解析必应 HPImageArchive 接口返回。@return array{url:string,title:string,copyright:string,link:string}|null */
    public static function parse(string $json, string $host, string $size = '1920x1080'): ?array
    {
        $d = json_decode($json, true);
        $img = is_array($d) && isset($d['images'][0]) && is_array($d['images'][0]) ? $d['images'][0] : null;
        if (!$img) return null;
        $base = (string)($img['urlbase'] ?? '');
        if (!preg_match('#^/th\?id=[A-Za-z0-9._-]+$#', $base)) return null;
        $size = in_array($size, ['1920x1080', 'UHD'], true) ? $size : '1920x1080';
        $link = (string)($img['copyrightlink'] ?? '');
        if (!preg_match('#^https://(www|cn)\.bing\.com/#', $link)) $link = '';
        $clean = function ($s) { return trim(mb_substr((string)preg_replace('/[\x00-\x1f]/u', '', (string)$s), 0, 200)); };
        return [
            'url' => 'https://' . $host . $base . '_' . $size . '.jpg',
            'title' => $clean($img['title'] ?? ''),
            'copyright' => $clean($img['copyright'] ?? ''),
            'link' => $link,
        ];
    }

    /** 根据前台语言选择区域。 */
    public static function market(): array
    {
        return I18n::lang() === 'en' ? ['en-US', 'www.bing.com'] : ['zh-CN', 'cn.bing.com'];
    }

    /** 当日壁纸（带缓存）；失败返回 null。 */
    public static function today(): ?array
    {
        static $memo = false;
        if ($memo !== false) return $memo;
        [$mkt, $host] = self::market();
        $now = time();
        $day = View::date('Ymd', $now);
        $db = \Typecho_Db::get();
        $row = null;
        try {
            $r = $db->fetchRow($db->select('value')->from('table.options')->where('name = ? AND user = 0', self::ROW)->limit(1));
            $row = $r ? json_decode((string)$r['value'], true) : null;
        } catch (\Throwable $e) {
            return $memo = null;
        }
        if (is_array($row) && ($row['day'] ?? '') === $day && ($row['mkt'] ?? '') === $mkt) {
            if (!empty($row['data'])) return $memo = $row['data'];
            if ((int)($row['retry'] ?? 0) > $now) return $memo = (isset($row['last']) && is_array($row['last']) ? $row['last'] : null);
        }
        $json = self::fetch('https://' . $host . '/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=' . $mkt);
        $data = $json === '' ? null : self::parse($json, $host);
        $last = is_array($row) ? ($row['data'] ?? ($row['last'] ?? null)) : null;
        $save = ['day' => $day, 'mkt' => $mkt, 'data' => $data, 'retry' => $data ? 0 : $now + 3600, 'last' => $data ?: $last];
        try {
            $v = (string)json_encode($save, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($row !== null || $r) $db->query($db->update('table.options')->rows(['value' => $v])->where('name = ? AND user = 0', self::ROW));
            else $db->query($db->insert('table.options')->rows(['name' => self::ROW, 'user' => 0, 'value' => $v]));
        } catch (\Throwable $e) {
        }
        // 当天取不到时沿用上一次成功的图片
        return $memo = ($data ?: (is_array($last) ? $last : null));
    }

    private static function fetch(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_USERAGENT => 'Nimbus Theme', CURLOPT_FOLLOWLOCATION => false]);
            $res = curl_exec($ch);
            $ok = $res !== false && (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE) === 200;
            if (PHP_VERSION_ID < 80000) curl_close($ch);
            return $ok ? (string)$res : '';
        }
        $res = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 3, 'header' => 'User-Agent: Nimbus Theme']]));
        return $res === false ? '' : (string)$res;
    }

    /** 首页背景：返回 [图片地址, 署名信息|null]；未开启或失败时用自定义背景图。 */
    public static function heroImage(): array
    {
        $custom = Options::list('HeroImages');
        $customUrl = $custom ? $custom[array_rand($custom)] : '';
        if ((string)Options::get('HeroSource', 'custom') === 'bing') {
            $b = self::today();
            if ($b) return [$b['url'], $b];
        }
        return [$customUrl, null];
    }
}
