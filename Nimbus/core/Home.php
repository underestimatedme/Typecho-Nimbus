<?php
namespace Nimbus;

/** 首页入口只读取站点配置；未配置时保留原来的阅读、随机文章行为。 */
final class Home
{
    public static function actions(string $randomUrl = ''): array
    {
        $primary = self::url((string)Options::get('HeroPrimaryUrl', ''));
        $secondary = self::url((string)Options::get('HeroSecondaryUrl', ''));
        $customSecondary = $secondary !== '';
        if (!$customSecondary) $secondary = self::url($randomUrl);
        $actions = [[
            'label' => (string)Options::get('HeroPrimaryLabel', I18n::t('开始阅读')),
            'url' => $primary !== '' ? $primary : '#n-posts',
            'icon' => 'arrow-right',
        ]];
        if ($secondary !== '') $actions[] = [
            'label' => (string)Options::get('HeroSecondaryLabel', I18n::t('随便看看')),
            'url' => $secondary,
            'icon' => $customSecondary ? 'arrow-right' : 'shuffle',
        ];
        return $actions;
    }

    private static function url(string $url): string
    {
        $url = trim($url);
        if (preg_match('/[\\\\\x00-\x20]/', $url)) return '';
        if (preg_match('/^#[a-zA-Z][a-zA-Z0-9_-]*$/D', $url)) return $url;
        return Music::safeUrl($url);
    }
}
