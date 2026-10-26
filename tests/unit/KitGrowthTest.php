<?php
use TypechoPlugin\NimbusKit\Lib\Growth;
use TypechoPlugin\NimbusKit\Lib\Schema;

test('growth attributes known campaigns before referrers without preserving raw values', function () {
    assert_eq('juejin', Growth::source('JueJin', 'google.com', 'blog.example'));
    assert_eq('other', Growth::source('private-campaign-123', '', 'blog.example'));
    assert_eq('wechat', Growth::source('weixin', '', 'blog.example'));
});
test('growth recognizes real referrer hosts but rejects lookalikes', function () {
    assert_eq('juejin', Growth::source('', 'link.juejin.cn', 'blog.example'));
    assert_eq('github', Growth::source('', 'github.com', 'blog.example'));
    assert_eq('other', Growth::source('', 'juejin.cn.evil.test', 'blog.example'));
    assert_eq('direct', Growth::source('', 'blog.example', 'blog.example'));
    assert_eq('direct', Growth::source('', '', 'blog.example'));
});
test('growth bounds goal IDs and discards credential, executable and protocol-relative URLs', function () {
    $raw = "code|Source|https://github.com/example/theme\nintro|介绍|/intro.html\nx|x|javascript:alert(1)\ny|y|//evil.test\nz|z|https://name:secret@host.test\nBAD ID|x|https://host.test\ncode|Duplicate|https://other.test";
    assert_eq([
        ['key' => 'code', 'label' => 'Source', 'url' => 'https://github.com/example/theme'],
        ['key' => 'intro', 'label' => '介绍', 'url' => '/intro.html'],
    ], Growth::goals($raw));
});
test('growth cookie scope changes with a configured destination', function () {
    assert_true(Growth::goalCookie('code', 'https://a.test') !== Growth::goalCookie('code', 'https://b.test'));
    assert_true((bool)preg_match('/^nimbus_g_[a-f0-9]{16}$/', Growth::goalCookie('intro', '/intro.html')));
});
test('growth aggregate schema works on SQLite and separates goal/source/day counters', function () {
    $db = new PDO('sqlite::memory:');
    $db->exec(Schema::growthStatement('sqlite', 't_'));
    $q = $db->prepare('INSERT INTO t_nimbus_growth (day,kind,source,goal,hits) VALUES (?,?,?,?,?)');
    $q->execute([20261026, 'entry', 'juejin', '', 1]);
    $q->execute([20261026, 'goal', 'juejin', 'code', 1]);
    $q->execute([20261026, 'goal', 'github', 'code', 1]);
    $db->exec("UPDATE t_nimbus_growth SET hits=hits+1 WHERE day=20261026 AND kind='goal' AND source='juejin' AND goal='code'");
    assert_eq(2, (int)$db->query("SELECT hits FROM t_nimbus_growth WHERE kind='goal' AND source='juejin'")->fetchColumn());
    assert_eq(1, (int)$db->query("SELECT hits FROM t_nimbus_growth WHERE kind='entry'")->fetchColumn());
});
