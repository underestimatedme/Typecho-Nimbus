<?php
use Nimbus\Menu;

$pages = [['title' => '关于', 'permalink' => '/about'], ['title' => '友链', 'permalink' => '/links']];
test('build from pages', function () use ($pages) { $m = Menu::build($pages, null); assert_eq('关于', $m[0]['title']); assert_eq('/about', $m[0]['url']); assert_eq([], $m[0]['children']); });
test('build from custom json', function () use ($pages) { $m = Menu::build($pages, [['title' => 'A', 'url' => '/a', 'children' => [['title' => 'B', 'url' => '/b']]]]); assert_eq('A', $m[0]['title']); assert_eq('B', $m[0]['children'][0]['title']); assert_eq('/b', $m[0]['children'][0]['url']); });
test('build ignores invalid custom entries and falls back', function () use ($pages) { $m = Menu::build($pages, [['nope' => 1]]); assert_eq('关于', $m[0]['title']); });
test('build keeps icon', function () use ($pages) { $m = Menu::build($pages, [['title' => 'A', 'url' => '/a', 'icon' => 'github']]); assert_eq('github', $m[0]['icon']); });
