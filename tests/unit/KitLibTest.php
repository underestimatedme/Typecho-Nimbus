<?php
use TypechoPlugin\NimbusKit\Lib\Schema;
use TypechoPlugin\NimbusKit\Lib\Visitor;
use TypechoPlugin\NimbusKit\Lib\Text;
use TypechoPlugin\NimbusKit\Lib\Sitemap;

// Schema
test('schema normalizes adapters', function () {
    assert_eq('sqlite', Schema::normalizeAdapter('Pdo_SQLite'));
    assert_eq('pgsql', Schema::normalizeAdapter('Pdo_Pgsql'));
    assert_eq('mysql', Schema::normalizeAdapter('Mysqli'));
});
test('schema creates five prefixed tables for every adapter', function () {
    foreach (['Pdo_SQLite', 'Pdo_Mysql', 'Pgsql'] as $a) {
        $sql = implode("\n", Schema::forAdapter($a, 'tc_'));
        foreach (Schema::TABLES as $t) assert_true(strpos($sql, "CREATE TABLE IF NOT EXISTS tc_$t") !== false, "$a $t");
    }
});
test('sqlite schema has no mysql syntax and has unique like index', function () {
    $sql = implode("\n", Schema::forAdapter('Pdo_SQLite', 'tc_'));
    assert_true(strpos($sql, 'AUTO_INCREMENT') === false && strpos($sql, 'ENGINE=') === false);
    assert_true(strpos($sql, 'CREATE UNIQUE INDEX IF NOT EXISTS tc_nimbus_like_uniq') !== false);
});
test('mysql schema declares unique key inline', function () {
    $sql = implode("\n", Schema::forAdapter('Mysqli', 'tc_'));
    assert_true(strpos($sql, 'UNIQUE KEY tc_nimbus_like_uniq') !== false && strpos($sql, 'CREATE UNIQUE INDEX') === false);
});
test('schema sqlite statements execute on a real sqlite db', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    foreach (Schema::forAdapter('Pdo_SQLite', 'tc_') as $s) { $pdo->exec($s); $pdo->exec($s); } // 重复执行安全
    $pdo->exec("INSERT INTO tc_nimbus_like_log (type,cid,visitor,created) VALUES ('post',1,'v',0)");
    $dup = false;
    try { $pdo->exec("INSERT INTO tc_nimbus_like_log (type,cid,visitor,created) VALUES ('post',1,'v',0)"); } catch (PDOException $e) { $dup = true; }
    assert_true($dup, 'duplicate like must fail');
});

// Visitor
test('fingerprint stable, salted, 40 chars', function () {
    $a = Visitor::fingerprint('s', '1.2.3.4', 'UA');
    assert_eq(40, strlen($a));
    assert_eq($a, Visitor::fingerprint('s', '1.2.3.4', 'UA'));
    assert_true($a !== Visitor::fingerprint('t', '1.2.3.4', 'UA'));
});
test('sameOrigin', function () {
    assert_true(Visitor::sameOrigin('http://me.com', null, 'http://me.com/'));
    assert_true(Visitor::sameOrigin(null, 'http://me.com/a/b', 'http://me.com/'));
    assert_eq(false, Visitor::sameOrigin('http://evil.com', 'http://me.com/', 'http://me.com/'));
    assert_eq(false, Visitor::sameOrigin(null, null, 'http://me.com/'));
    assert_eq(false, Visitor::sameOrigin('null', null, 'http://me.com/'));
});

// Text
test('moment escapes html and autolinks only http(s)', function () {
    $h = Text::moment("<script>alert(1)</script>\n看 https://a.com/x?y=1 和 javascript:alert(1)");
    assert_true(strpos($h, '<script>') === false, 'no raw script');
    assert_true(strpos($h, '&lt;script&gt;') !== false, 'escaped');
    assert_true(strpos($h, '<a href="https://a.com/x?y=1"') !== false, 'link');
    assert_true(strpos($h, 'href="javascript') === false, 'no js link');
    assert_true(strpos($h, '<br>') !== false, 'newline');
});
test('safeUrl', function () {
    assert_eq('https://a.com/x.png', Text::safeUrl(' https://a.com/x.png '));
    assert_eq('/usr/uploads/a.png', Text::safeUrl('/usr/uploads/a.png'));
    assert_eq('', Text::safeUrl('javascript:alert(1)'));
    assert_eq('', Text::safeUrl('//evil.com/x'));
    assert_eq('', Text::safeUrl('https://a.com/"onerror="x'));
});
test('photos parse url|caption and drop bad lines', function () {
    $p = Text::photos("https://a.com/1.jpg|海边\n\njavascript:x\nhttps://a.com/2.jpg");
    assert_eq([['url' => 'https://a.com/1.jpg', 'caption' => '海边'], ['url' => 'https://a.com/2.jpg', 'caption' => '']], $p);
});
test('excerpt centers on keyword', function () {
    $t = str_repeat('前文', 60) . '关键词在这里' . str_repeat('后文', 60);
    $x = Text::excerpt($t, '关键词', 40);
    assert_true(mb_strpos($x, '关键词') !== false && mb_strpos($x, '…') === 0);
});
test('likePattern neutralizes wildcards', fn() => assert_eq('%100_a_%', Text::likePattern('100%a_')));
test('contains is exact and case-insensitive', function () {
    assert_true(Text::contains('Hello CSS World', 'css'));
    assert_eq(false, Text::contains('a\\b', '%%'));
    assert_eq(false, Text::contains('anything', ''));
});

// Sitemap
test('sitemap escapes and formats lastmod', function () {
    $x = Sitemap::xml([['loc' => 'http://a.com/?a=1&b=2', 'lastmod' => 0, 'priority' => '1.0'], ['loc' => 'http://a.com/p', 'lastmod' => 1700000000, 'priority' => '0.8']]);
    assert_true(strpos($x, '<loc>http://a.com/?a=1&amp;b=2</loc>') !== false);
    assert_true(strpos($x, '<lastmod>2023-11-14T22:13:20Z</lastmod>') !== false);
    $doc = new DOMDocument(); assert_true($doc->loadXML($x), 'valid xml');
});

test('groupBookmarks orders groups by min sort and items within', function () {
    $g = Text::groupBookmarks([
        ['id' => 1, 'group' => '工具', 'sort' => 5], ['id' => 2, 'group' => 'AI', 'sort' => 1],
        ['id' => 3, 'group' => '工具', 'sort' => 2], ['id' => 4, 'group' => '', 'sort' => 9],
    ]);
    assert_eq(['AI', '工具', '未分组'], array_column($g, 'name'));
    assert_eq([3, 1], array_column($g[1]['items'], 'id'));
});
test('tags split on commas, keep spaces inside names, dedupe', fn() => assert_eq(['Next.js', 'PHP', 'Vue 3'], Text::tags('Next.js, PHP，PHP、Vue 3')));
test('dayKey', fn() => assert_eq((int)date('Ymd', 1700000000), Text::dayKey(1700000000)));
test('plain text drops hidden content (search/excerpt)', fn() => assert_eq('公开', Text::plain('公开[hide]秘密 ZXQ[/hide]')));
