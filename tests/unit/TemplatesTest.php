<?php
// Typecho 只把头注释含 @package custom 的 page-*.php 列为可选模板
test('every page-*.php template is selectable in admin', function () {
    $files = glob(NIMBUS_DIR . '/page-*.php');
    assert_true(count($files) >= 5, 'templates found');
    foreach ($files as $f) {
        $head = substr((string)file_get_contents($f), 0, 400);
        assert_true(strpos($head, '@package custom') !== false, basename($f) . ' missing @package custom');
        assert_true((bool)preg_match('#/\*\*\s*\n\s*\*\s*\S+#u', $head), basename($f) . ' missing title line');
    }
});
