<?php
use TypechoPlugin\NimbusKit\Lib\GitHub;

$site = 'https://blog.example.com/';
test('github state 往返与过期', function () {
    $s = GitHub::state('k', 'https://blog.example.com/a', 1000);
    assert_eq('https://blog.example.com/a', GitHub::verifyState('k', $s, 1500));
    assert_eq(null, GitHub::verifyState('k', $s, 1000 + GitHub::STATE_TTL + 1));
    assert_eq(null, GitHub::verifyState('other', $s, 1500));
    assert_eq(null, GitHub::verifyState('', $s, 1500));
});
test('github state 篡改即失效', function () {
    $s = GitHub::state('k', '/a', 1000);
    [$body, $mac] = explode('.', $s);
    $evil = rtrim(strtr(base64_encode('{"b":"https://evil.com","exp":99999,"n":"x"}'), '+/', '-_'), '=');
    assert_eq(null, GitHub::verifyState('k', $evil . '.' . $mac, 1500));
    assert_eq(null, GitHub::verifyState('k', $body . '.' . $mac . 'x', 1500));
    assert_eq(null, GitHub::verifyState('k', 'garbage', 1500));
    assert_eq(null, GitHub::verifyState('k', 'a.b.c', 1500));
});
test('safeBack 只允许本站', function () use ($site) {
    assert_eq('https://blog.example.com/archives/1/#comments', GitHub::safeBack('https://blog.example.com/archives/1/#comments', $site));
    assert_eq('https://blog.example.com/x?y=1', GitHub::safeBack('/x?y=1', $site));
    foreach (['https://evil.com/', '//evil.com/x', '/\\evil.com', 'javascript:alert(1)', 'https://blog.example.com.evil.com/', 'http://blog.example.com/', 'https://blog.example.com:8443/', "/a\r\nSet-Cookie: x", ''] as $bad) {
        assert_eq('https://blog.example.com/', GitHub::safeBack($bad, $site), $bad);
    }
    assert_eq('http://127.0.0.1:8099/p', GitHub::safeBack('/p', 'http://127.0.0.1:8099'));
    assert_eq('http://127.0.0.1:8099/p', GitHub::safeBack('http://127.0.0.1:8099/p', 'http://127.0.0.1:8099'));
});
test('identity 公开邮箱 / noreply / 名称清洗', function () {
    $a = GitHub::identity(['id' => 583231, 'login' => 'octocat', 'name' => 'The <Octocat>', 'email' => null]);
    assert_eq('583231+octocat@users.noreply.github.com', $a['mail']);
    assert_eq('The Octocat', $a['name']);
    assert_eq('https://github.com/octocat', $a['url']);
    assert_eq('a@b.co', GitHub::identity(['id' => 1, 'login' => 'a', 'email' => 'a@b.co'])['mail']);
    assert_eq('a', GitHub::identity(['id' => 1, 'login' => 'a', 'name' => '  '])['name']);
    assert_eq(null, GitHub::identity(['id' => 0, 'login' => 'a']));
    assert_eq(null, GitHub::identity(['id' => 5, 'login' => '../x']));
});
test('身份 cookie 签名校验', function () {
    $id = GitHub::identity(['id' => 7, 'login' => 'neo', 'name' => 'Neo']);
    $c = GitHub::cookieValue('k', $id, 100);
    assert_eq($id, GitHub::readCookie('k', $c, 200));
    assert_eq(null, GitHub::readCookie('k', $c, 100 + GitHub::COOKIE_TTL + 1));
    assert_eq(null, GitHub::readCookie('x', $c, 200));
});
test('authorizeUrl 编码参数', function () {
    $u = GitHub::authorizeUrl('https://github.com/', 'cid', 'https://b.com/cb?x=1', 's.t');
    assert_true(strpos($u, 'https://github.com/login/oauth/authorize?client_id=cid&redirect_uri=https%3A%2F%2Fb.com%2Fcb%3Fx%3D1&state=s.t') === 0, $u);
});
