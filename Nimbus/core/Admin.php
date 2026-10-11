<?php
namespace Nimbus;

/**
 * 后台「设置外观」页面渲染：分组 Tab + Typecho 原生表单元素。
 */
final class Admin
{
    const GROUPS = ['Site', 'Index', 'Post', 'Sidebar', 'Appearance', 'Media', 'Advanced'];

    /**
     * 多选项：Typecho 只有在已保存值是数组时才接受请求里的数组，首次保存会丢失；这里直接读取提交值，
     * 只保留声明过的选项（以及占位值「-」，表示全部取消）。
     * @param array<string,mixed> $settings
     * @param array<string,mixed> $post
     */
    public static function normalize(array $settings, array $post): array
    {
        foreach (self::GROUPS as $g) {
            $cls = 'Nimbus\\Config\\' . $g;
            foreach ($cls::items() as $it) {
                if ($it['type'] !== 'checkbox' || !isset($post[$it['name']]) || !is_array($post[$it['name']])) continue;
                $allowed = array_merge(array_map('strval', array_keys($it['options'] ?? [])), ['-']);
                $settings[$it['name']] = array_values(array_unique(array_intersect(array_map('strval', $post[$it['name']]), $allowed)));
            }
        }
        return $settings;
    }

    public static function save(array $settings, bool $isInit): void
    {
        $o = \Helper::options();
        // Typecho 切换主题前已删除旧配置行，但本请求 options 仍保留原主题设置。
        if ($isInit && preg_match('/^Cuteen(?:[_-].*)?$/i', (string)$o->theme)) {
            unset($settings['MenuSource']);
            $settings = self::migrateCuteen($settings, [
                'CustomMenu' => $o->CustomMenu, 'RandImg' => $o->RandImg,
                'LabelOrder' => $o->LabelOrder, 'MergePages' => $o->MergePages,
            ]);
        }
        if (!$isInit) $settings = self::normalize($settings, $_POST);
        $db = \Typecho_Db::get();
        // 启用主题时 options->theme 仍是旧主题名，按主题目录名保存
        $name = 'theme:' . basename(dirname(__DIR__));
        $exists = $db->fetchRow($db->select('name')->from('table.options')->where('name = ? AND user = 0', $name)->limit(1));
        if ($exists) $db->query($db->update('table.options')->rows(['value' => serialize($settings)])->where('name = ? AND user = 0', $name));
        else $db->query($db->insert('table.options')->rows(['name' => $name, 'user' => 0, 'value' => serialize($settings)]));
    }

    /** 切换时只继承菜单与缩略图池，已指定的 Nimbus 值优先。 */
    public static function migrateCuteen(array $settings, array $legacy): array
    {
        if (empty($settings['CustomMenu'])) {
            $menu = Menu::parse((string)($legacy['CustomMenu'] ?? ''));
            $items = $menu ? Menu::build([], $menu) : [];
            if ($items) $settings['CustomMenu'] = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (empty($settings['ThumbPool']) && is_string($legacy['RandImg'] ?? null)) $settings['ThumbPool'] = $legacy['RandImg'];
        if (empty($settings['MenuSource'])) $settings['MenuSource'] = ($legacy['LabelOrder'] ?? '') === 'P-C' ? 'pages-first' : 'categories-first';
        if (empty($settings['MenuPageGroup']) && is_string($legacy['MergePages'] ?? null)) $settings['MenuPageGroup'] = trim($legacy['MergePages']);
        return $settings;
    }

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
        echo '<a class="n-admin-link" href="https://github.com/underestimatedme/Typecho-Nimbus" target="_blank" rel="noopener">文档与反馈</a>';
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
