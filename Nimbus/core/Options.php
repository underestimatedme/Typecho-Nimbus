<?php
namespace Nimbus;

/**
 * 主题配置读取。模板只通过本类取配置，统一默认值与类型转换。
 */
final class Options
{
    /** @var callable|null 测试注入的数据源 fn(string $key): ?string */
    private static $source = null;
    /** @var array<string,string>|null */
    private static $defaults = null;

    public static function setSource(callable $fn): void
    {
        self::$source = $fn;
        self::$defaults = null;
    }

    private static function raw(string $key): ?string
    {
        if (self::$source) {
            $v = (self::$source)($key);
            return $v === null ? null : (string)$v;
        }
        $o = \Helper::options();
        $v = $o->{$key};
        if (is_array($v)) return implode(',', $v);
        return $v === null ? null : (string)$v;
    }

    /** @return array<string,string> */
    public static function defaults(): array
    {
        if (self::$defaults !== null) return self::$defaults;
        $d = [];
        foreach (['Site', 'Index', 'Post', 'Sidebar', 'Appearance', 'Media', 'Advanced'] as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            foreach ($cls::items() as $it) {
                $d[$it['name']] = isset($it['default']) ? (string)$it['default'] : '';
            }
        }
        return self::$defaults = $d;
    }

    /**
     * 读取配置；空值回落到 $default，再回落到 Config 声明的默认值。
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $v = self::raw($key);
        if ($v === null || trim($v) === '') {
            if ($default !== null && $default !== '') return $default;
            $d = self::defaults();
            if (isset($d[$key]) && $d[$key] !== '') return $d[$key];
            return $default;
        }
        return $v;
    }

    public static function bool(string $key): bool
    {
        return in_array((string)self::get($key), ['1', 'true', 'on'], true);
    }

    public static function int(string $key): int
    {
        $v = self::get($key);
        if (is_numeric($v)) return (int)$v;
        $d = self::defaults();
        return (int)($d[$key] ?? 0);
    }

    /** 按换行 / 半角逗号 / 全角逗号 / 顿号 / 分号拆分，去空去重。 */
    public static function list(string $key): array
    {
        $v = (string)self::get($key, '');
        $parts = preg_split('/[\n\r,，、;；]+/u', $v);
        if (!is_array($parts)) return [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '' && !in_array($p, $out, true)) $out[] = $p;
        }
        return $out;
    }

    public static function json(string $key): ?array
    {
        $v = self::get($key);
        if (!is_string($v)) return null;
        $d = json_decode($v, true);
        return is_array($d) ? $d : null;
    }

    /** 多选框是否包含某值。 */
    public static function has(string $key, string $value): bool
    {
        return in_array($value, self::list($key), true);
    }
}
