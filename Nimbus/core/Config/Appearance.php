<?php
namespace Nimbus\Config;

final class Appearance
{
    public static function title(): string { return '外观'; }

    public static function items(): array
    {
        return [
            ['name' => 'ThemeLight', 'type' => 'radio', 'label' => '亮色皮肤', 'description' => '白天模式使用的皮肤。', 'default' => 'sky', 'options' => ['sky' => '晴空 · 明亮通透']],
            ['name' => 'ThemeDark', 'type' => 'radio', 'label' => '暗色皮肤', 'description' => '夜间模式使用的皮肤。', 'default' => 'dusk', 'options' => ['dusk' => '暮色 · 深邃温暖', 'cyber' => '赛博朋克 · 霓虹']],
            ['name' => 'ThemeFollowSystem', 'type' => 'radio', 'label' => '默认跟随系统', 'description' => '访客首次访问时按系统亮暗偏好选择皮肤；关闭则默认亮色。', 'default' => '1', 'options' => ['1' => '跟随系统', '0' => '默认亮色']],
            ['name' => 'ThemeSwitchable', 'type' => 'radio', 'label' => '允许访客切换', 'description' => '关闭后不显示切换按钮。', 'default' => '1', 'options' => ['1' => '允许', '0' => '不允许']],
            ['name' => 'FontScheme', 'type' => 'radio', 'label' => '字体方案', 'description' => '衬线：标题使用主题内置的思源宋体子集；系统：全部使用系统字体；自定义：填写下方 URL 与字体名。', 'default' => 'serif', 'options' => ['serif' => '衬线标题（推荐）', 'system' => '系统字体', 'custom' => '自定义']],
            ['name' => 'FontCustomUrl', 'type' => 'text', 'label' => '自定义字体 CSS URL', 'description' => '如 Google Fonts 的 css2 链接。', 'default' => ''],
            ['name' => 'FontCustomFamily', 'type' => 'text', 'label' => '自定义字体名', 'description' => '用于标题的 font-family 名称。', 'default' => ''],
            ['name' => 'Radius', 'type' => 'radio', 'label' => '圆角大小', 'description' => '', 'default' => 'md', 'options' => ['sm' => '小', 'md' => '中', 'lg' => '大']],
            ['name' => 'Motion', 'type' => 'radio', 'label' => '动效强度', 'description' => '克制：关闭装饰性动画（光斑、Ken Burns），保留交互反馈。', 'default' => 'full', 'options' => ['full' => '全部', 'reduced' => '克制', 'off' => '关闭']],
            ['name' => 'Mourning', 'type' => 'radio', 'label' => '哀悼模式', 'description' => '全站灰度显示。', 'default' => '0', 'options' => ['1' => '开启', '0' => '关闭']],
        ];
    }
}
