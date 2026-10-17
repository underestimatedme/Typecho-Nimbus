<?php
use Nimbus\Assets;

test('CSS 入口解析与合并：按层包裹、保留其他语句', function () {
    $entry = "@layer a, b;\n@import url(\"x.css\") layer(b);\n@import url('y.css');\n.z{color:red}";
    $out = Assets::bundle($entry, function ($f) { return $f === 'x.css' ? '.x{}' : '.y{}'; });
    assert_true(strpos($out, "@layer a, b;") !== false);
    assert_true(strpos($out, "@layer b {\n.x{}\n}") !== false, $out);
    assert_true(strpos($out, ".y{}") !== false && strpos($out, '@import') === false);
    assert_true(strpos($out, '.z{color:red}') !== false);
    assert_eq(['x.css', 'y.css'], Assets::parseEntry($entry)[1]);
});
test('仓库附带的 nimbus.bundle.css 与源文件一致', function () {
    $dir = dirname(__DIR__, 2) . '/Nimbus/assets/css';
    $want = Assets::bundle((string)file_get_contents($dir . '/nimbus.css'), function ($f) use ($dir) { return (string)file_get_contents($dir . '/' . $f); });
    assert_true(is_file($dir . '/nimbus.bundle.css') && file_get_contents($dir . '/nimbus.bundle.css') === $want, '请运行 php tests/build-css.php 重新生成');
});
test('杂志文章分隔线使用紧凑的渐变线样式', function () {
    $css = file_get_contents(dirname(__DIR__, 2) . '/Nimbus/assets/css/pages/article.css');
    assert_true(strpos($css, '.n-prose-magazine hr { width: 72px; height: 2px; margin: 1.5em auto;') !== false, $css);
    assert_true(strpos($css, '✦   ✦   ✦') === false, '不应再显示三颗星');
});
test('模块依赖：解析静态 import、规范化路径、包含页面模块', function () {
    assert_eq(['./util.js', '../modules/a.js'], Assets::imports("import { x } from './util.js';\nimport * as a from '../modules/a.js';\nimport('./lazy.js');\nconst s = 'import x from \"./no.js\"';"));
    $g = Assets::moduleGraph(['app.js', 'pages/post.js']);
    assert_true(in_array('modules/util.js', $g, true) && in_array('modules/toc.js', $g, true) && in_array('pages/post.js', $g, true));
    assert_true(!in_array('modules/router.js', $g, true), '动态 import 不应出现');
});
test('模块版本号：覆盖全部模块、不含后台脚本、格式稳定', function () {
    $v = Assets::jsVersions();
    assert_true(isset($v['app.js'], $v['modules/util.js'], $v['pages/post.js']));
    assert_true(!isset($v['admin.js']));
    foreach ($v as $rel => $ver) assert_true((bool)preg_match('/^[0-9a-f]{8}$/', $ver), $rel);
    assert_eq($v, Assets::jsVersions());
});
