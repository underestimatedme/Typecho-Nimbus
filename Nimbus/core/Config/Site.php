<?php
namespace Nimbus\Config;

final class Site
{
    public static function title(): string { return '站点'; }

    public static function items(): array
    {
        return [
            ['name' => 'LogoImage', 'type' => 'text', 'label' => 'Logo 图片', 'description' => '导航栏 Logo 图片 URL，留空显示文字 Logo。建议高度 60px 以上的透明 PNG/SVG。', 'default' => ''],
            ['name' => 'LogoText', 'type' => 'text', 'label' => 'Logo 文字', 'description' => '留空使用站点名称。', 'default' => ''],
            ['name' => 'Favicon', 'type' => 'text', 'label' => 'Favicon', 'description' => '站点图标 URL（ico / png / svg）。', 'default' => ''],
            ['name' => 'SiteSubtitle', 'type' => 'text', 'label' => '站点副标题', 'description' => '显示在浏览器标题的站点名之后，如 "— 云间小记"。', 'default' => ''],
            ['name' => 'SiteSince', 'type' => 'text', 'label' => '建站日期', 'description' => '格式 YYYY-MM-DD，用于站点信息小部件计算运行天数。', 'default' => ''],
            ['name' => 'Language', 'type' => 'radio', 'label' => '前台语言', 'description' => '界面文字的语言（文章内容不受影响，后台设置页保持中文）。「跟随浏览器」按访客浏览器语言自动选择中文或英文；如果站点开启了页面缓存或 CDN 缓存 HTML，请选择固定语言。', 'default' => 'zh', 'options' => ['zh' => '中文', 'en' => 'English', 'auto' => '跟随浏览器']],
            ['name' => 'ICP', 'type' => 'text', 'label' => 'ICP 备案号', 'description' => '如 粤ICP备12345678号，自动链接到工信部网站。', 'default' => ''],
            ['name' => 'Police', 'type' => 'text', 'label' => '公安备案号', 'description' => '如 粤公网安备 44030502000000号，自动链接到公安备案查询。', 'default' => ''],
            ['name' => 'FooterHtml', 'type' => 'textarea', 'label' => '页脚自定义 HTML', 'description' => '显示在页脚版权信息下方，可放统计代码、额外声明。', 'default' => ''],
            ['name' => 'AuthorAvatar', 'type' => 'text', 'label' => '作者头像', 'description' => '留空使用站长邮箱的 Gravatar。', 'default' => ''],
            ['name' => 'AuthorName', 'type' => 'text', 'label' => '作者昵称', 'description' => '留空使用站点名称。', 'default' => ''],
            ['name' => 'AuthorBio', 'type' => 'text', 'label' => '作者签名', 'description' => '一句话介绍，显示在作者卡片头像下方。', 'default' => '热爱生活，偶尔写代码。'],
            ['name' => 'SocialLinks', 'type' => 'textarea', 'label' => '社交链接', 'description' => '每行一个，格式：名称|链接|图标。图标可选：github weibo bilibili twitter email rss qq wechat telegram zhihu link。例：GitHub|https://github.com/you|github', 'default' => ''],
        ];
    }
}
