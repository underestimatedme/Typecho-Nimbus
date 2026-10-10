<?php
namespace Nimbus;

/**
 * 前台界面中英双语。源码里直接写中文原文，I18n::t('原文') 在英文模式下查 lang/en.php。
 * 占位符写作 {name}：I18n::t('共 {n} 篇', ['n' => 3])。后台设置页保持中文。
 */
final class I18n
{
    /** @var string|null */
    private static $lang = null;
    /** @var array|null */
    private static $pack = null;

    /** 测试或预览时强制语言。 */
    public static function set(?string $lang): void
    {
        self::$lang = $lang === null ? null : ($lang === 'en' ? 'en' : 'zh');
        self::$pack = null;
    }

    /** 按 Accept-Language 选择：最先出现的 en / zh 胜出，都没有时用中文。 */
    public static function negotiate(string $header): string
    {
        foreach (explode(',', strtolower($header)) as $part) {
            $tag = trim(explode(';', $part)[0]);
            if ($tag === '' || $tag === '*') continue;
            if (strpos($tag, 'zh') === 0) return 'zh';
            if (strpos($tag, 'en') === 0) return 'en';
        }
        return 'zh';
    }

    public static function lang(): string
    {
        if (self::$lang !== null) return self::$lang;
        try {
            $opt = (string)Options::get('Language', 'zh');
        } catch (\Throwable $e) {
            return 'zh'; // 配置不可用（如命令行测试）时用中文，且不缓存
        }
        if ($opt === 'auto') return self::$lang = self::negotiate((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        return self::$lang = ($opt === 'en' ? 'en' : 'zh');
    }

    public static function htmlLang(): string
    {
        return self::lang() === 'en' ? 'en' : 'zh-CN';
    }

    private static function pack(): array
    {
        if (self::$pack !== null) return self::$pack;
        if (self::lang() !== 'en') return self::$pack = ['ui' => [], 'js' => []];
        $p = include dirname(__DIR__) . '/lang/en.php';
        return self::$pack = is_array($p) ? $p + ['ui' => [], 'js' => []] : ['ui' => [], 'js' => []];
    }

    /** @param array<string,scalar> $args */
    public static function t(string $zh, array $args = []): string
    {
        $p = self::pack();
        $s = $p['ui'][$zh] ?? $p['js'][$zh] ?? $zh;
        if ($args) {
            $map = [];
            foreach ($args as $k => $v) $map['{' . $k . '}'] = (string)$v;
            $s = strtr($s, $map);
        }
        return $s;
    }

    /** 转义后的 t()，用于 HTML 输出。 */
    public static function e(string $zh, array $args = []): string
    {
        return View::e(self::t($zh, $args));
    }

    /** 下发给前端脚本的词条（中文模式为空，前端直接用原文）。 */
    public static function js(): array
    {
        return self::pack()['js'];
    }
}
