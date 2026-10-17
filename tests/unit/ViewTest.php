<?php
use Nimbus\View;

test('e escapes', fn() => assert_eq('&lt;a&gt;&quot;', View::e('<a>"')));
test('sameText recognizes content that differs only by markup and whitespace', function () {
    $same = method_exists(View::class, 'sameText') && View::sameText('<p>公告&nbsp; 内容</p>', "公告\n 内容");
    assert_true($same, 'HTML formatting and whitespace should not make duplicate content look different');
});
test('sameText keeps different visible text distinct', function () {
    $same = method_exists(View::class, 'sameText') && View::sameText('公告内容', '另一条公告');
    assert_eq(false, $same);
});
test('e null safe', fn() => assert_eq('', View::e(null)));
test('icon returns svg with class', fn() => assert_true(strpos(View::icon('search', 'n-ico'), '<svg') === 0 && strpos(View::icon('search', 'n-ico'), 'n-ico') !== false));
test('icon unknown returns empty', fn() => assert_eq('', View::icon('nope')));
test('social icons exist', function () { foreach (['github', 'weibo', 'bilibili', 'twitter', 'email', 'rss', 'qq', 'wechat', 'telegram', 'zhihu', 'link'] as $n) assert_true(View::icon($n) !== '', "icon $n"); });
test('t decodes stored title entities', fn() => assert_eq('C++ & Rust "笔记"', View::t('C++ &amp; Rust &quot;笔记&quot;')));
test('e(t()) escapes exactly once', fn() => assert_eq('C++ &amp; Rust', View::e(View::t('C++ &amp; Rust'))));
test('View::date 按站点时区格式化', function () {
    \Nimbus\View::$tz = 8 * 3600;
    $ts = gmmktime(20, 30, 0, 10, 10, 2026);
    assert_eq('2026-10-11 04:30', \Nimbus\View::date('Y-m-d H:i', $ts));
    assert_eq('2026-10-11T04:30:00+08:00', \Nimbus\View::date('c', $ts));
    \Nimbus\View::$tz = -5 * 3600 - 1800;
    assert_eq('2026-10-10 15:00', \Nimbus\View::date('Y-m-d H:i', $ts));
    \Nimbus\View::$tz = null;
});
