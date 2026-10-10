<?php
namespace TypechoPlugin\NimbusKit;

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 插件配置读取（带默认值，未保存配置时也可用）。
 */
final class Config
{
    const FEATURES = ['views', 'likes', 'search', 'ajaxComment', 'sitemap'];
    const DEFAULTS = [
        'features' => self::FEATURES,
        'mailEnable' => '0',
        'smtpHost' => '',
        'smtpPort' => '465',
        'smtpSecure' => 'ssl',
        'smtpUser' => '',
        'smtpPass' => '',
        'mailFromName' => '',
        'mailFrom' => '',
        'adminMail' => '',
        'notifyAdmin' => '1',
        'notifyReply' => '1',
        'dropOnDeactivate' => '0',
    ];
    /** @var array|null */
    private static $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;
        $cfg = self::DEFAULTS;
        try {
            $plugin = \Helper::options()->plugin('NimbusKit');
            foreach (array_keys(self::DEFAULTS) as $k) {
                $v = $plugin->{$k};
                if ($v !== null) $cfg[$k] = $v;
            }
        } catch (\Throwable $e) {
            // 未保存配置：用默认值
        }
        if (!is_array($cfg['features'])) $cfg['features'] = $cfg['features'] === '' ? [] : (array)$cfg['features'];
        return self::$cache = $cfg;
    }

    public static function get(string $key)
    {
        $a = self::all();
        return $a[$key] ?? null;
    }

    public static function feature(string $name): bool
    {
        return in_array($name, (array)self::get('features'), true);
    }

    public static function reset(): void
    {
        self::$cache = null;
    }
}
