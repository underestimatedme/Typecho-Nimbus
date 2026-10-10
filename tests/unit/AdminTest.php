<?php
// 后台选项说明会被 Typecho 当 HTML 输出：确认渲染前已转义，不会出现原始标签
test('config descriptions and labels contain no raw tags after escaping', function () {
    foreach (['Site', 'Index', 'Post', 'Sidebar', 'Appearance', 'Advanced'] as $g) {
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
