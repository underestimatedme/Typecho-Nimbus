<?php
namespace Nimbus;

/**
 * 后台「设置外观」页面渲染：分组 Tab + Typecho 原生表单元素。
 */
final class Admin
{
    const GROUPS = ['Site', 'Index', 'Post', 'Sidebar', 'Appearance', 'Media', 'Advanced'];

    public static function render($form): void
    {
        $url = \Helper::options()->themeUrl . '/assets';
        echo '<link rel="stylesheet" href="' . View::e($url) . '/css/admin.css?v=' . NIMBUS_VERSION . '">';
        $defaults = [];
        foreach (self::GROUPS as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            foreach ($cls::items() as $it) $defaults[$it['name']] = isset($it['default']) ? $it['default'] : '';
        }
        // 从未保存过的多选项：由 admin.js 勾上默认值（Typecho 会把未保存的值当作空数组渲染）
        $unsaved = [];
        $opt = \Helper::options();
        foreach ($defaults as $k => $v) if (is_array($v) && $opt->{$k} === null) $unsaved[] = $k;
        echo '<div class="n-admin" data-nimbus-admin data-defaults="' . View::e(json_encode($defaults, JSON_UNESCAPED_UNICODE)) . '" data-unsaved="' . View::e(json_encode($unsaved)) . '">';
        echo '<div class="n-admin-head"><strong>Nimbus</strong><span class="n-admin-ver">v' . NIMBUS_VERSION . '</span>';
        echo '<a class="n-admin-link" href="https://github.com/underestimatedme/Nimbus" target="_blank" rel="noopener">文档与反馈</a>';
        echo '</div>';
        if (Kit::enabled()) {
            echo '<div class="n-admin-tip n-admin-tip-ok">已连接 <b>NimbusKit</b> v' . View::e((string)Kit::version()) . '：已开启 ' . View::e(implode('、', array_map(function ($f) { return ['views' => '浏览量', 'likes' => '点赞', 'search' => '即时搜索', 'ajaxComment' => 'AJAX 评论', 'sitemap' => 'Sitemap'][$f] ?? $f; }, Kit::features()))) . '。</div>';
        } else {
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
                $el->setAttribute('class', 'typecho-option n-admin-item n-tab-' . $g);
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
        // Typecho 把说明当 HTML 输出；统一转义，避免说明里的 <script> 等吞掉后续表单
        $desc = View::e(isset($it['description']) ? $it['description'] : '');
        $label = View::e($it['label']);
        switch ($it['type']) {
            case 'textarea':
                return new \Typecho_Widget_Helper_Form_Element_Textarea($it['name'], null, $default, $label, $desc);
            case 'radio':
                return new \Typecho_Widget_Helper_Form_Element_Radio($it['name'], $opts, $default, $label, $desc);
            case 'select':
                return new \Typecho_Widget_Helper_Form_Element_Select($it['name'], $opts, $default, $label, $desc);
            case 'checkbox':
                return new \Typecho_Widget_Helper_Form_Element_Checkbox($it['name'], $opts, $default === '' ? [] : (array)$default, $label, $desc);
            default:
                return new \Typecho_Widget_Helper_Form_Element_Text($it['name'], null, $default, $label, $desc);
        }
    }
}
