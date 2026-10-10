<?php
namespace Nimbus\Config;

final class Post
{
    public static function title(): string { return '文章'; }

    public static function items(): array
    {
        return [
            ['name' => 'PostLayout', 'type' => 'radio', 'label' => '文章页布局', 'description' => '沉浸：有头图的文章使用通栏大图标题；屏幕宽度 ≥1440 时为「目录｜正文｜侧栏」三栏。经典：卡片式头图，正文旁只显示目录。', 'default' => 'immersive', 'options' => ['immersive' => '沉浸（推荐）', 'classic' => '经典']],
            ['name' => 'ShowWordCount', 'type' => 'radio', 'label' => '显示字数', 'description' => '', 'default' => '1', 'options' => ['1' => '显示', '0' => '隐藏']],
            ['name' => 'ShowReadingTime', 'type' => 'radio', 'label' => '显示预计阅读时长', 'description' => '按每分钟 400 字估算。', 'default' => '1', 'options' => ['1' => '显示', '0' => '隐藏']],
            ['name' => 'TocEnable', 'type' => 'radio', 'label' => '文章目录', 'description' => '桌面端在右侧吸顶显示，移动端为浮动按钮。可在文章编辑页单独隐藏。', 'default' => '1', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'CodeLineNumbers', 'type' => 'radio', 'label' => '代码行号', 'description' => '', 'default' => '1', 'options' => ['1' => '显示', '0' => '隐藏']],
            ['name' => 'CodeFoldLines', 'type' => 'text', 'label' => '代码折叠阈值', 'description' => '超过该行数的代码块默认折叠，0 为不折叠。', 'default' => '30'],
            ['name' => 'CopyrightEnable', 'type' => 'radio', 'label' => '版权声明', 'description' => '文末显示版权卡片。', 'default' => '1', 'options' => ['1' => '显示', '0' => '隐藏']],
            ['name' => 'CopyrightText', 'type' => 'text', 'label' => '版权声明文案', 'description' => '', 'default' => '本文采用 CC BY-NC-SA 4.0 许可协议，转载请注明出处。'],
            ['name' => 'RewardEnable', 'type' => 'radio', 'label' => '打赏', 'description' => '文末显示打赏按钮，需至少填写一个收款码。', 'default' => '0', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'RewardAlipay', 'type' => 'text', 'label' => '支付宝收款码', 'description' => '图片 URL。', 'default' => ''],
            ['name' => 'RewardWechat', 'type' => 'text', 'label' => '微信收款码', 'description' => '图片 URL。', 'default' => ''],
            ['name' => 'RewardQQ', 'type' => 'text', 'label' => 'QQ 收款码', 'description' => '图片 URL。', 'default' => ''],
            ['name' => 'ShareEnable', 'type' => 'radio', 'label' => '分享按钮', 'description' => '微博、QQ 空间、复制链接。', 'default' => '1', 'options' => ['1' => '显示', '0' => '隐藏']],
            ['name' => 'RelatedCount', 'type' => 'text', 'label' => '相关文章数量', 'description' => '同标签优先，不足补同分类。0 为不显示。', 'default' => '4'],
            ['name' => 'ExpireDays', 'type' => 'text', 'label' => '过期提醒天数', 'description' => '文章最后修改超过该天数时显示提醒条，0 为关闭。', 'default' => '0'],
            ['name' => 'LightboxEnable', 'type' => 'radio', 'label' => '图片灯箱', 'description' => '点击正文图片放大查看。', 'default' => '1', 'options' => ['1' => '开启', '0' => '关闭']],
            ['name' => 'MathEnable', 'type' => 'radio', 'label' => '数学公式 (KaTeX)', 'description' => '自动：检测到 $$ 或 \\( 时加载。', 'default' => 'auto', 'options' => ['auto' => '自动检测', 'on' => '总是加载', 'off' => '关闭']],
            ['name' => 'MermaidEnable', 'type' => 'radio', 'label' => '流程图 (Mermaid)', 'description' => '自动：检测到 mermaid 代码块时加载。', 'default' => 'auto', 'options' => ['auto' => '自动检测', 'on' => '总是加载', 'off' => '关闭']],
        ];
    }
}
