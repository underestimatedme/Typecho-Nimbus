<?php
namespace Nimbus\Config;

final class Index
{
    public static function title(): string { return '首页'; }

    public static function items(): array
    {
        return [
            ['name' => 'HomeStyle', 'type' => 'radio', 'label' => '首页风格', 'description' => '杂志：全屏封面（极光背景、最新文章卡片堆、分类滚动条）+ 精选 Bento 网格 + 通栏文章网格，首页不显示侧栏；经典：Hero + 置顶轮播 + 文章列表 + 侧栏。两种风格都使用下面的 Hero 标题、副标题与背景图。', 'default' => 'magazine', 'options' => ['magazine' => '杂志', 'classic' => '经典']],
            ['name' => 'HeroEnable', 'type' => 'radio', 'label' => '首页 Hero 区', 'description' => '关闭后首页从导航下方直接显示文章列表。', 'default' => '1', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'HeroSource', 'type' => 'radio', 'label' => 'Hero 背景来源', 'description' => '每日必应：每天自动换成必应首页的当日壁纸（服务端每天取一次并缓存，中文站使用 cn.bing.com，国内可访问），封面右下角显示图片说明与版权；取不到时使用下面的背景图。图片版权归原作者所有，仅供个人站点展示。', 'default' => 'custom', 'options' => ['custom' => '自定义图片', 'bing' => '每日必应']],
            ['name' => 'CoverStackCount', 'type' => 'select', 'label' => '封面卡片数量', 'description' => '杂志风首页封面右侧叠放的最新文章卡片数量。', 'default' => '3', 'options' => ['0' => '不显示', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']],
            ['name' => 'HeroImages', 'type' => 'textarea', 'label' => 'Hero 背景图', 'description' => '每行一个图片 URL，多行时随机展示。留空使用主题内置图。', 'default' => ''],
            ['name' => 'HeroHeight', 'type' => 'text', 'label' => 'Hero 高度', 'description' => 'CSS 高度值，如 600px 或 70vh。', 'default' => '600px'],
            ['name' => 'HeroTitle', 'type' => 'text', 'label' => 'Hero 主标题', 'description' => '留空使用站点名称。用 | 分隔前后两段，后段显示流光效果，如：给时光|以生命', 'default' => ''],
            ['name' => 'HeroSubtitles', 'type' => 'textarea', 'label' => 'Hero 副标题', 'description' => '每行一句，打字机效果轮播。', 'default' => "记录、思考、与世界温柔相处"],
            ['name' => 'HeroKicker', 'type' => 'text', 'label' => 'Hero 状态胶囊文字', 'description' => '显示在主标题上方的小胶囊，留空不显示。', 'default' => ''],
            ['name' => 'HeroPrimaryLabel', 'type' => 'text', 'label' => '首页主按钮文字', 'description' => '留空使用「开始阅读」。', 'default' => ''],
            ['name' => 'HeroPrimaryUrl', 'type' => 'text', 'label' => '首页主按钮地址', 'description' => '支持 https://、http://、站内绝对路径或 #锚点；留空滚动到文章列表。导览页发布后再填写真实地址。', 'default' => ''],
            ['name' => 'HeroSecondaryLabel', 'type' => 'text', 'label' => '首页次按钮文字', 'description' => '留空使用「随便看看」。', 'default' => ''],
            ['name' => 'HeroSecondaryUrl', 'type' => 'text', 'label' => '首页次按钮地址', 'description' => '支持 https://、http://、站内绝对路径或 #锚点；留空随机打开一篇文章。', 'default' => ''],
            ['name' => 'TopPosts', 'type' => 'text', 'label' => '置顶文章', 'description' => '文章 cid，用逗号分隔，按填写顺序轮播。留空不显示轮播。', 'default' => ''],
            ['name' => 'TutorialCategories', 'type' => 'textarea', 'label' => '教程分类', 'description' => '把分类当作系列教程：每行一个分类缩略名，可在后面加「|封面图 URL」。教程会出现在杂志风首页与「教程」页面模板中；分类页显示为课程目录；文章页显示章节导航，上一篇 / 下一篇改为上一章 / 下一章。章节顺序：文章自定义字段 chapter（数字）优先，否则按发布时间从早到晚。', 'default' => ''],
            ['name' => 'ListStyle', 'type' => 'radio', 'label' => '列表样式', 'description' => '', 'default' => 'card', 'options' => ['card' => '图文交错卡片', 'grid' => '三栏网格', 'plain' => '纯文字列表']],
            ['name' => 'Pagination', 'type' => 'radio', 'label' => '分页方式', 'description' => '首页与分类、标签、搜索等文章列表的翻页方式。分页：底部显示页码；滚动加载：滑到底部自动加载下一页；加载更多：底部放一个按钮，点击加载下一页。', 'default' => 'normal', 'options' => ['normal' => '分页（页码）', 'infinite' => '滚动加载', 'more' => '加载更多按钮']],
            ['name' => 'PaginationSwitch', 'type' => 'radio', 'label' => '访客可切换分页方式', 'description' => '开启后在文章列表显示「分页 / 滚动」切换按钮，访客的选择记在其浏览器里；关闭时所有访客都使用上面设置的分页方式。', 'default' => '0', 'options' => ['0' => '关闭', '1' => '开启']],
            ['name' => 'ExcerptLength', 'type' => 'text', 'label' => '摘要字数', 'description' => '自动截取摘要的字数。', 'default' => '120'],
            ['name' => 'ThumbPool', 'type' => 'textarea', 'label' => '默认缩略图池', 'description' => '每行一个 URL。文章无图时按 cid 稳定选取。留空使用主题内置图。', 'default' => ''],
        ];
    }
}
