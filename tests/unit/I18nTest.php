<?php
use Nimbus\I18n;
require_once __DIR__ . '/../i18n-keys.php';

test('Accept-Language 协商', function () {
    assert_eq('en', I18n::negotiate('en-US,en;q=0.9,zh;q=0.5'));
    assert_eq('zh', I18n::negotiate('zh-CN,zh;q=0.9,en;q=0.8'));
    assert_eq('en', I18n::negotiate('fr-FR, en-GB;q=0.7'));
    assert_eq('zh', I18n::negotiate('fr-FR, de'));
    assert_eq('zh', I18n::negotiate(''));
});
test('中文模式原样返回并替换占位符', function () {
    I18n::set('zh');
    assert_eq('共 3 篇文章', I18n::t('共 {n} 篇文章', ['n' => 3]));
    assert_eq('zh-CN', I18n::htmlLang());
    assert_eq([], I18n::js());
    I18n::set(null);
});
test('英文模式查表、未收录回落原文、转义', function () {
    I18n::set('en');
    assert_eq('{n} posts', I18n::t('共 {n} 篇文章'));
    assert_eq('3 posts', I18n::t('共 {n} 篇文章', ['n' => 3]));
    assert_eq('没收录的文字', I18n::t('没收录的文字'));
    assert_eq('Reply to &lt;b&gt;', I18n::e('回复 {name}', ['name' => '<b>']));
    assert_eq('en', I18n::htmlLang());
    assert_eq('Copy', I18n::js()['复制']);
    I18n::set(null);
});
test('前台源码中的翻译键都有英文译文', function () {
    $root = dirname(__DIR__, 2);
    $keys = nimbus_i18n_keys($root);
    $pack = include $root . '/Nimbus/lang/en.php';
    $miss = [];
    foreach ($keys['ui'] as $k) if (!isset($pack['ui'][$k]) && !isset($pack['js'][$k])) $miss[] = $k;
    foreach ($keys['js'] as $k) if (!isset($pack['js'][$k])) $miss[] = '[js] ' . $k;
    assert_eq([], $miss);
    assert_true(count($keys['ui']) > 150 && count($keys['js']) > 20, '键提取数量异常');
});
test('译文保留原文中的占位符', function () {
    $pack = include dirname(__DIR__, 2) . '/Nimbus/lang/en.php';
    $bad = [];
    foreach (array_merge($pack['ui'], $pack['js']) as $zh => $en) {
        preg_match_all('/\{(\w+)\}/', $zh, $a); preg_match_all('/\{(\w+)\}/', $en, $b);
        $x = $a[1]; $y = $b[1]; sort($x); sort($y);
        if ($x !== $y) $bad[] = $zh;
    }
    assert_eq([], $bad);
});
