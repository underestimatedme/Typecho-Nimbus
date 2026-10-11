<?php
test('Cuteen activation carries old menu and random image pool into Nimbus', function () {
    $s = Nimbus\Admin::migrateCuteen(['CustomMenu' => '', 'ThumbPool' => ''], ['CustomMenu' => '{"name":"闪念","url":"/blink.html"}', 'RandImg' => "https://img.example/a.jpg\nhttps://img.example/b.jpg", 'LabelOrder' => 'P-C', 'MergePages' => '页面']);
    assert_eq('闪念', json_decode($s['CustomMenu'], true)[0]['title']);
    assert_eq("https://img.example/a.jpg\nhttps://img.example/b.jpg", $s['ThumbPool']);
    assert_eq('pages-first', $s['MenuSource']);
    assert_eq('页面', $s['MenuPageGroup']);
});
test('Cuteen migration preserves explicit Nimbus settings', function () {
    $s = Nimbus\Admin::migrateCuteen(['CustomMenu' => '[{"title":"新","url":"/new"}]', 'ThumbPool' => '/new.jpg', 'MenuSource' => 'pages'], ['CustomMenu' => '{"name":"旧","url":"/old"}', 'RandImg' => '/old.jpg']);
    assert_eq('[{"title":"新","url":"/new"}]', $s['CustomMenu']);
    assert_eq('/new.jpg', $s['ThumbPool']);
    assert_eq('pages', $s['MenuSource']);
});
// 后台选项说明会被 Typecho 当 HTML 输出：确认渲染前已转义，不会出现原始标签
test('config descriptions and labels contain no raw tags after escaping', function () {
    foreach (['Site', 'Index', 'Post', 'Sidebar', 'Appearance', 'Media', 'Advanced'] as $g) {
        $cls = 'Nimbus\\Config\\' . $g;
        foreach ($cls::items() as $it) {
            $d = Nimbus\View::e($it['description'] ?? '');
            assert_true(strpos($d, '<') === false, "$g/{$it['name']} description");
        }
    }
});
test('Admin escapes description before handing to Typecho', function () {
    $src = file_get_contents(NIMBUS_DIR . '/core/Admin.php');
    assert_true(strpos($src, "\$desc = View::e(") !== false);
});
test('多选项保存：读取提交值、过滤未知选项、保留全部取消占位', function () {
    $s = \Nimbus\Admin::normalize(['CyberEffects' => null, 'Motion' => 'full'], ['CyberEffects' => ['-', 'rain', 'evil<script>', 'trail', 'rain']]);
    assert_eq(['-', 'rain', 'trail'], $s['CyberEffects']);
    assert_eq('full', $s['Motion']);
    assert_eq(['-'], \Nimbus\Admin::normalize([], ['CyberEffects' => ['-']])['CyberEffects']);
    assert_eq(null, \Nimbus\Admin::normalize(['CyberEffects' => null], [])['CyberEffects']);
    assert_eq(null, \Nimbus\Admin::normalize(['CyberEffects' => null], ['CyberEffects' => 'rain'])['CyberEffects']);
});
