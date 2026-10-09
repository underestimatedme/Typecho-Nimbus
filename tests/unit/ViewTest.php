<?php
use Nimbus\View;

test('e escapes', fn() => assert_eq('&lt;a&gt;&quot;', View::e('<a>"')));
test('e null safe', fn() => assert_eq('', View::e(null)));
test('icon returns svg with class', fn() => assert_true(strpos(View::icon('search', 'n-ico'), '<svg') === 0 && strpos(View::icon('search', 'n-ico'), 'n-ico') !== false));
test('icon unknown returns empty', fn() => assert_eq('', View::icon('nope')));
test('social icons exist', function () { foreach (['github', 'weibo', 'bilibili', 'twitter', 'email', 'rss', 'qq', 'wechat', 'telegram', 'zhihu', 'link'] as $n) assert_true(View::icon($n) !== '', "icon $n"); });
