<?php
use Nimbus\Links;

test('parse valid', fn() => assert_eq([['name' => 'A', 'url' => 'https://a', 'avatar' => '', 'desc' => '']], Links::parse('[{"name":"A","url":"https://a"}]')));
test('parse drops invalid', fn() => assert_eq([], Links::parse('[{"name":"A"},{"url":"x"}]')));
test('parse empty on bad json', fn() => assert_eq([], Links::parse('{')));
test('parse keeps avatar and desc', fn() => assert_eq('d', Links::parse('[{"name":"A","url":"https://a","avatar":"x.png","desc":"d"}]')[0]['desc']));
test('initial letter', fn() => assert_eq('云', Links::initial('云间客')) ?: assert_eq('T', Links::initial('typecho')));
