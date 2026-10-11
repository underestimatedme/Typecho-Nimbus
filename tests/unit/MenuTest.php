<?php
use Nimbus\Menu;

$pages = [['title' => '关于', 'permalink' => '/about'], ['title' => '友链', 'permalink' => '/links']];
test('build from pages', function () use ($pages) { $m = Menu::build($pages, null); assert_eq('关于', $m[0]['title']); assert_eq('/about', $m[0]['url']); assert_eq([], $m[0]['children']); });
test('build from custom json', function () use ($pages) { $m = Menu::build($pages, [['title' => 'A', 'url' => '/a', 'children' => [['title' => 'B', 'url' => '/b']]]]); assert_eq('A', $m[0]['title']); assert_eq('B', $m[0]['children'][0]['title']); assert_eq('/b', $m[0]['children'][0]['url']); });
test('build ignores invalid custom entries and falls back', function () use ($pages) { $m = Menu::build($pages, [['nope' => 1]]); assert_eq('关于', $m[0]['title']); });
test('build keeps icon', function () use ($pages) { $m = Menu::build($pages, [['title' => 'A', 'url' => '/a', 'icon' => 'github']]); assert_eq('github', $m[0]['icon']); });

test('Cuteen menu fragments keep name fields and dropdown without parent URL', function () use ($pages) {
    $raw = '{"name":"编程","children":[{"name":"Go","url":"/category/go/"}]}, {"name":"闪念","url":"/blink.html"}';
    $m = Menu::build($pages, Menu::parse($raw));
    assert_eq('编程', $m[0]['title']);
    assert_eq('#', $m[0]['url']);
    assert_eq('Go', $m[0]['children'][0]['title']);
    assert_eq('/blink.html', $m[1]['url']);
    assert_eq(false, Menu::isCurrent('#', '/'));
});
test('menu parser accepts Nimbus arrays and rejects malformed JSON', function () {
    assert_eq([['title' => 'A', 'url' => '/a']], Menu::parse('[{"title":"A","url":"/a"}]'));
    assert_eq(null, Menu::parse('{bad'));
});
test('default menu retains category hierarchy and page grouping', function () use ($pages) {
    $cats = [['mid' => 1, 'parent' => 0, 'name' => '编程', 'permalink' => '/category/code/'], ['mid' => 2, 'parent' => 1, 'name' => 'Go', 'permalink' => '/category/go/']];
    $m = Menu::build($pages, null, $cats, false, '页面');
    assert_eq('编程', $m[0]['title']);
    assert_eq('/category/go/', $m[0]['children'][0]['url']);
    assert_eq('页面', $m[1]['title']);
    assert_eq('关于', $m[1]['children'][0]['title']);
    $m = Menu::build($pages, null, $cats, true);
    assert_eq('关于', $m[0]['title']);
    assert_eq('编程', $m[2]['title']);
});
test('Cuteen dropdown retains third-level categories', function () {
    $cats = [
        ['mid' => 1, 'parent' => 0, 'name' => '编程', 'permalink' => '/code/'],
        ['mid' => 2, 'parent' => 1, 'name' => 'Go', 'permalink' => '/go/'],
        ['mid' => 3, 'parent' => 2, 'name' => '进阶', 'permalink' => '/advanced/'],
    ];
    $m = Menu::build([], null, $cats);
    assert_eq(['/go/', '/advanced/'], array_column($m[0]['children'], 'url'));
});
