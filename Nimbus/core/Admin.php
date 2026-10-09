<?php
namespace Nimbus;

/**
 * 后台「设置外观」页面渲染：分组 Tab + Typecho 原生表单元素。
 */
final class Admin
{
    const GROUPS = ['Site', 'Index', 'Post', 'Sidebar', 'Appearance', 'Advanced'];

    public static function render($form): void
    {
        $url = \Helper::options()->themeUrl . '/assets';
        echo '<link rel="stylesheet" href="' . View::e($url) . '/css/admin.css?v=' . NIMBUS_VERSION . '">';
        echo '<div class="n-admin" data-nimbus-admin>';
        echo '<div class="n-admin-head"><strong>Nimbus</strong><span class="n-admin-ver">v' . NIMBUS_VERSION . '</span>';
        echo '<a class="n-admin-link" href="https://github.com/underestimatedme/Nimbus" target="_blank" rel="noopener">文档与反馈</a>';
        echo '</div>';
        if (!Kit::enabled()) {
            echo '<div class="n-admin-tip">未检测到 <b>NimbusKit</b> 插件：点赞、浏览量、即时搜索、说说等互动功能会自动隐藏，主题其余功能不受影响。</div>';
        }
        echo '<nav class="n-admin-tabs" role="tablist">';
        foreach (self::GROUPS as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            echo '<button type="button" role="tab" data-tab="' . $g . '">' . View::e($cls::title()) . '</button>';
        }
        echo '</nav></div>';
        foreach (self::GROUPS as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            foreach ($cls::items() as $it) {
                $el = self::element($it);
                $el->setAttribute('class', 'n-admin-item n-tab-' . $g);
                $form->addInput($el);
            }
        }
        echo '<script src="' . View::e($url) . '/js/admin.js?v=' . NIMBUS_VERSION . '"></script>';
    }

    /** @return \Typecho_Widget_Helper_Form_Element */
    private static function element(array $it)
    {
        $opts = isset($it['options']) ? $it['options'] : null;
        $default = isset($it['default']) ? $it['default'] : '';
        $desc = isset($it['description']) ? $it['description'] : '';
        switch ($it['type']) {
            case 'textarea':
                return new \Typecho_Widget_Helper_Form_Element_Textarea($it['name'], null, $default, $it['label'], $desc);
            case 'radio':
                return new \Typecho_Widget_Helper_Form_Element_Radio($it['name'], $opts, $default, $it['label'], $desc);
            case 'select':
                return new \Typecho_Widget_Helper_Form_Element_Select($it['name'], $opts, $default, $it['label'], $desc);
            case 'checkbox':
                return new \Typecho_Widget_Helper_Form_Element_Checkbox($it['name'], $opts, $default === '' ? [] : (array)$default, $it['label'], $desc);
            default:
                return new \Typecho_Widget_Helper_Form_Element_Text($it['name'], null, $default, $it['label'], $desc);
        }
    }
}
