<?php
namespace Nimbus\Config;

/**
 * 文章 / 页面编辑页的自定义字段。
 */
final class Fields
{
    public static function render($layout): void
    {
        $f = [
            ['thumb', 'text', '缩略图 URL', '留空自动提取正文首图。'],
            ['summary', 'textarea', '自定义摘要', '留空自动截取正文。'],
            ['expireNotice', 'text', '过期提醒天数', '覆盖全局设置；0 关闭；留空用全局。'],
            ['passwordHint', 'text', '密码提示语', '仅密码保护文章显示。'],
            ['links', 'textarea', '友链数据（仅友链页模板）', 'JSON 数组：[{"name":"","url":"","avatar":"","desc":""}]'],
        ];
        foreach ($f as $row) {
            list($name, $type, $label, $desc) = $row;
            $cls = $type === 'textarea' ? '\Typecho_Widget_Helper_Form_Element_Textarea' : '\Typecho_Widget_Helper_Form_Element_Text';
            $el = new $cls($name, null, '', $label, $desc);
            $layout->addItem($el);
        }
        $cb = new \Typecho_Widget_Helper_Form_Element_Checkbox(
            'nimbusHide',
            ['title' => '隐藏标题', 'toc' => '隐藏目录', 'copyright' => '隐藏版权', 'reward' => '隐藏打赏'],
            [],
            '显示控制',
            ''
        );
        $layout->addItem($cb);
    }
}
