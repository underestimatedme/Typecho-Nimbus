<?php
use Nimbus\Options;

$store = [];
Options::setSource(function (string $k) use (&$store) { return $store[$k] ?? null; });

test('get returns stored value', function () use (&$store) { $store['SiteSubtitle'] = 'hi'; assert_eq('hi', Options::get('SiteSubtitle')); });
test('get falls back to default when empty string', function () use (&$store) { $store['SiteSubtitle'] = ''; assert_eq('x', Options::get('SiteSubtitle', 'x')); });
test('get falls back to config default when missing', function () use (&$store) { unset($store['ListStyle']); assert_eq('card', Options::get('ListStyle')); });
test('bool parses 1/0', function () use (&$store) { $store['HeroEnable'] = '0'; assert_eq(false, Options::bool('HeroEnable')); $store['HeroEnable'] = '1'; assert_eq(true, Options::bool('HeroEnable')); });
test('int parses', function () use (&$store) { $store['RelatedCount'] = '7'; assert_eq(7, Options::int('RelatedCount')); $store['RelatedCount'] = 'abc'; assert_eq(4, Options::int('RelatedCount')); });
test('list splits on newline, comma, fullwidth comma, 、 and ;', function () use (&$store) { $store['TopPosts'] = "1, 2，3、4;5\n6\n\n6"; assert_eq(['1','2','3','4','5','6'], Options::list('TopPosts')); });
test('json returns null on invalid', function () use (&$store) { $store['CustomMenu'] = '{bad'; assert_eq(null, Options::json('CustomMenu')); $store['CustomMenu'] = '[{"a":1}]'; assert_eq([['a'=>1]], Options::json('CustomMenu')); });
