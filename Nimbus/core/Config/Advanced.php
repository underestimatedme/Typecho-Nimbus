<?php
namespace Nimbus\Config;

final class Advanced
{
    public static function title(): string { return '高级'; }

    public static function items(): array
    {
        return [
            ['name' => 'CdnPrefix', 'type' => 'text', 'label' => '静态资源 CDN 前缀', 'description' => '把主题 assets 目录同步到 CDN 后填写其 URL（不含结尾斜杠），如 https://cdn.example.com/nimbus/assets', 'default' => ''],
            ['name' => 'HeadCode', 'type' => 'textarea', 'label' => '自定义头部代码', 'description' => '插入 </head> 之前，可放 meta、统计脚本。', 'default' => ''],
            ['name' => 'FootCode', 'type' => 'textarea', 'label' => '自定义底部代码', 'description' => '插入 </body> 之前。', 'default' => ''],
            ['name' => 'CustomCss', 'type' => 'textarea', 'label' => '自定义 CSS', 'description' => '不含 <style> 标签。', 'default' => ''],
            ['name' => 'CustomJs', 'type' => 'textarea', 'label' => '自定义 JS', 'description' => '不含 <script> 标签。', 'default' => ''],
            ['name' => 'RouterEnable', 'type' => 'radio', 'label' => '无刷新页面切换', 'description' => '站内链接通过 fetch 加载并带过渡动画；与第三方脚本冲突时可关闭。', 'default' => '1', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'CustomMenu', 'type' => 'textarea', 'label' => '自定义导航菜单', 'description' => 'JSON 数组，留空自动使用独立页面。格式：[{"title":"分类","url":"/category/","children":[{"title":"技术","url":"/category/tech/"}]}]', 'default' => ''],
            ['name' => 'GravatarMirror', 'type' => 'text', 'label' => 'Gravatar 镜像', 'description' => '头像服务前缀。', 'default' => 'https://cravatar.cn/avatar/'],
            ['name' => 'Debug', 'type' => 'radio', 'label' => '调试模式', 'description' => '在浏览器控制台输出主题配置。', 'default' => '0', 'options' => ['1' => '开启', '0' => '关闭']],
        ];
    }
}
