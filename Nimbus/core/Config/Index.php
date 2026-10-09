<?php
namespace Nimbus\Config;

final class Index
{
    public static function title(): string { return '首页'; }

    public static function items(): array
    {
        return [
            ['name' => 'HeroEnable', 'type' => 'radio', 'label' => '首页 Hero 区', 'description' => '关闭后首页从导航下方直接显示文章列表。', 'default' => '1', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'HeroImages', 'type' => 'textarea', 'label' => 'Hero 背景图', 'description' => '每行一个图片 URL，多行时随机展示。留空使用主题内置图。', 'default' => ''],
            ['name' => 'HeroHeight', 'type' => 'text', 'label' => 'Hero 高度', 'description' => 'CSS 高度值，如 600px 或 70vh。', 'default' => '600px'],
            ['name' => 'HeroTitle', 'type' => 'text', 'label' => 'Hero 主标题', 'description' => '留空使用站点名称。用 | 分隔前后两段，后段显示流光效果，如：给时光|以生命', 'default' => ''],
            ['name' => 'HeroSubtitles', 'type' => 'textarea', 'label' => 'Hero 副标题', 'description' => '每行一句，打字机效果轮播。', 'default' => "记录、思考、与世界温柔相处"],
            ['name' => 'HeroKicker', 'type' => 'text', 'label' => 'Hero 状态胶囊文字', 'description' => '显示在主标题上方的小胶囊，留空不显示。', 'default' => ''],
            ['name' => 'TopPosts', 'type' => 'text', 'label' => '置顶文章', 'description' => '文章 cid，用逗号分隔，按填写顺序轮播。留空不显示轮播。', 'default' => ''],
            ['name' => 'ListStyle', 'type' => 'radio', 'label' => '列表样式', 'description' => '', 'default' => 'card', 'options' => ['card' => '图文交错卡片', 'grid' => '三栏网格', 'plain' => '纯文字列表']],
            ['name' => 'Pagination', 'type' => 'radio', 'label' => '分页方式', 'description' => '', 'default' => 'normal', 'options' => ['normal' => '传统分页', 'more' => '加载更多按钮', 'infinite' => '无限滚动']],
            ['name' => 'ExcerptLength', 'type' => 'text', 'label' => '摘要字数', 'description' => '自动截取摘要的字数。', 'default' => '120'],
            ['name' => 'ThumbPool', 'type' => 'textarea', 'label' => '默认缩略图池', 'description' => '每行一个 URL。文章无图时按 cid 稳定选取。留空使用主题内置图。', 'default' => ''],
        ];
    }
}
