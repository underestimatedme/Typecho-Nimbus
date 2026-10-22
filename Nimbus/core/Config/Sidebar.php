<?php
namespace Nimbus\Config;

final class Sidebar
{
    public static function title(): string { return '侧栏'; }

    public static function items(): array
    {
        return [
            ['name' => 'SidebarPosition', 'type' => 'radio', 'label' => '侧栏位置', 'description' => '', 'default' => 'right', 'options' => ['right' => '右侧', 'left' => '左侧', 'none' => '不显示']],
            ['name' => 'Widgets', 'type' => 'textarea', 'label' => '小部件顺序', 'description' => '每行一个，按顺序显示；行首加 # 可临时关闭。可用：author（作者卡）notice（公告）hot（热门文章）recent-comments（最新评论）tags（标签云）categories（分类）calendar（日历）site-info（站点信息）custom-html（自定义 HTML）', 'default' => "author\nnotice\nhot\nrecent-comments\ntags\ncategories"],
            ['name' => 'NoticeHtml', 'type' => 'textarea', 'label' => '公告内容', 'description' => '支持 HTML 与链接。经典布局侧栏留空不显示公告；杂志风首页留空显示带 GitHub 链接的 Nimbus 主题介绍。', 'default' => ''],
            ['name' => 'HotCount', 'type' => 'text', 'label' => '热门文章数量', 'description' => '', 'default' => '5'],
            ['name' => 'CustomWidgetTitle', 'type' => 'text', 'label' => '自定义小部件标题', 'description' => '', 'default' => ''],
            ['name' => 'CustomWidgetHtml', 'type' => 'textarea', 'label' => '自定义小部件内容', 'description' => '支持 HTML。', 'default' => ''],
        ];
    }
}
