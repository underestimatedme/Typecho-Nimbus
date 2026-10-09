<?php
namespace Nimbus;

/**
 * NimbusKit 插件适配层。插件未启用时所有数据方法返回 null，模板据此降级。
 */
final class Kit
{
    const MIN_VERSION = '1.0.0';
    /** @var bool|null */
    private static $enabled = null;

    public static function enabled(): bool
    {
        if (self::$enabled !== null) return self::$enabled;
        self::$enabled = false;
        if (defined('NIMBUS_TESTING')) return false;
        try {
            $plugins = \Typecho_Plugin::export();
            $activated = isset($plugins['activated']) ? $plugins['activated'] : [];
            if (is_array($activated) && array_key_exists('NimbusKit', $activated) && class_exists('NimbusKit_Api')) {
                $ver = defined('NimbusKit_Api::VERSION') ? \NimbusKit_Api::VERSION : '0';
                self::$enabled = version_compare($ver, self::MIN_VERSION, '>=');
            }
        } catch (\Throwable $e) {
            self::$enabled = false;
        }
        return self::$enabled;
    }

    public static function apiUrl(): ?string
    {
        return self::enabled() ? rtrim((string)\Helper::options()->index, '/') . '/nimbus-api' : null;
    }

    /** @return mixed|null */
    private static function call(string $method, array $args = [])
    {
        if (!self::enabled() || !method_exists('NimbusKit_Api', $method)) return null;
        try {
            return call_user_func_array(['NimbusKit_Api', $method], $args);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function views(int $cid): ?int
    {
        $v = self::call('views', [$cid]);
        return $v === null ? null : (int)$v;
    }

    public static function likes(int $cid): ?int
    {
        $v = self::call('likes', [$cid]);
        return $v === null ? null : (int)$v;
    }

    public static function hotPosts(int $limit): ?array
    {
        $v = self::call('hotPosts', [$limit]);
        return is_array($v) ? $v : null;
    }

    public static function links(): ?array
    {
        $v = self::call('links');
        return is_array($v) ? $v : null;
    }
}
